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
     * Execute the pipeline with streaming output
     *
     * @param  int|null  $sessionId  Chat session ID
     * @param  string  $userMessage  User's message
     * @param  string|null  $conversationId  Conversation ID for approval requests
     * @param  string|null  $messageId  Message ID for approval requests
     * @return \Generator Yields events: router_decision, tool_plan, tool_result, outcome_summary, final_message
     */
    public function executeStreaming(?int $sessionId, string $userMessage, ?string $conversationId = null, ?string $messageId = null): \Generator
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
            if (! $decision->needs_tools) {
                $message = $this->composer->compose($context, null, null);

                // Get the actual model used (from session or config)
                $composerModel = $context->agent_prefs['model_name'] ?? Config::get('fragments.tool_aware_turn.models.composer', 'gpt-4o');
                $composerProvider = $this->getProviderForModel($composerModel);

                yield [
                    'type' => 'final_message',
                    'message' => $message,
                    'used_tools' => false,
                    'ai_provider' => $composerProvider,
                    'ai_model' => $composerModel,
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

            if (! $plan->hasSteps()) {
                $message = $this->composer->compose($context, null, null);

                // Get the actual model used (from session or config)
                $composerModel = $context->agent_prefs['model_name'] ?? Config::get('fragments.tool_aware_turn.models.composer', 'gpt-4o');
                $composerProvider = $this->getProviderForModel($composerModel);

                yield [
                    'type' => 'final_message',
                    'message' => $message,
                    'used_tools' => false,
                    'ai_provider' => $composerProvider,
                    'ai_model' => $composerModel,
                ];

                yield ['type' => 'done'];

                return;
            }

            // Step 4: Execute tools (streaming)
            $trace = null;
            foreach ($this->toolRunner->executeStreaming($plan, $sessionId, $conversationId, $messageId) as $event) {
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

            $summary = $this->summarizer->summarize($trace, $context);

            yield [
                'type' => 'summary',
                'summary' => $summary->toArray(),
            ];

            // Step 6: Compose final response
            yield ['type' => 'composing'];

            $message = $this->composer->compose($context, $summary, $trace->correlation_id);

            $totalTime = (microtime(true) - $startTime) * 1000;

            // Get the actual model used (from session or config)
            $composerModel = $context->agent_prefs['model_name'] ?? Config::get('fragments.tool_aware_turn.models.composer', 'gpt-4o');
            $composerProvider = $this->getProviderForModel($composerModel);

            yield [
                'type' => 'final_message',
                'message' => $message,
                'used_tools' => true,
                'correlation_id' => $trace->correlation_id,
                'total_time_ms' => round($totalTime, 2),
                'ai_provider' => $composerProvider,
                'ai_model' => $composerModel,
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

        if (! $auditEnabled) {
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

    /**
     * Determine provider from model name
     */
    protected function getProviderForModel(string $model): string
    {
        if (str_starts_with($model, 'gpt-') || str_starts_with($model, 'o1-')) {
            return 'openai';
        }
        if (str_starts_with($model, 'claude-')) {
            return 'anthropic';
        }
        if (str_contains($model, '/')) {
            return explode('/', $model)[0];
        }
        
        return Config::get('fragments.models.default_provider', 'openai');
    }
}
