<?php

namespace App\Services\Telemetry;

use App\Jobs\ProcessTelemetryBatch;
use App\Models\TelemetryCorrelationChain;
use App\Models\TelemetryEvent;
use App\Models\TelemetryHealthCheck;
use App\Models\TelemetryMetric;
use App\Models\TelemetryPerformanceSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class TelemetrySink
{
    private array $eventBuffer = [];

    private array $metricBuffer = [];

    private int $bufferSize;

    private bool $asyncProcessing;

    public function __construct()
    {
        $this->bufferSize = config('telemetry.performance.buffer_size', 1000);
        $this->asyncProcessing = config('telemetry.performance.async_processing', true);
    }

    /**
     * Check current async processing setting (can change at runtime)
     */
    private function isAsyncProcessing(): bool
    {
        return config('telemetry.performance.async_processing', $this->asyncProcessing);
    }

    /**
     * Store a telemetry event in the unified sink
     */
    public function storeEvent(array $eventData): ?int
    {
        if (! config('telemetry.enabled', true)) {
            return null;
        }

        $normalizedEvent = $this->normalizeEventData($eventData);

        if ($this->isAsyncProcessing()) {
            $this->bufferEvent($normalizedEvent);
        } else {
            return $this->persistEvent($normalizedEvent);
        }

        return null;
    }

    /**
     * Store multiple events in batch
     */
    public function storeEvents(array $events): array
    {
        if (! config('telemetry.enabled', true)) {
            return [];
        }

        $results = [];
        foreach ($events as $eventData) {
            $results[] = $this->storeEvent($eventData);
        }

        return $results;
    }

    /**
     * Store a telemetry metric
     */
    public function storeMetric(string $name, float $value, array $labels = [], string $type = 'gauge'): ?int
    {
        if (! config('telemetry.enabled', true)) {
            return null;
        }

        $metricData = [
            'metric_name' => $name,
            'component' => $labels['component'] ?? 'unknown',
            'metric_type' => $type,
            'value' => $value,
            'labels' => $labels,
            'timestamp' => now(),
            'aggregation_period' => 'raw',
        ];

        if ($this->isAsyncProcessing()) {
            $this->bufferMetric($metricData);
        } else {
            return $this->persistMetric($metricData);
        }

        return null;
    }

    /**
     * Store a health check result
     */
    public function storeHealthCheck(string $component, string $checkName, bool $isHealthy, ?string $errorMessage = null, ?float $responseTime = null, array $metadata = []): int
    {
        return TelemetryHealthCheck::create([
            'component' => $component,
            'check_name' => $checkName,
            'is_healthy' => $isHealthy,
            'error_message' => $errorMessage,
            'response_time_ms' => $responseTime,
            'check_metadata' => $metadata,
            'checked_at' => now(),
        ])->id;
    }

    /**
     * Store a performance snapshot
     */
    public function storePerformanceSnapshot(string $component, string $operation, float $durationMs, array $resourceMetrics = []): int
    {
        $performanceClass = $this->classifyPerformance($component, $durationMs);

        return TelemetryPerformanceSnapshot::create([
            'component' => $component,
            'operation' => $operation,
            'duration_ms' => $durationMs,
            'memory_usage_bytes' => $resourceMetrics['memory_usage'] ?? null,
            'cpu_usage_percent' => $resourceMetrics['cpu_usage'] ?? null,
            'resource_metrics' => $resourceMetrics,
            'performance_class' => $performanceClass,
            'recorded_at' => now(),
        ])->id;
    }

    /**
     * Create or update a correlation chain
     */
    public function updateCorrelationChain(string $correlationId, array $chainData = []): void
    {
        $chainId = $chainData['chain_id'] ?? $correlationId;

        TelemetryCorrelationChain::updateOrCreate(
            ['chain_id' => $chainId],
            [
                'root_correlation_id' => $chainData['root_correlation_id'] ?? $correlationId,
                'depth' => $chainData['depth'] ?? 1,
                'started_at' => $chainData['started_at'] ?? now(),
                'completed_at' => $chainData['completed_at'] ?? null,
                'total_events' => $chainData['total_events'] ?? 1,
                'chain_metadata' => $chainData['metadata'] ?? [],
                'status' => $chainData['status'] ?? 'active',
            ]
        );
    }

    /**
     * Flush buffered events and metrics
     */
    public function flush(): void
    {
        if (! empty($this->eventBuffer)) {
            if ($this->isAsyncProcessing()) {
                Queue::push(new ProcessTelemetryBatch('events', $this->eventBuffer));
            } else {
                $this->persistEvents($this->eventBuffer);
            }
            $this->eventBuffer = [];
        }

        if (! empty($this->metricBuffer)) {
            if ($this->isAsyncProcessing()) {
                Queue::push(new ProcessTelemetryBatch('metrics', $this->metricBuffer));
            } else {
                $this->persistMetrics($this->metricBuffer);
            }
            $this->metricBuffer = [];
        }
    }

    /**
     * Normalize incoming event data to standard format
     */
    private function normalizeEventData(array $eventData): array
    {
        return [
            'correlation_id' => $eventData['correlation_id'] ?? $this->generateCorrelationId(),
            'event_type' => $eventData['event_type'] ?? 'unknown',
            'event_name' => $eventData['event_name'] ?? 'event',
            'timestamp' => $eventData['timestamp'] ?? now(),
            'component' => $eventData['component'] ?? 'unknown',
            'operation' => $eventData['operation'] ?? null,
            'metadata' => $this->sanitizeMetadata($eventData['metadata'] ?? []),
            'context' => $this->sanitizeContext($eventData['context'] ?? []),
            'performance' => $eventData['performance'] ?? [],
            'message' => $eventData['message'] ?? null,
            'level' => $eventData['level'] ?? 'info',
        ];
    }

    /**
     * Buffer an event for batch processing
     */
    private function bufferEvent(array $eventData): void
    {
        $this->eventBuffer[] = $eventData;

        if (count($this->eventBuffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    /**
     * Buffer a metric for batch processing
     */
    private function bufferMetric(array $metricData): void
    {
        $this->metricBuffer[] = $metricData;

        if (count($this->metricBuffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    /**
     * Persist a single event
     */
    private function persistEvent(array $eventData): int
    {
        try {
            return TelemetryEvent::create($eventData)->id;
        } catch (\Exception $e) {
            Log::error('Failed to persist telemetry event', [
                'error' => $e->getMessage(),
                'event_data' => $eventData,
            ]);
            throw $e;
        }
    }

    /**
     * Persist multiple events in batch
     */
    private function persistEvents(array $events): array
    {
        try {
            $results = [];
            DB::transaction(function () use ($events, &$results) {
                foreach ($events as $eventData) {
                    $results[] = TelemetryEvent::create($eventData)->id;
                }
            });

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to persist telemetry events batch', [
                'error' => $e->getMessage(),
                'event_count' => count($events),
            ]);
            throw $e;
        }
    }

    /**
     * Persist a single metric
     */
    private function persistMetric(array $metricData): int
    {
        try {
            return TelemetryMetric::create($metricData)->id;
        } catch (\Exception $e) {
            Log::error('Failed to persist telemetry metric', [
                'error' => $e->getMessage(),
                'metric_data' => $metricData,
            ]);
            throw $e;
        }
    }

    /**
     * Persist multiple metrics in batch
     */
    private function persistMetrics(array $metrics): array
    {
        try {
            $results = [];
            DB::transaction(function () use ($metrics, &$results) {
                foreach ($metrics as $metricData) {
                    $results[] = TelemetryMetric::create($metricData)->id;
                }
            });

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to persist telemetry metrics batch', [
                'error' => $e->getMessage(),
                'metric_count' => count($metrics),
            ]);
            throw $e;
        }
    }

    /**
     * Sanitize metadata to remove sensitive information
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $sensitiveFields = config('telemetry.security.sensitive_fields', []);
        $maxLength = config('telemetry.security.max_field_length', 1000);

        foreach ($metadata as $key => $value) {
            // Check for sensitive field names
            foreach ($sensitiveFields as $sensitiveField) {
                if (stripos($key, $sensitiveField) !== false) {
                    $metadata[$key] = config('telemetry.security.hash_long_values', true)
                        ? hash('sha256', (string) $value)
                        : '[REDACTED]';

                    continue 2;
                }
            }

            // Truncate long values
            if (is_string($value) && strlen($value) > $maxLength) {
                $metadata[$key] = config('telemetry.security.hash_long_values', true)
                    ? hash('sha256', $value)
                    : substr($value, 0, $maxLength).'...[TRUNCATED]';
            }
        }

        return $metadata;
    }

    /**
     * Sanitize context information
     */
    private function sanitizeContext(array $context): array
    {
        if (config('telemetry.security.anonymize_user_data', false)) {
            unset($context['user_id'], $context['user_email'], $context['ip_address']);
        }

        return $this->sanitizeMetadata($context);
    }

    /**
     * Classify performance based on component thresholds
     */
    private function classifyPerformance(string $component, float $durationMs): string
    {
        $thresholds = config("telemetry.components.{$component}.performance_thresholds", [
            'fast' => 100,
            'normal' => 500,
            'slow' => 2000,
        ]);

        if ($durationMs <= $thresholds['fast']) {
            return 'fast';
        } elseif ($durationMs <= $thresholds['normal']) {
            return 'normal';
        } elseif ($durationMs <= $thresholds['slow']) {
            return 'slow';
        } else {
            return 'critical';
        }
    }

    /**
     * Generate a correlation ID if none provided
     */
    private function generateCorrelationId(): string
    {
        return uniqid('tel_', true);
    }

    /**
     * Get buffer status for monitoring
     */
    public function getBufferStatus(): array
    {
        return [
            'event_buffer_size' => count($this->eventBuffer),
            'metric_buffer_size' => count($this->metricBuffer),
            'max_buffer_size' => $this->bufferSize,
            'async_processing' => $this->isAsyncProcessing(),
        ];
    }
}
