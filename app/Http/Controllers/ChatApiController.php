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

        $conversationId = $data['conversation_id'] ?? (string) Str::uuid();
        $sessionId = $data['session_id'] ?? null;

        CorrelationContext::addContext('conversation_id', $conversationId);
        if ($sessionId) {
            CorrelationContext::addContext('session_id', $sessionId);
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
}
