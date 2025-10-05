<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;

class TelemetryAdapter
{
    protected TelemetrySink $sink;

    protected array $context = [];

    public function __construct(TelemetrySink $sink)
    {
        $this->sink = $sink;
        $this->context = $this->buildBaseContext();
    }

    /**
     * Adapt tool telemetry event to unified format
     */
    public function adaptToolEvent(string $eventName, array $data): void
    {
        $eventData = [
            'correlation_id' => $data['correlation_id'] ?? $this->generateCorrelationId(),
            'event_type' => 'tool',
            'event_name' => $eventName,
            'timestamp' => now(),
            'component' => $data['tool_name'] ?? 'unknown_tool',
            'operation' => $data['operation'] ?? null,
            'metadata' => [
                'tool_name' => $data['tool_name'] ?? null,
                'tool_type' => $data['tool_type'] ?? null,
                'parameters' => $data['parameters'] ?? [],
                'result' => $data['result'] ?? null,
                'invocation_id' => $data['invocation_id'] ?? null,
            ],
            'context' => array_merge($this->context, $data['context'] ?? []),
            'performance' => $data['performance'] ?? [],
            'message' => $data['message'] ?? $this->generateEventMessage($eventName, $data),
            'level' => $this->determineLogLevel($eventName, $data),
        ];

        $this->sink->storeEvent($eventData);

        // Store performance metrics if available
        if (isset($data['performance']['duration_ms'])) {
            $this->sink->storeMetric(
                'tool.execution_duration_ms',
                $data['performance']['duration_ms'],
                [
                    'component' => 'tool_telemetry',
                    'tool_name' => $data['tool_name'] ?? 'unknown',
                    'tool_type' => $data['tool_type'] ?? 'unknown',
                ],
                'histogram'
            );
        }
    }

    /**
     * Adapt command telemetry event to unified format
     */
    public function adaptCommandEvent(string $eventName, array $data): void
    {
        $eventData = [
            'correlation_id' => $data['correlation_id'] ?? $this->generateCorrelationId(),
            'event_type' => 'command',
            'event_name' => $eventName,
            'timestamp' => now(),
            'component' => $data['command_name'] ?? 'unknown_command',
            'operation' => $data['step_name'] ?? $data['operation'] ?? null,
            'metadata' => [
                'command_name' => $data['command_name'] ?? null,
                'command_type' => $data['command_type'] ?? null,
                'step_name' => $data['step_name'] ?? null,
                'step_type' => $data['step_type'] ?? null,
                'template_data' => $data['template_data'] ?? [],
                'arguments' => $data['arguments'] ?? [],
                'result' => $data['result'] ?? null,
            ],
            'context' => array_merge($this->context, $data['context'] ?? []),
            'performance' => $data['performance'] ?? [],
            'message' => $data['message'] ?? $this->generateEventMessage($eventName, $data),
            'level' => $this->determineLogLevel($eventName, $data),
        ];

        $this->sink->storeEvent($eventData);

        // Store command execution metrics
        if (isset($data['performance']['duration_ms'])) {
            $this->sink->storeMetric(
                'command.execution_duration_ms',
                $data['performance']['duration_ms'],
                [
                    'component' => 'command_telemetry',
                    'command_name' => $data['command_name'] ?? 'unknown',
                    'step_type' => $data['step_type'] ?? 'unknown',
                ],
                'histogram'
            );
        }
    }

    /**
     * Adapt fragment telemetry event to unified format
     */
    public function adaptFragmentEvent(string $eventName, array $data): void
    {
        $eventData = [
            'correlation_id' => $data['correlation_id'] ?? $this->generateCorrelationId(),
            'event_type' => 'fragment',
            'event_name' => $eventName,
            'timestamp' => now(),
            'component' => $data['step_name'] ?? 'fragment_processor',
            'operation' => $data['pipeline_step'] ?? $data['operation'] ?? null,
            'metadata' => [
                'fragment_id' => $data['fragment_id'] ?? null,
                'pipeline_name' => $data['pipeline_name'] ?? null,
                'step_name' => $data['step_name'] ?? null,
                'step_type' => $data['step_type'] ?? null,
                'fragment_type' => $data['fragment_type'] ?? null,
                'pipeline_stage' => $data['pipeline_stage'] ?? null,
                'result' => $data['result'] ?? null,
            ],
            'context' => array_merge($this->context, $data['context'] ?? []),
            'performance' => $data['performance'] ?? [],
            'message' => $data['message'] ?? $this->generateEventMessage($eventName, $data),
            'level' => $this->determineLogLevel($eventName, $data),
        ];

        $this->sink->storeEvent($eventData);

        // Store fragment processing metrics
        if (isset($data['performance']['duration_ms'])) {
            $this->sink->storeMetric(
                'fragment.processing_duration_ms',
                $data['performance']['duration_ms'],
                [
                    'component' => 'fragment_telemetry',
                    'step_name' => $data['step_name'] ?? 'unknown',
                    'pipeline_name' => $data['pipeline_name'] ?? 'unknown',
                ],
                'histogram'
            );
        }
    }

    /**
     * Adapt chat telemetry event to unified format
     */
    public function adaptChatEvent(string $eventName, array $data): void
    {
        $eventData = [
            'correlation_id' => $data['correlation_id'] ?? $this->generateCorrelationId(),
            'event_type' => 'chat',
            'event_name' => $eventName,
            'timestamp' => now(),
            'component' => 'chat_system',
            'operation' => $data['operation'] ?? null,
            'metadata' => [
                'session_id' => $data['session_id'] ?? null,
                'message_id' => $data['message_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'ai_provider' => $data['ai_provider'] ?? null,
                'model_name' => $data['model_name'] ?? null,
                'tokens_used' => $data['tokens_used'] ?? null,
                'response_quality' => $data['response_quality'] ?? null,
            ],
            'context' => array_merge($this->context, $data['context'] ?? []),
            'performance' => $data['performance'] ?? [],
            'message' => $data['message'] ?? $this->generateEventMessage($eventName, $data),
            'level' => $this->determineLogLevel($eventName, $data),
        ];

        $this->sink->storeEvent($eventData);

        // Store chat metrics
        if (isset($data['tokens_used'])) {
            $this->sink->storeMetric(
                'chat.tokens_used',
                $data['tokens_used'],
                [
                    'component' => 'chat_telemetry',
                    'ai_provider' => $data['ai_provider'] ?? 'unknown',
                    'model_name' => $data['model_name'] ?? 'unknown',
                ],
                'counter'
            );
        }

        if (isset($data['performance']['response_time_ms'])) {
            $this->sink->storeMetric(
                'chat.response_time_ms',
                $data['performance']['response_time_ms'],
                [
                    'component' => 'chat_telemetry',
                    'ai_provider' => $data['ai_provider'] ?? 'unknown',
                ],
                'histogram'
            );
        }
    }

    /**
     * Record a health check result
     */
    public function recordHealthCheck(string $component, string $checkName, bool $isHealthy, ?string $errorMessage = null, ?float $responseTime = null, array $metadata = []): void
    {
        $this->sink->storeHealthCheck($component, $checkName, $isHealthy, $errorMessage, $responseTime, $metadata);
    }

    /**
     * Record a performance snapshot
     */
    public function recordPerformanceSnapshot(string $component, string $operation, float $durationMs, array $resourceMetrics = []): void
    {
        $this->sink->storePerformanceSnapshot($component, $operation, $durationMs, $resourceMetrics);
    }

    /**
     * Update correlation chain information
     */
    public function updateCorrelationChain(string $correlationId, array $chainData = []): void
    {
        $this->sink->updateCorrelationChain($correlationId, $chainData);
    }

    /**
     * Build base context for all events
     */
    protected function buildBaseContext(): array
    {
        return [
            'environment' => app()->environment(),
            'app_version' => config('app.version', '1.0.0'),
            'server_name' => gethostname(),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate correlation ID if not provided
     */
    protected function generateCorrelationId(): string
    {
        return uniqid('corr_', true);
    }

    /**
     * Generate a human-readable event message
     */
    protected function generateEventMessage(string $eventName, array $data): string
    {
        $component = $data['tool_name'] ?? $data['command_name'] ?? $data['step_name'] ?? 'unknown';

        return match ($eventName) {
            'start', 'invocation_start', 'execution_start' => "Started {$component}",
            'complete', 'invocation_complete', 'execution_complete' => "Completed {$component}",
            'error', 'invocation_error', 'execution_error' => "Error in {$component}: ".($data['error_message'] ?? 'Unknown error'),
            'slow_execution', 'performance_warning' => "Slow execution detected in {$component}",
            default => "{$eventName} in {$component}"
        };
    }

    /**
     * Determine appropriate log level based on event name and data
     */
    protected function determineLogLevel(string $eventName, array $data): string
    {
        // Check for explicit level in data
        if (isset($data['level'])) {
            return $data['level'];
        }

        // Check for error conditions
        if (str_contains($eventName, 'error') || str_contains($eventName, 'failed')) {
            return 'error';
        }

        // Check for warning conditions
        if (str_contains($eventName, 'slow') || str_contains($eventName, 'warning')) {
            return 'warning';
        }

        // Check performance thresholds
        if (isset($data['performance']['duration_ms'])) {
            $duration = $data['performance']['duration_ms'];
            if ($duration > 5000) {
                return 'warning';
            }
            if ($duration > 10000) {
                return 'error';
            }
        }

        // Default to info level
        return 'info';
    }

    /**
     * Batch process multiple events
     */
    public function batchAdaptEvents(array $events): void
    {
        foreach ($events as $event) {
            $eventType = $event['type'] ?? 'unknown';
            $eventName = $event['event_name'] ?? 'event';
            $data = $event['data'] ?? [];

            match ($eventType) {
                'tool' => $this->adaptToolEvent($eventName, $data),
                'command' => $this->adaptCommandEvent($eventName, $data),
                'fragment' => $this->adaptFragmentEvent($eventName, $data),
                'chat' => $this->adaptChatEvent($eventName, $data),
                default => Log::warning("Unknown telemetry event type: {$eventType}")
            };
        }
    }

    /**
     * Flush any buffered data
     */
    public function flush(): void
    {
        $this->sink->flush();
    }
}
