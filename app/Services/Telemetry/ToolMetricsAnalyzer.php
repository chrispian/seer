<?php

namespace App\Services\Telemetry;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class ToolMetricsAnalyzer
{
    public function analyzePerformance(int $days = 7): array
    {
        $logFile = storage_path('logs/tool-telemetry.log');

        if (! File::exists($logFile)) {
            return ['error' => 'Tool telemetry log file not found'];
        }

        $since = Carbon::now()->subDays($days);
        $events = $this->parseLogEvents($logFile, $since);

        $completedEvents = array_filter($events, fn ($event) => str_contains($event['event'] ?? '', 'tool.invocation.completed')
        );

        if (empty($completedEvents)) {
            return ['summary' => ['total_invocations' => 0]];
        }

        $durations = array_column($completedEvents, 'duration_ms');
        $memoryUsage = array_column($completedEvents, 'memory_used');

        sort($durations);
        sort($memoryUsage);

        $totalInvocations = count($completedEvents);

        return [
            'summary' => [
                'total_invocations' => $totalInvocations,
                'avg_duration_ms' => round(array_sum($durations) / $totalInvocations, 2),
                'min_duration_ms' => min($durations),
                'max_duration_ms' => max($durations),
                'p50_duration_ms' => $this->percentile($durations, 50),
                'p95_duration_ms' => $this->percentile($durations, 95),
                'p99_duration_ms' => $this->percentile($durations, 99),
                'avg_memory_mb' => round(array_sum($memoryUsage) / $totalInvocations / 1024 / 1024, 2),
                'max_memory_mb' => round(max($memoryUsage) / 1024 / 1024, 2),
            ],
            'by_tool' => $this->analyzePerformanceByTool($completedEvents),
            'by_performance_category' => $this->analyzeByPerformanceCategory($completedEvents),
            'hourly_patterns' => $this->analyzeHourlyPatterns($completedEvents),
            'slow_operations' => $this->findSlowOperations($completedEvents),
        ];
    }

    public function analyzeUsagePatterns(int $days = 7): array
    {
        $logFile = storage_path('logs/tool-telemetry.log');

        if (! File::exists($logFile)) {
            return ['error' => 'Tool telemetry log file not found'];
        }

        $since = Carbon::now()->subDays($days);
        $events = $this->parseLogEvents($logFile, $since);

        $invocationEvents = array_filter($events, fn ($event) => str_contains($event['event'] ?? '', 'tool.invocation.')
        );

        return [
            'tool_popularity' => $this->calculateToolPopularity($invocationEvents),
            'usage_by_day' => $this->analyzeUsageByDay($invocationEvents),
            'usage_by_hour' => $this->analyzeUsageByHour($invocationEvents),
            'parameter_patterns' => $this->analyzeParameterPatterns($invocationEvents),
            'tool_chains' => $this->analyzeToolChains($events),
            'concurrent_usage' => $this->analyzeConcurrentUsage($invocationEvents),
        ];
    }

    public function analyzeErrors(int $days = 7): array
    {
        $logFile = storage_path('logs/tool-telemetry.log');

        if (! File::exists($logFile)) {
            return ['error' => 'Tool telemetry log file not found'];
        }

        $since = Carbon::now()->subDays($days);
        $events = $this->parseLogEvents($logFile, $since);

        $completedEvents = array_filter($events, fn ($event) => str_contains($event['event'] ?? '', 'tool.invocation.')
        );

        $failedEvents = array_filter($events, fn ($event) => str_contains($event['event'] ?? '', 'tool.invocation.failed')
        );

        $totalEvents = count($completedEvents);
        $errorCount = count($failedEvents);

        return [
            'summary' => [
                'total_errors' => $errorCount,
                'total_invocations' => $totalEvents,
                'error_rate' => $totalEvents > 0 ? $errorCount / $totalEvents : 0,
            ],
            'by_tool' => $this->analyzeErrorsByTool($failedEvents),
            'error_patterns' => $this->analyzeErrorPatterns($failedEvents),
            'error_trends' => $this->analyzeErrorTrends($failedEvents, $days),
            'consecutive_failures' => $this->findConsecutiveFailures($events),
        ];
    }

    public function analyzeCorrelation(int $days = 7): array
    {
        $logFile = storage_path('logs/tool-telemetry.log');

        if (! File::exists($logFile)) {
            return ['error' => 'Tool telemetry log file not found'];
        }

        $since = Carbon::now()->subDays($days);
        $events = $this->parseLogEvents($logFile, $since);

        return [
            'tool_sequences' => $this->findToolSequences($events),
            'correlation_chains' => $this->analyzeCorrelationChains($events),
            'nested_invocations' => $this->findNestedInvocations($events),
            'cross_tool_patterns' => $this->analyzeCrossToolPatterns($events),
        ];
    }

    private function parseLogEvents(string $logFile, Carbon $since): array
    {
        $events = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            try {
                // Parse Laravel log format
                if (preg_match('/\[(.*?)\].*?: (.*?)({.*})/', $line, $matches)) {
                    $timestamp = Carbon::parse($matches[1]);

                    if ($timestamp->lt($since)) {
                        continue;
                    }

                    $eventData = json_decode($matches[3], true);
                    if ($eventData && isset($eventData['event'])) {
                        $eventData['parsed_timestamp'] = $timestamp;
                        $events[] = $eventData;
                    }
                }
            } catch (\Exception $e) {
                // Skip malformed log lines
                continue;
            }
        }

        return $events;
    }

    private function percentile(array $values, int $percentile): float
    {
        $count = count($values);
        $index = ($percentile / 100) * ($count - 1);

        if (floor($index) == $index) {
            return $values[$index];
        }

        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        $fraction = $index - floor($index);

        return $lower + ($fraction * ($upper - $lower));
    }

    private function analyzePerformanceByTool(array $events): array
    {
        $byTool = [];

        foreach ($events as $event) {
            $toolName = $event['tool_name'] ?? 'unknown';
            $duration = $event['duration_ms'] ?? 0;

            if (! isset($byTool[$toolName])) {
                $byTool[$toolName] = [];
            }

            $byTool[$toolName][] = $duration;
        }

        $result = [];
        foreach ($byTool as $tool => $durations) {
            sort($durations);
            $count = count($durations);

            $result[$tool] = [
                'invocations' => $count,
                'avg_duration_ms' => round(array_sum($durations) / $count, 2),
                'min_duration_ms' => min($durations),
                'max_duration_ms' => max($durations),
                'p95_duration_ms' => $this->percentile($durations, 95),
            ];
        }

        return $result;
    }

    private function analyzeByPerformanceCategory(array $events): array
    {
        $categories = ['fast' => 0, 'normal' => 0, 'slow' => 0, 'very_slow' => 0, 'critical' => 0];

        foreach ($events as $event) {
            $category = $event['performance_category'] ?? 'unknown';
            if (isset($categories[$category])) {
                $categories[$category]++;
            }
        }

        return $categories;
    }

    private function analyzeHourlyPatterns(array $events): array
    {
        $hourly = array_fill(0, 24, 0);

        foreach ($events as $event) {
            if (isset($event['parsed_timestamp'])) {
                $hour = $event['parsed_timestamp']->hour;
                $hourly[$hour]++;
            }
        }

        return $hourly;
    }

    private function findSlowOperations(array $events): array
    {
        $slowThreshold = config('tool-telemetry.performance.alert_thresholds.slow_tool', 3000);

        $slowOps = array_filter($events, fn ($event) => ($event['duration_ms'] ?? 0) > $slowThreshold
        );

        return array_map(fn ($event) => [
            'tool_name' => $event['tool_name'] ?? 'unknown',
            'duration_ms' => $event['duration_ms'] ?? 0,
            'timestamp' => $event['completed_at'] ?? null,
            'invocation_id' => $event['invocation_id'] ?? null,
        ], $slowOps);
    }

    private function calculateToolPopularity(array $events): array
    {
        $popularity = [];

        foreach ($events as $event) {
            $toolName = $event['tool_name'] ?? 'unknown';
            $popularity[$toolName] = ($popularity[$toolName] ?? 0) + 1;
        }

        arsort($popularity);

        return $popularity;
    }

    private function analyzeUsageByDay(array $events): array
    {
        $daily = [];

        foreach ($events as $event) {
            if (isset($event['parsed_timestamp'])) {
                $day = $event['parsed_timestamp']->format('Y-m-d');
                $daily[$day] = ($daily[$day] ?? 0) + 1;
            }
        }

        ksort($daily);

        return $daily;
    }

    private function analyzeUsageByHour(array $events): array
    {
        $hourly = array_fill(0, 24, 0);

        foreach ($events as $event) {
            if (isset($event['parsed_timestamp'])) {
                $hour = $event['parsed_timestamp']->hour;
                $hourly[$hour]++;
            }
        }

        return $hourly;
    }

    private function analyzeParameterPatterns(array $events): array
    {
        $patterns = [];

        foreach ($events as $event) {
            if (isset($event['parameter_stats'])) {
                $complexity = $event['parameter_stats']['complexity'] ?? 0;
                $size = $event['parameter_stats']['size_bytes'] ?? 0;

                $patterns[] = [
                    'tool_name' => $event['tool_name'] ?? 'unknown',
                    'complexity' => $complexity,
                    'size_bytes' => $size,
                ];
            }
        }

        return [
            'avg_complexity' => count($patterns) > 0 ? array_sum(array_column($patterns, 'complexity')) / count($patterns) : 0,
            'avg_size_bytes' => count($patterns) > 0 ? array_sum(array_column($patterns, 'size_bytes')) / count($patterns) : 0,
            'by_tool' => $this->groupParameterPatternsByTool($patterns),
        ];
    }

    private function analyzeToolChains(array $events): array
    {
        $chainEvents = array_filter($events, fn ($event) => str_contains($event['event'] ?? '', 'tool.chain.')
        );

        $chains = [];
        foreach ($chainEvents as $event) {
            if (isset($event['correlation_id'])) {
                $correlationId = $event['correlation_id'];
                if (! isset($chains[$correlationId])) {
                    $chains[$correlationId] = [];
                }
                $chains[$correlationId][] = $event;
            }
        }

        return [
            'total_chains' => count($chains),
            'avg_chain_length' => count($chains) > 0 ? array_sum(array_map('count', $chains)) / count($chains) : 0,
            'longest_chain' => count($chains) > 0 ? max(array_map('count', $chains)) : 0,
        ];
    }

    private function analyzeConcurrentUsage(array $events): array
    {
        // This would require more sophisticated analysis of overlapping invocations
        // For now, return basic concurrent usage metrics

        $startEvents = array_filter($events, fn ($event) => str_contains($event['event'] ?? '', 'tool.invocation.started')
        );

        $endEvents = array_filter($events, fn ($event) => str_contains($event['event'] ?? '', 'tool.invocation.completed') ||
            str_contains($event['event'] ?? '', 'tool.invocation.failed')
        );

        return [
            'peak_concurrent' => $this->calculatePeakConcurrency($startEvents, $endEvents),
            'avg_concurrent' => $this->calculateAverageConcurrency($startEvents, $endEvents),
        ];
    }

    private function analyzeErrorsByTool(array $events): array
    {
        $errors = [];

        foreach ($events as $event) {
            $toolName = $event['tool_name'] ?? 'unknown';
            $errors[$toolName] = ($errors[$toolName] ?? 0) + 1;
        }

        arsort($errors);

        return $errors;
    }

    private function analyzeErrorPatterns(array $events): array
    {
        $patterns = [];

        foreach ($events as $event) {
            $error = $event['error'] ?? 'unknown';
            $errorType = $this->categorizeError($error);
            $patterns[$errorType] = ($patterns[$errorType] ?? 0) + 1;
        }

        return $patterns;
    }

    private function analyzeErrorTrends(array $events, int $days): array
    {
        $daily = [];

        foreach ($events as $event) {
            if (isset($event['parsed_timestamp'])) {
                $day = $event['parsed_timestamp']->format('Y-m-d');
                $daily[$day] = ($daily[$day] ?? 0) + 1;
            }
        }

        ksort($daily);

        return $daily;
    }

    private function findConsecutiveFailures(array $events): array
    {
        // Group by tool and find consecutive failures
        $byTool = [];
        foreach ($events as $event) {
            if (str_contains($event['event'] ?? '', 'tool.invocation.')) {
                $toolName = $event['tool_name'] ?? 'unknown';
                $byTool[$toolName][] = $event;
            }
        }

        $consecutiveFailures = [];
        foreach ($byTool as $tool => $toolEvents) {
            usort($toolEvents, fn ($a, $b) => ($a['parsed_timestamp'] ?? new Carbon)->compare($b['parsed_timestamp'] ?? new Carbon)
            );

            $consecutive = 0;
            $maxConsecutive = 0;

            foreach ($toolEvents as $event) {
                if (str_contains($event['event'] ?? '', 'failed')) {
                    $consecutive++;
                    $maxConsecutive = max($maxConsecutive, $consecutive);
                } else {
                    $consecutive = 0;
                }
            }

            if ($maxConsecutive > 0) {
                $consecutiveFailures[$tool] = $maxConsecutive;
            }
        }

        return $consecutiveFailures;
    }

    private function findToolSequences(array $events): array
    {
        // Implementation for finding common tool usage sequences
        return []; // Placeholder
    }

    private function analyzeCorrelationChains(array $events): array
    {
        // Implementation for analyzing correlation chains
        return []; // Placeholder
    }

    private function findNestedInvocations(array $events): array
    {
        // Implementation for finding nested tool invocations
        return []; // Placeholder
    }

    private function analyzeCrossToolPatterns(array $events): array
    {
        // Implementation for cross-tool pattern analysis
        return []; // Placeholder
    }

    private function groupParameterPatternsByTool(array $patterns): array
    {
        $byTool = [];

        foreach ($patterns as $pattern) {
            $tool = $pattern['tool_name'];
            if (! isset($byTool[$tool])) {
                $byTool[$tool] = [];
            }
            $byTool[$tool][] = $pattern;
        }

        $result = [];
        foreach ($byTool as $tool => $toolPatterns) {
            $result[$tool] = [
                'avg_complexity' => array_sum(array_column($toolPatterns, 'complexity')) / count($toolPatterns),
                'avg_size_bytes' => array_sum(array_column($toolPatterns, 'size_bytes')) / count($toolPatterns),
                'count' => count($toolPatterns),
            ];
        }

        return $result;
    }

    private function calculatePeakConcurrency(array $startEvents, array $endEvents): int
    {
        // Simplified calculation - in reality would need to track overlapping time periods
        return max(count($startEvents), count($endEvents));
    }

    private function calculateAverageConcurrency(array $startEvents, array $endEvents): float
    {
        // Simplified calculation
        $total = count($startEvents) + count($endEvents);

        return $total > 0 ? $total / 2 : 0;
    }

    private function categorizeError(string $error): string
    {
        if (str_contains(strtolower($error), 'timeout')) {
            return 'timeout';
        }

        if (str_contains(strtolower($error), 'database') || str_contains(strtolower($error), 'sql')) {
            return 'database';
        }

        if (str_contains(strtolower($error), 'permission') || str_contains(strtolower($error), 'access')) {
            return 'permission';
        }

        if (str_contains(strtolower($error), 'validation')) {
            return 'validation';
        }

        return 'other';
    }
}
