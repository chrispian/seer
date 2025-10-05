<?php

namespace App\Services\Telemetry;

use App\Contracts\ToolContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ToolTelemetry
{
    private const LOG_CHANNEL = 'tool-telemetry';

    private static array $activeInvocations = [];

    private static array $toolChains = [];

    private static array $healthStatus = [];

    public function startInvocation(ToolContract $tool, array $parameters = []): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $invocationId = (string) Str::uuid();
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);

        $context = [
            'invocation_id' => $invocationId,
            'tool_name' => $tool->name(),
            'tool_scope' => $tool->scope(),
            'started_at' => now()->toISOString(),
            'parameters' => $this->sanitizeParameters($parameters),
            'parameter_stats' => $this->analyzeParameters($parameters),
            'memory_before' => $memoryBefore,
            'correlation' => CorrelationContext::hasContext() ? CorrelationContext::forLogging() : null,
            'caller_context' => $this->extractCallerContext(),
        ];

        self::$activeInvocations[$invocationId] = [
            'tool_name' => $tool->name(),
            'start_time' => $startTime,
            'memory_before' => $memoryBefore,
            'parameters' => $parameters,
            'context' => $context,
        ];

        // Track tool chains
        if (config('tool-telemetry.correlation.track_tool_chains', true)) {
            $this->trackToolChain($tool->name(), $invocationId);
        }

        $this->log('tool.invocation.started', $context);

        return $invocationId;
    }

    public function completeInvocation(string $invocationId, array $result = [], ?string $error = null): void
    {
        if (! $this->isEnabled() || ! isset(self::$activeInvocations[$invocationId])) {
            return;
        }

        $invocation = self::$activeInvocations[$invocationId];
        $endTime = microtime(true);
        $memoryAfter = memory_get_usage(true);
        $durationMs = ($endTime - $invocation['start_time']) * 1000;

        $context = array_merge($invocation['context'], [
            'completed_at' => now()->toISOString(),
            'duration_ms' => round($durationMs, 2),
            'memory_after' => $memoryAfter,
            'memory_peak' => memory_get_peak_usage(true),
            'memory_used' => $memoryAfter - $invocation['memory_before'],
            'performance_category' => $this->categorizePerformance($durationMs),
            'success' => $error === null,
            'error' => $error,
            'result_stats' => $error === null ? $this->analyzeResult($result) : null,
        ]);

        // Add tool-specific metrics
        $context['tool_metrics'] = $this->extractToolMetrics($invocation['tool_name'], $invocation['parameters'], $result, $durationMs);

        $eventType = $error === null ? 'tool.invocation.completed' : 'tool.invocation.failed';
        $this->log($eventType, $context);

        // Performance alerts
        if ($this->shouldAlert($durationMs, $error)) {
            $this->sendPerformanceAlert($context);
        }

        // Update health status
        $this->updateToolHealth($invocation['tool_name'], $error === null, $durationMs);

        // Track usage patterns
        if (config('tool-telemetry.metrics.usage_patterns', true)) {
            $this->trackUsagePattern($invocation['tool_name'], $invocation['parameters'], $durationMs);
        }

        unset(self::$activeInvocations[$invocationId]);
    }

    public function trackToolChain(string $toolName, string $invocationId): void
    {
        $correlationId = CorrelationContext::get();
        if (! $correlationId) {
            return;
        }

        if (! isset(self::$toolChains[$correlationId])) {
            self::$toolChains[$correlationId] = [
                'started_at' => now()->toISOString(),
                'tools' => [],
                'depth' => 0,
            ];
        }

        $chain = &self::$toolChains[$correlationId];
        $chain['tools'][] = [
            'tool_name' => $toolName,
            'invocation_id' => $invocationId,
            'timestamp' => now()->toISOString(),
            'depth' => $chain['depth'],
        ];

        $chain['depth']++;

        if ($chain['depth'] > config('tool-telemetry.correlation.max_chain_depth', 10)) {
            $this->log('tool.chain.depth_exceeded', [
                'correlation_id' => $correlationId,
                'depth' => $chain['depth'],
                'chain' => $chain,
            ]);
        }
    }

    public function recordHealthCheck(string $toolName, bool $healthy, ?string $error = null, float $responseTimeMs = 0): void
    {
        if (! config('tool-telemetry.health.enabled', true)) {
            return;
        }

        $timestamp = now();
        $healthData = [
            'tool_name' => $toolName,
            'healthy' => $healthy,
            'response_time_ms' => $responseTimeMs,
            'error' => $error,
            'timestamp' => $timestamp->toISOString(),
        ];

        // Update health status
        if (! isset(self::$healthStatus[$toolName])) {
            self::$healthStatus[$toolName] = [
                'consecutive_failures' => 0,
                'consecutive_successes' => 0,
                'last_check' => null,
                'current_status' => 'unknown',
            ];
        }

        $status = &self::$healthStatus[$toolName];

        if ($healthy) {
            $status['consecutive_failures'] = 0;
            $status['consecutive_successes']++;
        } else {
            $status['consecutive_successes'] = 0;
            $status['consecutive_failures']++;
        }

        $status['last_check'] = $timestamp;
        $previousStatus = $status['current_status'];

        // Determine new status
        $failureThreshold = config('tool-telemetry.health.failure_threshold', 3);
        $recoveryThreshold = config('tool-telemetry.health.recovery_threshold', 2);

        if ($status['consecutive_failures'] >= $failureThreshold) {
            $status['current_status'] = 'unhealthy';
        } elseif ($status['consecutive_successes'] >= $recoveryThreshold) {
            $status['current_status'] = 'healthy';
        }

        // Log health change
        if ($previousStatus !== $status['current_status']) {
            $this->log('tool.health.status_changed', array_merge($healthData, [
                'previous_status' => $previousStatus,
                'new_status' => $status['current_status'],
                'consecutive_failures' => $status['consecutive_failures'],
                'consecutive_successes' => $status['consecutive_successes'],
            ]));
        }

        $this->log('tool.health.check', $healthData);
    }

    private function sanitizeParameters(array $parameters): array
    {
        $sensitivePatterns = config('tool-telemetry.sanitization.sensitive_patterns', []);
        $maxLength = config('tool-telemetry.sanitization.max_parameter_length', 500);
        $allowlist = config('tool-telemetry.sanitization.parameter_allowlist', []);

        $sanitized = [];

        foreach ($parameters as $key => $value) {
            // Check if parameter is explicitly allowed
            if (in_array($key, $allowlist)) {
                $sanitized[$key] = $this->truncateValue($value, $maxLength);

                continue;
            }

            // Check for sensitive patterns
            $isSensitive = false;
            foreach ($sensitivePatterns as $pattern) {
                if (preg_match($pattern, $key)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                if (config('tool-telemetry.sanitization.hash_sensitive_values', true)) {
                    $sanitized[$key] = hash('sha256', json_encode($value));
                } else {
                    $sanitized[$key] = '[REDACTED]';
                }
            } else {
                $sanitized[$key] = $this->truncateValue($value, $maxLength);
            }
        }

        return $sanitized;
    }

    private function analyzeParameters(array $parameters): array
    {
        return [
            'count' => count($parameters),
            'size_bytes' => strlen(json_encode($parameters)),
            'complexity' => $this->calculateComplexity($parameters),
            'types' => $this->getParameterTypes($parameters),
        ];
    }

    private function analyzeResult(array $result): array
    {
        $resultSize = strlen(json_encode($result));

        return [
            'size_bytes' => $resultSize,
            'size_category' => $this->categorizeDataSize($resultSize),
            'structure' => $this->analyzeStructure($result),
            'record_count' => $this->extractRecordCount($result),
        ];
    }

    private function extractToolMetrics(string $toolName, array $parameters, array $result, float $durationMs): array
    {
        $toolConfig = config("tool-telemetry.tool_types.{$toolName}", []);
        $metrics = [];

        switch ($toolName) {
            case 'db.query':
                if ($toolConfig['track_query_performance'] ?? false) {
                    $metrics['query_type'] = $this->identifyQueryType($parameters);
                    $metrics['filter_count'] = count($parameters['filters'] ?? []);
                    $metrics['has_search'] = ! empty($parameters['search']);
                    $metrics['limit'] = $parameters['limit'] ?? null;
                }
                if ($toolConfig['track_result_counts'] ?? false) {
                    $metrics['result_count'] = count($result['items'] ?? []);
                }
                break;

            case 'memory.search':
                if ($toolConfig['track_search_performance'] ?? false) {
                    $metrics['query_length'] = strlen($parameters['q'] ?? '');
                    $metrics['has_filters'] = ! empty($parameters['kinds']) || ! empty($parameters['scope']);
                }
                break;

            case 'memory.write':
                if ($toolConfig['track_data_size'] ?? false) {
                    $metrics['content_size'] = strlen($parameters['body'] ?? '');
                    $metrics['topic_length'] = strlen($parameters['topic'] ?? '');
                }
                break;
        }

        return $metrics;
    }

    private function categorizePerformance(float $durationMs): string
    {
        $thresholds = config('tool-telemetry.performance.tool_thresholds', [
            'fast' => 50,
            'normal' => 200,
            'slow' => 1000,
            'very_slow' => 3000,
        ]);

        if ($durationMs < $thresholds['fast']) {
            return 'fast';
        }
        if ($durationMs < $thresholds['normal']) {
            return 'normal';
        }
        if ($durationMs < $thresholds['slow']) {
            return 'slow';
        }
        if ($durationMs < $thresholds['very_slow']) {
            return 'very_slow';
        }

        return 'critical';
    }

    private function categorizeDataSize(int $bytes): string
    {
        $thresholds = config('tool-telemetry.performance.data_size_thresholds', [
            'small_payload' => 1024,
            'medium_payload' => 102400,
            'large_payload' => 1048576,
        ]);

        if ($bytes < $thresholds['small_payload']) {
            return 'small';
        }
        if ($bytes < $thresholds['medium_payload']) {
            return 'medium';
        }
        if ($bytes < $thresholds['large_payload']) {
            return 'large';
        }

        return 'very_large';
    }

    private function shouldAlert(float $durationMs, ?string $error): bool
    {
        if (! config('tool-telemetry.alerts.enabled', true)) {
            return false;
        }

        $conditions = config('tool-telemetry.alerts.conditions', [
            'availability_issues' => true,
            'performance_degradation' => true,
        ]);

        if ($error && ($conditions['availability_issues'] ?? true)) {
            return true;
        }

        if ($durationMs > config('tool-telemetry.performance.alert_thresholds.slow_tool', 3000) && ($conditions['performance_degradation'] ?? true)) {
            return true;
        }

        return false;
    }

    private function sendPerformanceAlert(array $context): void
    {
        $this->log('tool.alert.performance', [
            'alert_type' => 'performance_degradation',
            'tool_name' => $context['tool_name'],
            'duration_ms' => $context['duration_ms'],
            'threshold_ms' => config('tool-telemetry.performance.alert_thresholds.slow_tool', 3000),
            'context' => $context,
        ]);
    }

    private function updateToolHealth(string $toolName, bool $success, float $durationMs): void
    {
        $this->recordHealthCheck($toolName, $success, $success ? null : 'execution_failure', $durationMs);
    }

    private function trackUsagePattern(string $toolName, array $parameters, float $durationMs): void
    {
        if (! $this->shouldSample('usage_patterns')) {
            return;
        }

        $this->log('tool.usage.pattern', [
            'tool_name' => $toolName,
            'parameter_pattern' => $this->extractParameterPattern($parameters),
            'performance_category' => $this->categorizePerformance($durationMs),
            'timestamp' => now()->toISOString(),
            'hour_of_day' => now()->hour,
            'day_of_week' => now()->dayOfWeek,
        ]);
    }

    private function extractCallerContext(): array
    {
        if (! config('tool-telemetry.context.include_caller_context', true)) {
            return [];
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $callers = [];

        foreach ($trace as $frame) {
            if (isset($frame['class']) && ! str_contains($frame['class'], 'Telemetry')) {
                $callers[] = [
                    'class' => $frame['class'] ?? null,
                    'function' => $frame['function'] ?? null,
                    'file' => basename($frame['file'] ?? ''),
                    'line' => $frame['line'] ?? null,
                ];

                if (count($callers) >= 3) {
                    break;
                }
            }
        }

        return $callers;
    }

    private function extractParameterPattern(array $parameters): array
    {
        $pattern = [];

        foreach ($parameters as $key => $value) {
            $pattern[$key] = [
                'type' => gettype($value),
                'present' => $value !== null,
                'size' => is_string($value) ? strlen($value) : (is_array($value) ? count($value) : null),
            ];
        }

        return $pattern;
    }

    private function calculateComplexity(array $data): int
    {
        $complexity = 0;

        foreach ($data as $value) {
            if (is_array($value)) {
                $complexity += count($value) + $this->calculateComplexity($value);
            } else {
                $complexity += 1;
            }
        }

        return $complexity;
    }

    private function getParameterTypes(array $parameters): array
    {
        $types = [];
        foreach ($parameters as $key => $value) {
            $types[$key] = gettype($value);
        }

        return $types;
    }

    private function analyzeStructure(array $data): array
    {
        return [
            'depth' => $this->calculateDepth($data),
            'keys' => array_keys($data),
            'array_count' => $this->countArrays($data),
        ];
    }

    private function extractRecordCount(array $result): ?int
    {
        if (isset($result['items'])) {
            return count($result['items']);
        }

        if (isset($result['records'])) {
            return count($result['records']);
        }

        return null;
    }

    private function identifyQueryType(array $parameters): string
    {
        if (! empty($parameters['search'])) {
            return 'search';
        }

        if (! empty($parameters['filters'])) {
            return 'filtered';
        }

        return 'basic';
    }

    private function calculateDepth(array $data, int $currentDepth = 0): int
    {
        $maxDepth = $currentDepth;

        foreach ($data as $value) {
            if (is_array($value)) {
                $depth = $this->calculateDepth($value, $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    private function countArrays(array $data): int
    {
        $count = 0;

        foreach ($data as $value) {
            if (is_array($value)) {
                $count += 1 + $this->countArrays($value);
            }
        }

        return $count;
    }

    private function truncateValue(mixed $value, int $maxLength): mixed
    {
        if (is_string($value) && strlen($value) > $maxLength) {
            return substr($value, 0, $maxLength).'...';
        }

        if (is_array($value)) {
            $serialized = json_encode($value);
            if (strlen($serialized) > $maxLength) {
                return '[TRUNCATED_ARRAY:'.count($value).'_items]';
            }
        }

        return $value;
    }

    private function log(string $event, array $data): void
    {
        if (! $this->shouldSample($event)) {
            return;
        }

        $logData = array_merge($data, [
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
        ]);

        // Add correlation context if available
        if (CorrelationContext::hasContext()) {
            $logData['correlation'] = CorrelationContext::forLogging();
        }

        // Add request context
        if (config('tool-telemetry.context.include_request_id', true) && request()?->header('X-Request-ID')) {
            $logData['request_id'] = request()->header('X-Request-ID');
        }

        Log::channel(self::LOG_CHANNEL)->info($event, $logData);
    }

    private function isEnabled(): bool
    {
        return config('tool-telemetry.enabled', true);
    }

    private function shouldSample(string $eventType): bool
    {
        $sampleKey = match ($eventType) {
            'tool.invocation.started', 'tool.invocation.completed', 'tool.invocation.failed' => 'tool_execution',
            'tool.health.check' => 'health_checks',
            'tool.usage.pattern' => 'usage_patterns',
            default => $eventType
        };

        $rate = config("tool-telemetry.sampling.{$sampleKey}", 1.0);

        return mt_rand() / mt_getrandmax() < $rate;
    }

    public static function getActiveInvocations(): array
    {
        return self::$activeInvocations;
    }

    public static function getToolChains(): array
    {
        return self::$toolChains;
    }

    public static function getHealthStatus(): array
    {
        return self::$healthStatus;
    }
}
