<?php

namespace App\Services\Telemetry;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FragmentProcessingTelemetry
{
    private const LOG_CHANNEL = 'fragment-processing-telemetry';

    /**
     * Log start of complete fragment processing pipeline
     */
    public static function logPipelineStarted(Fragment $fragment, array $steps): string
    {
        $pipelineId = (string) Str::uuid();

        self::log('fragment.processing.pipeline.started', [
            'pipeline_id' => $pipelineId,
            'fragment_id' => $fragment->id,
            'fragment_type' => $fragment->type,
            'vault' => $fragment->vault,
            'project_id' => $fragment->project_id,
            'steps' => array_map(fn ($step) => class_basename($step), $steps),
            'step_count' => count($steps),
            'content_length' => strlen($fragment->message ?? ''),
            'has_tags' => ! empty($fragment->tags),
            'tag_count' => count($fragment->tags ?? []),
        ]);

        return $pipelineId;
    }

    /**
     * Log completion of fragment processing pipeline
     */
    public static function logPipelineCompleted(string $pipelineId, Fragment $fragment, float $durationMs, array $stepMetrics = []): void
    {
        self::log('fragment.processing.pipeline.completed', [
            'pipeline_id' => $pipelineId,
            'fragment_id' => $fragment->id,
            'duration_ms' => round($durationMs, 2),
            'performance_tier' => self::classifyPipelinePerformance($durationMs),
            'step_count' => count($stepMetrics),
            'total_step_duration_ms' => array_sum(array_column($stepMetrics, 'duration_ms')),
            'avg_step_duration_ms' => count($stepMetrics) > 0 ? round(array_sum(array_column($stepMetrics, 'duration_ms')) / count($stepMetrics), 2) : 0,
            'slowest_step' => self::findSlowestStep($stepMetrics),
            'failed_steps' => array_values(array_filter($stepMetrics, fn ($step) => ! $step['success'])),
            'final_fragment_type' => $fragment->type,
            'final_tag_count' => count($fragment->tags ?? []),
            'enrichment_applied' => ! empty($fragment->metadata['enrichment'] ?? null),
        ]);
    }

    /**
     * Log pipeline failure
     */
    public static function logPipelineFailed(string $pipelineId, Fragment $fragment, \Throwable $error, float $durationMs, array $stepMetrics = []): void
    {
        self::log('fragment.processing.pipeline.failed', [
            'pipeline_id' => $pipelineId,
            'fragment_id' => $fragment->id,
            'duration_ms' => round($durationMs, 2),
            'error_type' => get_class($error),
            'error_message' => $error->getMessage(),
            'error_code' => $error->getCode(),
            'completed_steps' => count($stepMetrics),
            'failed_at_step' => self::findFailedStep($stepMetrics),
            'partial_completion_rate' => count($stepMetrics) > 0 ? round(count(array_filter($stepMetrics, fn ($step) => $step['success'])) / count($stepMetrics) * 100, 2) : 0,
        ], 'error');
    }

    /**
     * Log individual step execution
     */
    public static function logStepExecution(string $pipelineId, string $stepName, Fragment $fragment, float $durationMs, bool $success, ?\Throwable $error = null, array $additionalContext = []): void
    {
        $baseData = [
            'pipeline_id' => $pipelineId,
            'step_name' => $stepName,
            'fragment_id' => $fragment->id,
            'duration_ms' => round($durationMs, 2),
            'success' => $success,
            'performance_tier' => self::classifyStepPerformance($durationMs),
        ];

        $data = array_merge($baseData, $additionalContext);

        if (! $success && $error) {
            $data = array_merge($data, [
                'error_type' => get_class($error),
                'error_message' => $error->getMessage(),
                'error_code' => $error->getCode(),
            ]);
        }

        $level = $success ? 'info' : 'error';
        $event = $success ? 'fragment.processing.step.completed' : 'fragment.processing.step.failed';

        self::log($event, $data, $level);
    }

    /**
     * Log fragment state changes during processing
     */
    public static function logFragmentStateChange(string $pipelineId, string $stepName, Fragment $beforeFragment, Fragment $afterFragment): void
    {
        $changes = self::detectFragmentChanges($beforeFragment, $afterFragment);

        if (empty($changes)) {
            return; // No changes to log
        }

        self::log('fragment.processing.state.changed', [
            'pipeline_id' => $pipelineId,
            'step_name' => $stepName,
            'fragment_id' => $afterFragment->id,
            'changes' => $changes,
            'change_count' => count($changes),
        ]);
    }

    /**
     * Log performance bottlenecks and alerts
     */
    public static function logPerformanceAlert(string $pipelineId, string $alertType, array $data): void
    {
        self::log('fragment.processing.performance.alert', [
            'pipeline_id' => $pipelineId,
            'alert_type' => $alertType,
            'alert_data' => $data,
        ], 'warning');
    }

    /**
     * Log correlation between fragments in processing chain
     */
    public static function logFragmentCorrelation(array $fragmentIds, string $correlationType, array $metadata = []): void
    {
        self::log('fragment.processing.correlation', [
            'fragment_ids' => $fragmentIds,
            'correlation_type' => $correlationType,
            'fragment_count' => count($fragmentIds),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Generate processing summary for analytics
     */
    public static function logProcessingSummary(array $pipelineMetrics): void
    {
        $summary = [
            'total_pipelines' => count($pipelineMetrics),
            'successful_pipelines' => count(array_filter($pipelineMetrics, fn ($p) => $p['success'])),
            'failed_pipelines' => count(array_filter($pipelineMetrics, fn ($p) => ! $p['success'])),
            'avg_pipeline_duration_ms' => round(array_sum(array_column($pipelineMetrics, 'duration_ms')) / count($pipelineMetrics), 2),
            'slowest_pipeline_ms' => max(array_column($pipelineMetrics, 'duration_ms')),
            'fastest_pipeline_ms' => min(array_column($pipelineMetrics, 'duration_ms')),
            'most_common_failure_step' => self::findMostCommonFailureStep($pipelineMetrics),
            'performance_distribution' => self::calculatePerformanceDistribution($pipelineMetrics),
        ];

        self::log('fragment.processing.summary', $summary);
    }

    /**
     * Detect changes between fragment states
     */
    private static function detectFragmentChanges(Fragment $before, Fragment $after): array
    {
        $changes = [];

        $fieldsToTrack = ['type', 'message', 'title', 'tags', 'metadata', 'vault', 'model_provider', 'model_name'];

        foreach ($fieldsToTrack as $field) {
            $beforeValue = $before->{$field};
            $afterValue = $after->{$field};

            if ($beforeValue !== $afterValue) {
                $changes[$field] = [
                    'before' => is_array($beforeValue) ? count($beforeValue) : (strlen(strval($beforeValue)) > 100 ? '[truncated]' : $beforeValue),
                    'after' => is_array($afterValue) ? count($afterValue) : (strlen(strval($afterValue)) > 100 ? '[truncated]' : $afterValue),
                ];
            }
        }

        return $changes;
    }

    /**
     * Find the slowest step in metrics
     */
    private static function findSlowestStep(array $stepMetrics): ?array
    {
        if (empty($stepMetrics)) {
            return null;
        }

        $slowest = array_reduce($stepMetrics, function ($carry, $step) {
            return ($carry === null || $step['duration_ms'] > $carry['duration_ms']) ? $step : $carry;
        });

        return $slowest ? [
            'step_name' => $slowest['step_name'],
            'duration_ms' => $slowest['duration_ms'],
        ] : null;
    }

    /**
     * Find which step failed
     */
    private static function findFailedStep(array $stepMetrics): ?string
    {
        foreach ($stepMetrics as $step) {
            if (! $step['success']) {
                return $step['step_name'];
            }
        }

        return null;
    }

    /**
     * Find most common failure step across pipelines
     */
    private static function findMostCommonFailureStep(array $pipelineMetrics): ?string
    {
        $failureCounts = [];

        foreach ($pipelineMetrics as $pipeline) {
            if (! $pipeline['success'] && ! empty($pipeline['failed_at_step'])) {
                $step = $pipeline['failed_at_step'];
                $failureCounts[$step] = ($failureCounts[$step] ?? 0) + 1;
            }
        }

        if (empty($failureCounts)) {
            return null;
        }

        return array_keys($failureCounts, max($failureCounts))[0];
    }

    /**
     * Calculate performance distribution
     */
    private static function calculatePerformanceDistribution(array $pipelineMetrics): array
    {
        $distribution = ['fast' => 0, 'normal' => 0, 'slow' => 0, 'very_slow' => 0];

        foreach ($pipelineMetrics as $pipeline) {
            $tier = self::classifyPipelinePerformance($pipeline['duration_ms']);
            $distribution[$tier]++;
        }

        return $distribution;
    }

    /**
     * Classify pipeline performance
     */
    private static function classifyPipelinePerformance(float $durationMs): string
    {
        return match (true) {
            $durationMs < 1000 => 'fast',      // < 1s
            $durationMs < 5000 => 'normal',    // 1-5s
            $durationMs < 15000 => 'slow',     // 5-15s
            default => 'very_slow'             // > 15s
        };
    }

    /**
     * Classify individual step performance
     */
    private static function classifyStepPerformance(float $durationMs): string
    {
        return match (true) {
            $durationMs < 100 => 'fast',       // < 100ms
            $durationMs < 500 => 'normal',     // 100-500ms
            $durationMs < 2000 => 'slow',      // 500ms-2s
            default => 'very_slow'             // > 2s
        };
    }

    /**
     * Core logging method with structured data
     */
    private static function log(string $event, array $data, string $level = 'info'): void
    {
        $logData = [
            'event' => $event,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'event_id' => (string) Str::uuid(),
                'service' => 'fragment-processing-pipeline',
                'version' => '1.0',
            ],
        ];

        // Add correlation context if available
        if (CorrelationContext::hasContext()) {
            $logData['correlation'] = CorrelationContext::forLogging();
        }

        // Use appropriate log level
        match ($level) {
            'error' => Log::channel(self::LOG_CHANNEL)->error($event, $logData),
            'warning' => Log::channel(self::LOG_CHANNEL)->warning($event, $logData),
            'debug' => Log::channel(self::LOG_CHANNEL)->debug($event, $logData),
            default => Log::channel(self::LOG_CHANNEL)->info($event, $logData),
        };
    }
}
