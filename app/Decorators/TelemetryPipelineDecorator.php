<?php

namespace App\Decorators;

use App\Models\Fragment;
use App\Services\Telemetry\CorrelationContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelemetryPipelineDecorator
{
    protected $action;

    protected string $stepName;

    protected array $context;

    public function __construct($action, ?string $stepName = null, array $context = [])
    {
        $this->action = $action;
        $this->stepName = $stepName ?? class_basename($action);
        $this->context = $context;
    }

    public function handle(Fragment $fragment, $next)
    {
        $decoratedFragment = $this->executeWithTelemetry($fragment, 'pipeline');

        return $next($decoratedFragment);
    }

    public function __invoke(Fragment $fragment): Fragment
    {
        return $this->executeWithTelemetry($fragment, 'direct');
    }

    protected function executeWithTelemetry(Fragment $fragment, string $executionMode): Fragment
    {
        $stepId = (string) Str::uuid();
        $startTime = microtime(true);

        $baseContext = [
            'step_id' => $stepId,
            'step_name' => $this->stepName,
            'fragment_id' => $fragment->id,
            'execution_mode' => $executionMode,
            'fragment_type' => $fragment->type,
            'vault' => $fragment->vault,
            'project_id' => $fragment->project_id,
        ];

        $logContext = array_merge($baseContext, $this->context);

        // Add correlation context if available
        if (CorrelationContext::hasContext()) {
            $logContext['correlation'] = CorrelationContext::forLogging();
        }

        $this->logStepStarted($logContext);

        try {
            // Execute the wrapped action
            $result = $executionMode === 'pipeline'
                ? $this->action->handle($fragment, fn ($f) => $f)
                : $this->action->__invoke($fragment);

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->logStepCompleted($logContext, $durationMs, $result);

            return $result;

        } catch (\Throwable $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->logStepFailed($logContext, $durationMs, $e);

            // Re-throw the exception to maintain pipeline behavior
            throw $e;
        }
    }

    protected function logStepStarted(array $context): void
    {
        Log::info('🔄 Fragment processing step started', array_merge($context, [
            'event' => 'fragment.processing.step.started',
            'timestamp' => now()->toISOString(),
        ]));
    }

    protected function logStepCompleted(array $context, float $durationMs, Fragment $result): void
    {
        $completionContext = array_merge($context, [
            'event' => 'fragment.processing.step.completed',
            'duration_ms' => $durationMs,
            'success' => true,
            'timestamp' => now()->toISOString(),
            'result_fragment_id' => $result->id,
            'fragment_changed' => $this->hasFragmentChanged($context['fragment_id'], $result),
        ]);

        // Add performance classification
        $completionContext['performance_tier'] = $this->classifyPerformance($durationMs);

        Log::info('✅ Fragment processing step completed', $completionContext);
    }

    protected function logStepFailed(array $context, float $durationMs, \Throwable $error): void
    {
        $errorContext = array_merge($context, [
            'event' => 'fragment.processing.step.failed',
            'duration_ms' => $durationMs,
            'success' => false,
            'timestamp' => now()->toISOString(),
            'error_type' => get_class($error),
            'error_message' => $error->getMessage(),
            'error_code' => $error->getCode(),
            'error_file' => $error->getFile(),
            'error_line' => $error->getLine(),
        ]);

        // Add performance classification even for failures
        $errorContext['performance_tier'] = $this->classifyPerformance($durationMs);

        Log::error('❌ Fragment processing step failed', $errorContext);
    }

    protected function hasFragmentChanged(string $originalId, Fragment $result): bool
    {
        // Basic check - fragment ID changed indicates new fragment created
        // Could be enhanced to check specific fields
        return $originalId !== $result->id;
    }

    protected function classifyPerformance(float $durationMs): string
    {
        return match (true) {
            $durationMs < 100 => 'fast',
            $durationMs < 500 => 'normal',
            $durationMs < 2000 => 'slow',
            default => 'very_slow'
        };
    }

    /**
     * Static factory method to wrap an action with telemetry
     */
    public static function wrap($action, ?string $stepName = null, array $context = []): self
    {
        return new static($action, $stepName, $context);
    }

    /**
     * Chain multiple decorated actions together
     */
    public static function chain(array $actions, array $globalContext = []): array
    {
        return array_map(function ($action) use ($globalContext) {
            if (is_array($action)) {
                [$actionInstance, $stepName, $stepContext] = $action;
                $context = array_merge($globalContext, $stepContext ?? []);

                return static::wrap($actionInstance, $stepName, $context);
            }

            return static::wrap($action, null, $globalContext);
        }, $actions);
    }
}
