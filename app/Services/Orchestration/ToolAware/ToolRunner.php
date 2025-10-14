<?php

namespace App\Services\Orchestration\ToolAware;

use App\Services\Orchestration\ToolAware\Contracts\ToolRunnerInterface;
use App\Services\Orchestration\ToolAware\DTOs\ExecutionTrace;
use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;
use App\Services\Orchestration\ToolAware\DTOs\ToolResult;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ToolRunner implements ToolRunnerInterface
{
    public function __construct(
        protected ToolRegistry $toolRegistry
    ) {}

    public function execute(ToolPlan $plan, ?int $sessionId = null, ?string $conversationId = null, ?string $messageId = null): ExecutionTrace
    {
        $trace = new ExecutionTrace;
        $maxSteps = Config::get('fragments.tool_aware_turn.limits.max_steps_per_turn', 10);

        Log::info('Starting tool execution', [
            'correlation_id' => $trace->correlation_id,
            'plan_steps' => count($plan->plan_steps),
            'session_id' => $sessionId,
        ]);

        $stepCount = 0;
        foreach ($plan->plan_steps as $step) {
            if ($stepCount >= $maxSteps) {
                Log::warning('Max step limit reached', [
                    'correlation_id' => $trace->correlation_id,
                    'limit' => $maxSteps,
                ]);
                break;
            }

            $toolId = $step['tool_id'] ?? null;
            $args = $step['args'] ?? [];

            if (! $toolId) {
                Log::error('Tool step missing tool_id', ['step' => $step]);

                continue;
            }

            $result = $this->executeSingleTool($toolId, $args, $trace->correlation_id, $sessionId, $conversationId, $messageId);
            $trace->addStep($result);

            $stepCount++;
        }

        Log::info('Tool execution completed', [
            'correlation_id' => $trace->correlation_id,
            'steps_executed' => $stepCount,
            'total_time_ms' => $trace->total_elapsed_ms,
            'has_errors' => $trace->hasErrors(),
        ]);

        return $trace;
    }

    public function executeStreaming(ToolPlan $plan, ?int $sessionId = null, ?string $conversationId = null, ?string $messageId = null): \Generator
    {
        $trace = new ExecutionTrace;
        $maxSteps = Config::get('fragments.tool_aware_turn.limits.max_steps_per_turn', 10);

        Log::info('Starting streaming tool execution', [
            'correlation_id' => $trace->correlation_id,
            'plan_steps' => count($plan->plan_steps),
            'session_id' => $sessionId,
        ]);

        $stepCount = 0;
        foreach ($plan->plan_steps as $step) {
            if ($stepCount >= $maxSteps) {
                Log::warning('Max step limit reached', [
                    'correlation_id' => $trace->correlation_id,
                    'limit' => $maxSteps,
                ]);
                break;
            }

            $toolId = $step['tool_id'] ?? null;
            $args = $step['args'] ?? [];

            if (! $toolId) {
                Log::error('Tool step missing tool_id', ['step' => $step]);

                continue;
            }

            $result = $this->executeSingleTool($toolId, $args, $trace->correlation_id, $sessionId, $conversationId, $messageId);
            $trace->addStep($result);

            yield [
                'type' => 'tool_result',
                'correlation_id' => $trace->correlation_id,
                'step_index' => $stepCount,
                'tool_id' => $toolId,
                'result' => $result->toArray(),
            ];

            $stepCount++;
        }

        Log::info('Streaming tool execution completed', [
            'correlation_id' => $trace->correlation_id,
            'steps_executed' => $stepCount,
            'total_time_ms' => $trace->total_elapsed_ms,
        ]);

        yield [
            'type' => 'execution_complete',
            'correlation_id' => $trace->correlation_id,
            'trace' => $trace->toArray(),
        ];
    }

    protected function executeSingleTool(string $toolId, array $args, string $correlationId, ?int $sessionId = null, ?string $conversationId = null, ?string $messageId = null): ToolResult
    {
        $startTime = microtime(true);

        try {
            if (! $this->toolRegistry->exists($toolId)) {
                throw new \RuntimeException("Tool not found: {$toolId}");
            }

            $tool = $this->toolRegistry->get($toolId);

            if (! $tool->isEnabled()) {
                throw new \RuntimeException("Tool is disabled: {$toolId}");
            }

            Log::info('Executing tool', [
                'correlation_id' => $correlationId,
                'tool_id' => $toolId,
                'args' => $args,
                'session_id' => $sessionId,
            ]);

            $context = ['correlation_id' => $correlationId];
            if ($sessionId !== null) {
                $context['session_id'] = $sessionId;
            }
            if ($conversationId !== null) {
                $context['conversation_id'] = $conversationId;
            }
            if ($messageId !== null) {
                $context['message_id'] = $messageId;
            }

            $response = $tool->call($args, $context);

            $elapsedMs = (microtime(true) - $startTime) * 1000;

            $success = $response['success'] ?? false;
            $result = $response['result'] ?? $response;
            $error = $response['error'] ?? null;

            Log::info('Tool execution result', [
                'correlation_id' => $correlationId,
                'tool_id' => $toolId,
                'success' => $success,
                'elapsed_ms' => round($elapsedMs, 2),
            ]);

            return new ToolResult(
                tool_id: $toolId,
                args: $args,
                result: $result,
                error: $error,
                elapsed_ms: $elapsedMs,
                success: $success
            );

        } catch (\Exception $e) {
            $elapsedMs = (microtime(true) - $startTime) * 1000;

            Log::error('Tool execution failed', [
                'correlation_id' => $correlationId,
                'tool_id' => $toolId,
                'error' => $e->getMessage(),
                'elapsed_ms' => round($elapsedMs, 2),
            ]);

            return new ToolResult(
                tool_id: $toolId,
                args: $args,
                result: null,
                error: $e->getMessage(),
                elapsed_ms: $elapsedMs,
                success: false
            );
        }
    }
}
