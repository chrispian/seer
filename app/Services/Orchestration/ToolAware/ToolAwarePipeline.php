<?php

namespace App\Services\Orchestration\ToolAware;

use App\Services\Orchestration\ToolAware\Contracts\ComposerInterface;
use App\Services\Orchestration\ToolAware\Contracts\ContextBrokerInterface;
use App\Services\Orchestration\ToolAware\Contracts\RouterInterface;
use App\Services\Orchestration\ToolAware\Contracts\ToolRunnerInterface;
use App\Services\Orchestration\ToolAware\Contracts\ToolSelectorInterface;
use App\Services\Orchestration\ToolAware\DTOs\ExecutionTrace;
use App\Services\Orchestration\ToolAware\DTOs\OutcomeSummary;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ToolAwarePipeline
{
    public function __construct(
        protected ContextBrokerInterface $contextBroker,
        protected RouterInterface $router,
        protected ToolSelectorInterface $toolSelector,
        protected ToolRunnerInterface $toolRunner,
        protected OutcomeSummarizer $summarizer,
        protected ComposerInterface $composer
    ) {}

    /**
     * Execute the full tool-aware turn pipeline
     *
     * @param int|null $sessionId Chat session ID
     * @param string $userMessage User's message
     * @return array ['message' => string, 'correlation_id' => string|null, 'used_tools' => bool, 'metadata' => array]
     */
    public function execute(?int $sessionId, string $userMessage): array
    {
        $startTime = microtime(true);
        $pipelineId = \Illuminate\Support\Str::uuid()->toString();

        Log::info('Tool-aware pipeline started', [
            'pipeline_id' => $pipelineId,
            'session_id' => $sessionId,
            'user_message_length' => strlen($userMessage),
        ]);

        try {
            // Step 1: Assemble context
            $context = $this->contextBroker->assemble($sessionId, $userMessage);
            
            Log::debug('Context assembled', [
                'pipeline_id' => $pipelineId,
                'summary_length' => strlen($context->conversation_summary),
                'tool_preview_count' => count($context->tool_registry_preview),
            ]);

            // Step 2: Router decision
            $decision = $this->router->decide($context);

            Log::info('Router decision', [
                'pipeline_id' => $pipelineId,
                'needs_tools' => $decision->needs_tools,
                'goal' => $decision->high_level_goal,
                'rationale' => $decision->rationale,
            ]);

            // If no tools needed, compose direct response
            if (!$decision->needs_tools) {
                $message = $this->composer->compose($context, null, null);

                $totalTime = (microtime(true) - $startTime) * 1000;
                
                Log::info('Pipeline completed (no tools)', [
                    'pipeline_id' => $pipelineId,
                    'total_time_ms' => round($totalTime, 2),
                ]);

                return [
                    'message' => $message,
                    'correlation_id' => null,
                    'used_tools' => false,
                    'metadata' => [
                        'pipeline_id' => $pipelineId,
                        'needs_tools' => false,
                        'total_time_ms' => round($totalTime, 2),
                    ],
                ];
            }

            // Step 3: Select tools
            $plan = $this->toolSelector->selectTools($decision->high_level_goal, $context);

            if (!$plan->hasSteps()) {
                Log::warning('Tool plan has no steps', [
                    'pipeline_id' => $pipelineId,
                    'inputs_needed' => $plan->inputs_needed,
                ]);

                // Fall back to no-tool response
                $message = $this->composer->compose($context, null, null);

                $totalTime = (microtime(true) - $startTime) * 1000;

                return [
                    'message' => $message,
                    'correlation_id' => null,
                    'used_tools' => false,
                    'metadata' => [
                        'pipeline_id' => $pipelineId,
                        'needs_tools' => true,
                        'plan_empty' => true,
                        'inputs_needed' => $plan->inputs_needed,
                        'total_time_ms' => round($totalTime, 2),
                    ],
                ];
            }

            Log::info('Tool plan created', [
                'pipeline_id' => $pipelineId,
                'selected_tools' => $plan->selected_tool_ids,
                'step_count' => $plan->stepCount(),
            ]);

            // Step 4: Execute tools
            $trace = $this->toolRunner->execute($plan);

            Log::info('Tools executed', [
                'pipeline_id' => $pipelineId,
                'correlation_id' => $trace->correlation_id,
                'steps_executed' => count($trace->steps),
                'total_elapsed_ms' => $trace->total_elapsed_ms,
                'has_errors' => $trace->hasErrors(),
            ]);

            // Step 5: Summarize outcome
            $summary = $this->summarizer->summarize($trace);

            Log::info('Outcome summarized', [
                'pipeline_id' => $pipelineId,
                'correlation_id' => $trace->correlation_id,
                'confidence' => $summary->confidence,
                'key_facts' => count($summary->key_facts),
            ]);

            // Step 6: Compose final response
            $message = $this->composer->compose($context, $summary, $trace->correlation_id);

            $totalTime = (microtime(true) - $startTime) * 1000;

            Log::info('Pipeline completed (with tools)', [
                'pipeline_id' => $pipelineId,
                'correlation_id' => $trace->correlation_id,
                'total_time_ms' => round($totalTime, 2),
                'tool_time_ms' => round($trace->total_elapsed_ms, 2),
            ]);

            // Step 7: Audit logging
            $this->auditLog($pipelineId, $context, $decision, $plan, $trace, $summary);

            return [
                'message' => $message,
                'correlation_id' => $trace->correlation_id,
                'used_tools' => true,
                'metadata' => [
                    'pipeline_id' => $pipelineId,
                    'needs_tools' => true,
                    'tools_used' => $plan->selected_tool_ids,
                    'step_count' => count($trace->steps),
                    'confidence' => $summary->confidence,
                    'has_errors' => $trace->hasErrors(),
                    'total_time_ms' => round($totalTime, 2),
                    'tool_time_ms' => round($trace->total_elapsed_ms, 2),
                ],
            ];

        } catch (\Exception $e) {
            $totalTime = (microtime(true) - $startTime) * 1000;

            Log::error('Pipeline failed', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'total_time_ms' => round($totalTime, 2),
            ]);

            // Graceful degradation - return error message
            return [
                'message' => "I encountered an error while processing your request: {$e->getMessage()}",
                'correlation_id' => null,
                'used_tools' => false,
                'metadata' => [
                    'pipeline_id' => $pipelineId,
                    'error' => true,
                    'error_message' => $e->getMessage(),
                    'total_time_ms' => round($totalTime, 2),
                ],
            ];
        }
    }

    public function executeStreaming(?int $sessionId, string $userMessage): \Generator
    {
        $startTime = microtime(true);
        $pipelineId = \Illuminate\Support\Str::uuid()->toString();

        Log::info('Tool-aware streaming pipeline started', [
            'pipeline_id' => $pipelineId,
            'session_id' => $sessionId,
        ]);

        try {
            yield [
                'type' => 'pipeline_start',
                'pipeline_id' => $pipelineId,
            ];

            // Step 1: Assemble context
            $context = $this->contextBroker->assemble($sessionId, $userMessage);
            
            yield [
                'type' => 'context_assembled',
                'summary_length' => strlen($context->conversation_summary),
                'tool_count' => count($context->tool_registry_preview),
            ];

            // Step 2: Router decision
            $decision = $this->router->decide($context);

            yield [
                'type' => 'router_decision',
                'needs_tools' => $decision->needs_tools,
                'rationale' => $decision->rationale,
                'goal' => $decision->high_level_goal,
            ];

            // If no tools needed, compose direct response
            if (!$decision->needs_tools) {
                $message = $this->composer->compose($context, null, null);

                yield [
                    'type' => 'final_message',
                    'message' => $message,
                    'used_tools' => false,
                ];

                yield ['type' => 'done'];
                return;
            }

            // Step 3: Select tools
            $plan = $this->toolSelector->selectTools($decision->high_level_goal, $context);

            yield [
                'type' => 'tool_plan',
                'selected_tools' => $plan->selected_tool_ids,
                'step_count' => $plan->stepCount(),
            ];

            if (!$plan->hasSteps()) {
                $message = $this->composer->compose($context, null, null);

                yield [
                    'type' => 'final_message',
                    'message' => $message,
                    'used_tools' => false,
                ];

                yield ['type' => 'done'];
                return;
            }

            // Step 4: Execute tools (streaming)
            $trace = null;
            foreach ($this->toolRunner->executeStreaming($plan) as $event) {
                if ($event['type'] === 'execution_complete') {
                    $trace = new ExecutionTrace($event['correlation_id']);
                    foreach ($event['trace']['steps'] as $stepData) {
                        $trace->addStep(new \App\Services\Orchestration\ToolAware\DTOs\ToolResult(
                            tool_id: $stepData['tool_id'],
                            args: $stepData['args'],
                            result: $stepData['result'],
                            error: $stepData['error'],
                            elapsed_ms: $stepData['elapsed_ms'],
                            success: $stepData['success']
                        ));
                    }
                }
                
                yield $event;
            }

            // Step 5: Summarize outcome
            yield ['type' => 'summarizing'];
            
            $summary = $this->summarizer->summarize($trace);

            yield [
                'type' => 'summary',
                'summary' => $summary->toArray(),
            ];

            // Step 6: Compose final response
            yield ['type' => 'composing'];
            
            $message = $this->composer->compose($context, $summary, $trace->correlation_id);

            $totalTime = (microtime(true) - $startTime) * 1000;

            yield [
                'type' => 'final_message',
                'message' => $message,
                'used_tools' => true,
                'correlation_id' => $trace->correlation_id,
                'total_time_ms' => round($totalTime, 2),
            ];

            // Audit logging
            $this->auditLog($pipelineId, $context, $decision, $plan, $trace, $summary);

            yield ['type' => 'done'];

        } catch (\Exception $e) {
            Log::error('Streaming pipeline failed', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);

            yield [
                'type' => 'error',
                'error' => $e->getMessage(),
            ];

            yield ['type' => 'done'];
        }
    }

    /**
     * Audit log the entire pipeline execution
     */
    protected function auditLog(
        string $pipelineId,
        $context,
        $decision,
        $plan,
        ExecutionTrace $trace,
        OutcomeSummary $summary
    ): void {
        $auditEnabled = Config::get('fragments.tool_aware_turn.features.audit_enabled', true);

        if (!$auditEnabled) {
            return;
        }

        $auditData = [
            'pipeline_id' => $pipelineId,
            'correlation_id' => $trace->correlation_id,
            'timestamp' => now()->toISOString(),
            'context' => [
                'user_message' => $context->user_message,
                'conversation_summary_length' => strlen($context->conversation_summary),
                'tool_preview_count' => count($context->tool_registry_preview),
            ],
            'decision' => $decision->toArray(),
            'plan' => [
                'selected_tools' => $plan->selected_tool_ids,
                'step_count' => $plan->stepCount(),
                'inputs_needed' => $plan->inputs_needed,
            ],
            'trace' => $trace->toArray(),
            'summary' => $summary->toArray(),
        ];

        // Redact if enabled
        $redactEnabled = Config::get('fragments.tool_aware_turn.features.redact_logs', true);
        if ($redactEnabled) {
            $auditData = $this->redactAuditData($auditData);
        }

        Log::channel('daily')->info('Tool-aware pipeline audit', $auditData);
    }

    /**
     * Redact sensitive information from audit data
     */
    protected function redactAuditData(array $data): array
    {
        $sensitivePatterns = [
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', // emails
            '/\b[A-Z0-9]{20,}\b/', // API keys
            '/sk-[a-zA-Z0-9]{20,}/', // OpenAI keys
            '/Bearer\s+[a-zA-Z0-9._-]+/', // Bearer tokens
        ];

        $jsonData = json_encode($data);
        
        foreach ($sensitivePatterns as $pattern) {
            $jsonData = preg_replace($pattern, '[REDACTED]', $jsonData);
        }

        return json_decode($jsonData, true) ?? $data;
    }
}
