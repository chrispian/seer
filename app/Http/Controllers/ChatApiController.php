<?php

namespace App\Http\Controllers;

use App\Services\Telemetry\ChatTelemetry;
use App\Services\Telemetry\CorrelationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatApiController extends Controller
{
    public function send(Request $req)
    {
        $startTime = microtime(true);
        $messageId = (string) Str::uuid();

        // Add message_id to correlation context for tracking
        CorrelationContext::addContext('message_id', $messageId);
        CorrelationContext::addContext('operation', 'chat_send');

        try {
            $data = $req->validate([
                'content' => 'required|string',
                'conversation_id' => 'nullable|string',
                'session_id' => 'nullable|integer|exists:chat_sessions,id',
                'attachments' => 'array',
                'provider' => 'nullable|string',
                'model' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            ChatTelemetry::logValidationError($e->errors(), $req->all());
            throw $e;
        }

        $validationTime = microtime(true);
        ChatTelemetry::logMessageReceived($data);

        \Log::info('Chat message received', [
            'content' => $data['content'],
            'content_length' => strlen($data['content']),
            'starts_with_exec' => str_starts_with(trim($data['content']), ':exec-tool'),
        ]);

        $conversationId = $data['conversation_id'] ?? (string) Str::uuid();
        $sessionId = $data['session_id'] ?? null;

        CorrelationContext::addContext('conversation_id', $conversationId);
        if ($sessionId) {
            CorrelationContext::addContext('session_id', $sessionId);
        }

        // Check for pending approval responses (before other processing)
        if (!str_starts_with(trim($data['content']), ':')) {
            $approvalManager = app(\App\Services\Security\ApprovalManager::class);
            $pendingApprovals = $approvalManager->getPendingForConversation($conversationId);
            
            if ($pendingApprovals->isNotEmpty()) {
                $intent = $approvalManager->detectApprovalInMessage($data['content']);
                
                if ($intent) {
                    $approval = $pendingApprovals->first();
                    
                    if ($intent === 'approve') {
                        $approvalManager->approveRequest($approval, auth()->id(), 'natural_language', $data['content']);
                        
                        return response()->json([
                            'message_id' => $messageId,
                            'conversation_id' => $conversationId,
                            'skip_stream' => true,
                            'assistant_message' => "✓ Interpreting as approval. Executing command...",
                            'approval_approved' => true,
                            'approval_id' => $approval->id,
                        ]);
                    } elseif ($intent === 'reject') {
                        $approvalManager->rejectRequest($approval, auth()->id(), 'natural_language', $data['content']);
                        
                        return response()->json([
                            'message_id' => $messageId,
                            'conversation_id' => $conversationId,
                            'skip_stream' => true,
                            'assistant_message' => "✗ Request rejected. I won't proceed with that operation.",
                            'approval_rejected' => true,
                            'approval_id' => $approval->id,
                        ]);
                    }
                }
            }
        }

        if (str_starts_with(trim($data['content']), ':exec-tool')) {
            \Log::info('Exec-tool prefix detected', [
                'content' => $data['content'],
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
            ]);
            return $this->handleExecTool($data, $conversationId, $sessionId, $messageId);
        }

        // Check if tool-aware turn is enabled
        if (config('fragments.tool_aware_turn.enabled', false)) {
            \Log::info('Tool-aware turn enabled, routing to pipeline', [
                'message_id' => $messageId,
                'session_id' => $sessionId,
            ]);
            return $this->handleToolAwareTurn($data, $conversationId, $sessionId, $messageId);
        }

        // Get session-specific model settings if session_id provided
        $sessionProvider = null;
        $sessionModel = null;
        if ($sessionId) {
            $chatSession = \App\Models\ChatSession::find($sessionId);
            if ($chatSession && $chatSession->model_provider && $chatSession->model_name) {
                $sessionProvider = $chatSession->model_provider;
                $sessionModel = $chatSession->model_name;
            }
        }

        $providerSelectionTime = microtime(true);

        // ✅ 1) Create USER fragment using chat-specific action (bypasses deduplication)
        $createChatFragment = app(\App\Actions\CreateChatFragment::class);
        $fragment = $createChatFragment($data['content']);
        $userFragmentId = $fragment->id;

        $fragmentCreationTime = microtime(true);
        ChatTelemetry::logUserFragmentCreated($userFragmentId, [
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
            'content' => $data['content'],
        ]);

        // Determine which provider and model to use (priority: request > session > fallback)
        $useProvider = $data['provider'] ?? $sessionProvider ?? config('fragments.models.fallback_provider', 'ollama');
        $useModel = $data['model'] ?? $sessionModel ?? config('fragments.models.fallback_text_model', 'llama3:latest');

        // Determine selection source for telemetry
        $selectionSource = 'fallback';
        if (isset($data['provider']) || isset($data['model'])) {
            $selectionSource = 'request';
        } elseif ($sessionProvider || $sessionModel) {
            $selectionSource = 'session';
        }

        ChatTelemetry::logProviderSelection([
            'provider' => $useProvider,
            'model' => $useModel,
            'source' => $selectionSource,
            'session_id' => $sessionId,
        ]);

        // Update the fragment with chat-specific metadata
        $fragment->update([
            'metadata' => array_merge($fragment->metadata ?? [], [
                'turn' => 'prompt',
                'conversation_id' => $conversationId,
                'session_id' => $sessionId,
                'provider' => $useProvider,
                'model' => $useModel,
            ]),
        ]);

        // Minimal chat history for the AI call (extend with real history later)
        // TODO: Implement real history
        // TODO: Implement real system message system so they aren't hard coded.
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user',   'content' => $data['content']],
        ];

        // Cache chat session using dedicated action
        $cacheStartTime = microtime(true);
        app(\App\Actions\CacheChatSession::class)(
            $messageId,
            $messages,
            $useProvider,
            $useModel,
            $userFragmentId,
            $conversationId
        );

        $cacheTime = microtime(true);
        ChatTelemetry::logSessionCached($messageId, [
            'provider' => $useProvider,
            'model' => $useModel,
            'conversation_id' => $conversationId,
            'messages' => $messages,
            'session_id' => $sessionId,
        ]);

        // Add message to ChatSession if session_id provided
        if ($sessionId) {
            $chatSession = \App\Models\ChatSession::find($sessionId);
            if ($chatSession) {
                $messageData = [
                    'id' => $userFragmentId,
                    'type' => 'user',
                    'message' => $data['content'],
                    'fragment_id' => $userFragmentId,
                    'created_at' => now()->toISOString(),
                ];
                $chatSession->addMessage($messageData);

                ChatTelemetry::logSessionMessageAdded($sessionId, $messageData);
            }
        }

        $endTime = microtime(true);

        // Log performance metrics
        ChatTelemetry::logSessionMetrics($messageId, [
            'total_duration_ms' => round(($endTime - $startTime) * 1000, 2),
            'validation_duration_ms' => round(($validationTime - $startTime) * 1000, 2),
            'fragment_creation_duration_ms' => round(($fragmentCreationTime - $providerSelectionTime) * 1000, 2),
            'provider_selection_duration_ms' => round(($providerSelectionTime - $validationTime) * 1000, 2),
            'cache_duration_ms' => round(($cacheTime - $cacheStartTime) * 1000, 2),
        ]);

        return response()->json([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'user_fragment_id' => $userFragmentId,
        ]);
    }

    public function stream(string $messageId)
    {
        // Add to correlation context
        CorrelationContext::addContext('message_id', $messageId);
        CorrelationContext::addContext('operation', 'chat_stream');

        // Retrieve and validate session
        $session = app(\App\Actions\RetrieveChatSession::class)($messageId);

        // Add session context for correlation
        CorrelationContext::addContext('conversation_id', $session['conversation_id']);
        CorrelationContext::addContext('provider', $session['provider']);
        CorrelationContext::addContext('model', $session['model']);

        // Check if this is a tool-aware request
        if ($session['provider'] === 'tool-aware') {
            return $this->streamToolAware($messageId, $session);
        }

        ChatTelemetry::logStreamingStarted($messageId, $session);

        return new StreamedResponse(function () use ($session, $messageId) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);

            // Start latency measurement
            $startTime = microtime(true);
            $deltaCount = 0;
            $totalLength = 0;
            $firstTokenTime = null;

            try {
                // Stream using the new provider system with enhanced telemetry
                $streamResult = app(\App\Actions\StreamChatProvider::class)(
                    $session['provider'],
                    $session['messages'],
                    [
                        'model' => $session['model'],
                        'temperature' => 0.7, // Could be configurable
                    ],
                    // onDelta callback with telemetry
                    function ($delta) use (&$deltaCount, &$totalLength, $startTime, $messageId, &$firstTokenTime, $session) {
                        // Track time to first token
                        if ($firstTokenTime === null) {
                            $firstTokenTime = microtime(true);
                            $timeToFirstToken = round(($firstTokenTime - $startTime) * 1000, 2);

                            ChatTelemetry::logFirstToken($messageId, [
                                'time_to_first_token_ms' => $timeToFirstToken,
                                'provider' => $session['provider'] ?? 'unknown',
                                'model' => $session['model'] ?? 'unknown',
                            ]);
                        }

                        $deltaCount++;
                        $totalLength += strlen($delta);

                        echo 'data: '.json_encode(['type' => 'assistant_delta', 'content' => $delta])."\n\n";
                        @ob_flush();
                        @flush();

                        // Log progress every 10 deltas
                        if ($deltaCount % 10 === 0) {
                            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
                            ChatTelemetry::logStreamingProgress($messageId, [
                                'delta_count' => $deltaCount,
                                'total_length' => $totalLength,
                                'elapsed_ms' => $elapsedMs,
                                'tokens_per_second' => $elapsedMs > 0 ? round(($deltaCount / $elapsedMs) * 1000, 2) : null,
                                'time_since_first_token_ms' => $firstTokenTime ? round((microtime(true) - $firstTokenTime) * 1000, 2) : null,
                            ]);
                        }
                    },
                    // onComplete callback
                    function () {
                        echo 'data: '.json_encode(['type' => 'done'])."\n\n";
                        @ob_flush();
                        @flush();
                    }
                );
            } catch (\Exception $e) {
                $streamingDuration = round((microtime(true) - $startTime) * 1000, 2);

                // Get enhanced error context from correlation context
                $errorContext = CorrelationContext::getContext();

                ChatTelemetry::logStreamingError($messageId, $e, [
                    'duration_ms' => $streamingDuration,
                    'provider' => $session['provider'],
                    'model' => $session['model'],
                    'error_category' => $errorContext['error_category'] ?? 'unknown',
                    'is_retryable' => $errorContext['is_retryable'] ?? false,
                    'total_deltas_received' => $deltaCount,
                    'content_length_at_failure' => $totalLength,
                    'time_to_first_token_ms' => $firstTokenTime ? round(($firstTokenTime - $startTime) * 1000, 2) : null,
                ]);

                // Handle streaming errors
                $errorMessage = "[Stream error: {$e->getMessage()}]";
                echo 'data: '.json_encode(['type' => 'assistant_delta', 'content' => $errorMessage])."\n\n";
                echo 'data: '.json_encode(['type' => 'done'])."\n\n";
                @ob_flush();
                @flush();

                return;
            }

            // Calculate latency and extract token usage
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $tokenUsage = app(\App\Actions\ExtractTokenUsage::class)($session['provider'], $streamResult['provider_response']);

            // Log streaming completion
            ChatTelemetry::logStreamingCompleted($messageId, [
                'final_message' => $streamResult['final_message'],
                'duration_ms' => $latencyMs,
                'token_usage' => $tokenUsage,
                'provider_response' => $streamResult['provider_response'],
            ]);

            // Process assistant fragment using pipeline
            $assistantData = [
                'message' => $streamResult['final_message'],
                'provider' => $session['provider'],
                'model' => $session['model'],
                'conversation_id' => $session['conversation_id'],
                'session_id' => $session['session_id'],
                'user_fragment_id' => $session['user_fragment_id'],
                'latency_ms' => $latencyMs,
                'token_usage' => $tokenUsage,
            ];

            ChatTelemetry::logAssistantFragmentProcessingStarted($assistantData);
            $assistantFragment = app(\App\Actions\ProcessAssistantFragment::class)($assistantData);

            // Log final transaction summary
            ChatTelemetry::logChatTransactionSummary([
                'message_id' => $messageId,
                'conversation_id' => $session['conversation_id'],
                'total_duration_ms' => $latencyMs,
                'user_fragment_id' => $session['user_fragment_id'],
                'assistant_fragment_id' => $assistantFragment->id,
                'provider' => $session['provider'],
                'model' => $session['model'],
                'input_length' => array_sum(array_map(fn ($msg) => strlen($msg['content'] ?? ''), $session['messages'])),
                'output_length' => strlen($streamResult['final_message']),
                'token_usage' => $tokenUsage,
                'success' => true,
                'error_occurred' => false,
                'enrichment_completed' => true, // This will be updated by ProcessAssistantFragment if needed
            ]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    protected function handleExecTool(array $data, string $conversationId, ?int $sessionId, string $messageId)
    {
        \Log::info('handleExecTool called', [
            'message_id' => $messageId,
            'enabled' => config('fragments.tools.exec_tool.enabled', false),
        ]);

        if (! config('fragments.tools.exec_tool.enabled', false)) {
            \Log::warning('Exec tool not enabled');
            return response()->json([
                'error' => 'Exec tool is not enabled',
                'message_id' => $messageId,
            ], 403);
        }

        $content = trim($data['content']);
        $command = trim(substr($content, strlen(':exec-tool')));
        
        if (empty($command)) {
            $command = config('fragments.tools.exec_tool.default_command', 'ls -asl');
        }

        \Log::info('Exec tool command parsed', [
            'command' => $command,
            'message_id' => $messageId,
        ]);

        $createChatFragment = app(\App\Actions\CreateChatFragment::class);
        $fragment = $createChatFragment($data['content']);
        $userFragmentId = $fragment->id;

        $fragment->update([
            'metadata' => array_merge($fragment->metadata ?? [], [
                'turn' => 'prompt',
                'conversation_id' => $conversationId,
                'session_id' => $sessionId,
                'tool' => 'exec',
                'command' => $command,
            ]),
        ]);

        if ($sessionId) {
            $chatSession = \App\Models\ChatSession::find($sessionId);
            if ($chatSession) {
                $messageData = [
                    'id' => $userFragmentId,
                    'type' => 'user',
                    'message' => $data['content'],
                    'fragment_id' => $userFragmentId,
                    'created_at' => now()->toISOString(),
                ];
                $chatSession->addMessage($messageData);
            }
        }

        $toolRegistry = app(\App\Services\Tools\ToolRegistry::class);

        $workdir = config('fragments.tools.exec_tool.workdir') ?? config('fragments.tools.shell.workdir');
        $timeout = config('fragments.tools.exec_tool.timeout_seconds', 20);

        try {
            // Security: Check if command needs approval
            $approvalManager = app(\App\Services\Security\ApprovalManager::class);
            $riskScorer = app(\App\Services\Security\RiskScorer::class);
            
            $risk = $riskScorer->scoreCommand($command, ['workdir' => $workdir]);
            
            \Log::info('Command risk assessed', [
                'command' => $command,
                'risk_score' => $risk['score'],
                'risk_level' => $risk['level'],
                'requires_approval' => $risk['requires_approval'],
            ]);

            // If high risk, create approval request and return pending state
            // BUT: Check if this approval was already granted (via API or natural language)
            $existingApproval = \App\Models\ApprovalRequest::where('conversation_id', $conversationId)
                ->where('operation_type', 'command')
                ->whereJsonContains('operation_details->command', $command)
                ->where('status', 'approved')
                ->latest()
                ->first();

            if ($risk['requires_approval'] && !$existingApproval) {
                $approvalRequest = $approvalManager->createApprovalRequest([
                    'type' => 'command',
                    'command' => $command,
                    'summary' => "Execute: {$command}",
                    'context' => ['workdir' => $workdir],
                ], $conversationId, $messageId);

                if ($approvalRequest) {
                    \Log::info('Approval required for command', [
                        'approval_id' => $approvalRequest->id,
                        'command' => $command,
                    ]);

                    $approvalData = $approvalManager->formatForChat($approvalRequest);
                    
                    $approvalMessage = "⚠️ This command requires your approval.\n\n**Command:** `{$command}`\n\n**Risk:** {$risk['level']} ({$risk['score']}/100)";

                    return response()->json([
                        'message_id' => $messageId,
                        'conversation_id' => $conversationId,
                        'user_fragment_id' => $userFragmentId,
                        'requires_approval' => true,
                        'approval_request' => $approvalData['approval_request'],
                        'message' => $approvalMessage,
                        'skip_stream' => true,
                        'assistant_message' => $approvalMessage,
                    ]);
                }
            } elseif ($existingApproval) {
                \Log::info('Using existing approval for command', [
                    'approval_id' => $existingApproval->id,
                    'command' => $command,
                ]);
            }

            \Log::info('Calling ShellTool via registry', [
                'command' => $command,
                'workdir' => $workdir,
                'timeout' => $timeout,
            ]);

            $context = [
                'user_id' => auth()->id(),
                'session_id' => $sessionId,
                'ip_address' => request()->ip(),
                'tool' => 'exec',
                'source' => 'chat_api',
            ];

            $result = $toolRegistry->call('shell', [
                'cmd' => $command,
                'workdir' => $workdir,
                'timeout' => $timeout,
            ], $context);

            \Log::info('ShellTool execution completed', [
                'exit_code' => $result['exit_code'] ?? null,
                'success' => $result['success'] ?? false,
            ]);

            $output = $result['stdout'] ?? '';
            $stderr = $result['stderr'] ?? '';
            $exitCode = $result['exit_code'] ?? 1;

            $toolOutput = '';
            if (! empty($output)) {
                $toolOutput .= $output;
            }
            if (! empty($stderr)) {
                $toolOutput .= "\n[stderr]: ".$stderr;
            }
            $toolOutput .= "\n[exit code: {$exitCode}]";

            $assistantFragment = $createChatFragment($toolOutput);
            $assistantFragment->update([
                'metadata' => array_merge($assistantFragment->metadata ?? [], [
                    'turn' => 'completion',
                    'conversation_id' => $conversationId,
                    'session_id' => $sessionId,
                    'tool' => 'exec',
                    'command' => $command,
                    'exit_code' => $exitCode,
                    'user_fragment_id' => $userFragmentId,
                ]),
            ]);

            if ($sessionId) {
                $chatSession = \App\Models\ChatSession::find($sessionId);
                if ($chatSession) {
                    $responseData = [
                        'id' => $assistantFragment->id,
                        'type' => 'assistant',
                        'message' => $toolOutput,
                        'fragment_id' => $assistantFragment->id,
                        'created_at' => now()->toISOString(),
                        'tool' => 'exec',
                    ];
                    $chatSession->addMessage($responseData);
                }
            }

            \Log::info('Exec tool completed successfully', [
                'message_id' => $messageId,
                'exit_code' => $exitCode,
                'output_length' => strlen($toolOutput),
            ]);

            return response()->json([
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
                'user_fragment_id' => $userFragmentId,
                'assistant_fragment_id' => $assistantFragment->id,
                'tool_output' => $toolOutput,
                'exit_code' => $exitCode,
                'skip_stream' => true,
                'assistant_message' => $toolOutput,
            ]);

        } catch (\Exception $e) {
            \Log::error('Exec tool execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message_id' => $messageId,
                'command' => $command,
            ]);

            $errorMessage = "Tool execution failed: {$e->getMessage()}";
            
            $assistantFragment = $createChatFragment($errorMessage);
            $assistantFragment->update([
                'metadata' => array_merge($assistantFragment->metadata ?? [], [
                    'turn' => 'completion',
                    'conversation_id' => $conversationId,
                    'session_id' => $sessionId,
                    'tool' => 'exec',
                    'command' => $command,
                    'error' => true,
                    'user_fragment_id' => $userFragmentId,
                ]),
            ]);

            if ($sessionId) {
                $chatSession = \App\Models\ChatSession::find($sessionId);
                if ($chatSession) {
                    $responseData = [
                        'id' => $assistantFragment->id,
                        'type' => 'assistant',
                        'message' => $errorMessage,
                        'fragment_id' => $assistantFragment->id,
                        'created_at' => now()->toISOString(),
                        'tool' => 'exec',
                        'error' => true,
                    ];
                    $chatSession->addMessage($responseData);
                }
            }

            return response()->json([
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
                'user_fragment_id' => $userFragmentId,
                'assistant_fragment_id' => $assistantFragment->id,
                'error' => $errorMessage,
                'skip_stream' => true,
                'assistant_message' => $errorMessage,
            ], 500);
        }
    }

    protected function handleToolAwareTurn(array $data, string $conversationId, ?int $sessionId, string $messageId)
    {
        $createChatFragment = app(\App\Actions\CreateChatFragment::class);
        
        // Create user fragment
        $fragment = $createChatFragment($data['content']);
        $userFragmentId = $fragment->id;

        $fragment->update([
            'metadata' => array_merge($fragment->metadata ?? [], [
                'turn' => 'prompt',
                'conversation_id' => $conversationId,
                'session_id' => $sessionId,
                'tool_aware' => true,
            ]),
        ]);

        // Add message to chat session
        if ($sessionId) {
            $chatSession = \App\Models\ChatSession::find($sessionId);
            if ($chatSession) {
                $messageData = [
                    'id' => $userFragmentId,
                    'type' => 'user',
                    'message' => $data['content'],
                    'fragment_id' => $userFragmentId,
                    'created_at' => now()->toISOString(),
                ];
                $chatSession->addMessage($messageData);
            }
        }

        try {
            // Cache session for streaming endpoint
            app(\App\Actions\CacheChatSession::class)(
                $messageId,
                [
                    ['role' => 'user', 'content' => $data['content']],
                ],
                'tool-aware',
                'pipeline',
                $userFragmentId,
                $conversationId,
                $sessionId
            );

            return response()->json([
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
                'user_fragment_id' => $userFragmentId,
            ]);

        } catch (\Exception $e) {
            \Log::error('Tool-aware turn failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = "I encountered an error while processing your request: {$e->getMessage()}";
            
            $assistantFragment = $createChatFragment($errorMessage);
            $assistantFragment->update([
                'metadata' => array_merge($assistantFragment->metadata ?? [], [
                    'turn' => 'completion',
                    'conversation_id' => $conversationId,
                    'session_id' => $sessionId,
                    'tool_aware' => true,
                    'error' => true,
                    'user_fragment_id' => $userFragmentId,
                ]),
            ]);

            if ($sessionId) {
                $chatSession = \App\Models\ChatSession::find($sessionId);
                if ($chatSession) {
                    $responseData = [
                        'id' => $assistantFragment->id,
                        'type' => 'assistant',
                        'message' => $errorMessage,
                        'fragment_id' => $assistantFragment->id,
                        'created_at' => now()->toISOString(),
                        'tool_aware' => true,
                        'error' => true,
                    ];
                    $chatSession->addMessage($responseData);
                }
            }

            return response()->json([
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
                'user_fragment_id' => $userFragmentId,
                'assistant_fragment_id' => $assistantFragment->id,
                'skip_stream' => true,
                'assistant_message' => $errorMessage,
                'error' => true,
            ], 500);
        }
    }

    protected function streamToolAware(string $messageId, array $session)
    {
        return new StreamedResponse(function () use ($messageId, $session) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);

            $createChatFragment = app(\App\Actions\CreateChatFragment::class);
            $sessionId = $session['session_id'] ?? null;
            $conversationId = $session['conversation_id'] ?? null;
            $userFragmentId = $session['user_fragment_id'] ?? null;
            
            $finalMessage = '';
            $correlationId = null;
            $usedTools = false;

            try {
                $pipeline = app(\App\Services\Orchestration\ToolAware\ToolAwarePipeline::class);
                
                $userMessage = $session['messages'][0]['content'] ?? '';
                
                foreach ($pipeline->executeStreaming($sessionId, $userMessage) as $event) {
                    echo 'data: ' . json_encode($event) . "\n\n";
                    @ob_flush();
                    @flush();

                    if ($event['type'] === 'final_message') {
                        $finalMessage = $event['message'];
                        $usedTools = $event['used_tools'] ?? false;
                        $correlationId = $event['correlation_id'] ?? null;
                    }
                }

                // Create assistant fragment
                if (!empty($finalMessage)) {
                    $assistantFragment = $createChatFragment($finalMessage);
                    $assistantFragment->update([
                        'metadata' => array_merge($assistantFragment->metadata ?? [], [
                            'turn' => 'completion',
                            'conversation_id' => $conversationId,
                            'session_id' => $sessionId,
                            'tool_aware' => true,
                            'used_tools' => $usedTools,
                            'correlation_id' => $correlationId,
                            'user_fragment_id' => $userFragmentId,
                        ]),
                    ]);

                    // Add to chat session
                    if ($sessionId) {
                        $chatSession = \App\Models\ChatSession::find($sessionId);
                        if ($chatSession) {
                            $chatSession->addMessage([
                                'id' => $assistantFragment->id,
                                'type' => 'assistant',
                                'message' => $finalMessage,
                                'fragment_id' => $assistantFragment->id,
                                'created_at' => now()->toISOString(),
                                'tool_aware' => true,
                                'used_tools' => $usedTools,
                                'correlation_id' => $correlationId,
                            ]);
                        }
                    }
                }

            } catch (\Exception $e) {
                \Log::error('Tool-aware streaming failed', [
                    'message_id' => $messageId,
                    'error' => $e->getMessage(),
                ]);

                echo 'data: ' . json_encode([
                    'type' => 'error',
                    'error' => 'I encountered an error while processing your request. The error has been logged.',
                ]) . "\n\n";
                
                echo 'data: ' . json_encode(['type' => 'done']) . "\n\n";
                @ob_flush();
                @flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
