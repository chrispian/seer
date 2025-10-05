<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;

/**
 * Analyzes command telemetry data to provide insights and metrics
 */
class CommandMetricsAnalyzer
{
    /**
     * Analyze command popularity from log files
     */
    public function analyzeCommandPopularity(int $days = 7): array
    {
        $logFile = storage_path('logs/command-telemetry.log');

        if (! file_exists($logFile)) {
            return [];
        }

        $commands = [];
        $handle = fopen($logFile, 'r');

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $data = json_decode($line, true);

                if (! $data || ! isset($data['event']) || $data['event'] !== 'command.execution.completed') {
                    continue;
                }

                $command = $data['data']['command'] ?? 'unknown';
                $timestamp = $data['meta']['timestamp'] ?? null;

                // Filter by date range
                if ($timestamp && strtotime($timestamp) < strtotime("-{$days} days")) {
                    continue;
                }

                if (! isset($commands[$command])) {
                    $commands[$command] = [
                        'count' => 0,
                        'success_count' => 0,
                        'total_duration' => 0,
                        'avg_duration' => 0,
                        'min_duration' => null,
                        'max_duration' => null,
                    ];
                }

                $commands[$command]['count']++;

                if ($data['data']['success'] ?? false) {
                    $commands[$command]['success_count']++;
                }

                $duration = $data['data']['duration_ms'] ?? 0;
                $commands[$command]['total_duration'] += $duration;

                if ($commands[$command]['min_duration'] === null || $duration < $commands[$command]['min_duration']) {
                    $commands[$command]['min_duration'] = $duration;
                }

                if ($commands[$command]['max_duration'] === null || $duration > $commands[$command]['max_duration']) {
                    $commands[$command]['max_duration'] = $duration;
                }
            }

            fclose($handle);
        }

        // Calculate averages and sort by popularity
        foreach ($commands as $command => &$stats) {
            $stats['avg_duration'] = $stats['count'] > 0 ? round($stats['total_duration'] / $stats['count'], 2) : 0;
            $stats['success_rate'] = $stats['count'] > 0 ? round(($stats['success_count'] / $stats['count']) * 100, 2) : 0;
        }

        // Sort by count (most popular first)
        uasort($commands, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $commands;
    }

    /**
     * Analyze performance bottlenecks
     */
    public function analyzePerformanceBottlenecks(int $days = 7): array
    {
        $logFile = storage_path('logs/command-telemetry.log');

        if (! file_exists($logFile)) {
            return [];
        }

        $slowCommands = [];
        $slowSteps = [];
        $handle = fopen($logFile, 'r');

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $data = json_decode($line, true);

                if (! $data || ! isset($data['event'])) {
                    continue;
                }

                $timestamp = $data['meta']['timestamp'] ?? null;

                // Filter by date range
                if ($timestamp && strtotime($timestamp) < strtotime("-{$days} days")) {
                    continue;
                }

                if ($data['event'] === 'command.execution.completed') {
                    $duration = $data['data']['duration_ms'] ?? 0;

                    if ($duration > config('command-telemetry.performance.alert_thresholds.slow_command', 5000)) {
                        $slowCommands[] = [
                            'command' => $data['data']['command'] ?? 'unknown',
                            'duration_ms' => $duration,
                            'performance_category' => $data['data']['performance_category'] ?? 'unknown',
                            'timestamp' => $timestamp,
                        ];
                    }
                }

                if ($data['event'] === 'command.step.completed') {
                    $duration = $data['data']['duration_ms'] ?? 0;

                    if ($duration > config('command-telemetry.performance.alert_thresholds.slow_step', 3000)) {
                        $slowSteps[] = [
                            'step_type' => $data['data']['step_type'] ?? 'unknown',
                            'step_id' => $data['data']['step_id'] ?? 'unknown',
                            'duration_ms' => $duration,
                            'performance_category' => $data['data']['performance_category'] ?? 'unknown',
                            'timestamp' => $timestamp,
                        ];
                    }
                }
            }

            fclose($handle);
        }

        return [
            'slow_commands' => collect($slowCommands)->sortByDesc('duration_ms')->take(20)->values()->all(),
            'slow_steps' => collect($slowSteps)->sortByDesc('duration_ms')->take(20)->values()->all(),
        ];
    }

    /**
     * Analyze error patterns
     */
    public function analyzeErrorPatterns(int $days = 7): array
    {
        $logFile = storage_path('logs/command-telemetry.log');

        if (! file_exists($logFile)) {
            return [];
        }

        $errors = [];
        $handle = fopen($logFile, 'r');

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $data = json_decode($line, true);

                if (! $data || ! isset($data['event'])) {
                    continue;
                }

                $timestamp = $data['meta']['timestamp'] ?? null;

                // Filter by date range
                if ($timestamp && strtotime($timestamp) < strtotime("-{$days} days")) {
                    continue;
                }

                if ($data['event'] === 'command.error') {
                    $category = $data['data']['error_category'] ?? 'general';
                    $context = $data['data']['context'] ?? 'unknown';

                    if (! isset($errors[$category])) {
                        $errors[$category] = [];
                    }

                    if (! isset($errors[$category][$context])) {
                        $errors[$category][$context] = 0;
                    }

                    $errors[$category][$context]++;
                }

                // Also check failed command executions
                if (in_array($data['event'], ['command.execution.completed', 'command.step.completed'])) {
                    if (! ($data['data']['success'] ?? true) && isset($data['data']['error'])) {
                        $category = $data['data']['error_category'] ?? 'general';
                        $context = $data['event'] === 'command.execution.completed'
                            ? $data['data']['command'] ?? 'unknown'
                            : $data['data']['step_type'] ?? 'unknown';

                        if (! isset($errors[$category])) {
                            $errors[$category] = [];
                        }

                        if (! isset($errors[$category][$context])) {
                            $errors[$category][$context] = 0;
                        }

                        $errors[$category][$context]++;
                    }
                }
            }

            fclose($handle);
        }

        // Sort each category by frequency
        foreach ($errors as $category => &$contexts) {
            arsort($contexts);
        }

        return $errors;
    }

    /**
     * Analyze template rendering performance
     */
    public function analyzeTemplatePerformance(int $days = 7): array
    {
        $logFile = storage_path('logs/command-telemetry.log');

        if (! file_exists($logFile)) {
            return [];
        }

        $templates = [
            'total_renders' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'avg_duration' => 0,
            'total_duration' => 0,
            'slow_renders' => [],
        ];

        $handle = fopen($logFile, 'r');

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $data = json_decode($line, true);

                if (! $data || $data['event'] !== 'command.template.rendered') {
                    continue;
                }

                $timestamp = $data['meta']['timestamp'] ?? null;

                // Filter by date range
                if ($timestamp && strtotime($timestamp) < strtotime("-{$days} days")) {
                    continue;
                }

                $templates['total_renders']++;

                if ($data['data']['cache_hit'] ?? false) {
                    $templates['cache_hits']++;
                } else {
                    $templates['cache_misses']++;
                }

                $duration = $data['data']['duration_ms'] ?? 0;
                $templates['total_duration'] += $duration;

                // Track slow renders
                if ($duration > config('command-telemetry.performance.template_thresholds.slow', 200)) {
                    $templates['slow_renders'][] = [
                        'template_hash' => $data['data']['template_hash'] ?? 'unknown',
                        'template_length' => $data['data']['template_length'] ?? 0,
                        'duration_ms' => $duration,
                        'cache_hit' => $data['data']['cache_hit'] ?? false,
                        'timestamp' => $timestamp,
                    ];
                }
            }

            fclose($handle);
        }

        if ($templates['total_renders'] > 0) {
            $templates['avg_duration'] = round($templates['total_duration'] / $templates['total_renders'], 2);
            $templates['cache_hit_rate'] = round(($templates['cache_hits'] / $templates['total_renders']) * 100, 2);
        }

        // Sort slow renders by duration
        usort($templates['slow_renders'], fn ($a, $b) => $b['duration_ms'] <=> $a['duration_ms']);
        $templates['slow_renders'] = array_slice($templates['slow_renders'], 0, 10);

        return $templates;
    }

    /**
     * Generate a comprehensive metrics summary
     */
    public function generateSummary(int $days = 7): array
    {
        return [
            'period_days' => $days,
            'generated_at' => now()->toISOString(),
            'command_popularity' => $this->analyzeCommandPopularity($days),
            'performance_bottlenecks' => $this->analyzePerformanceBottlenecks($days),
            'error_patterns' => $this->analyzeErrorPatterns($days),
            'template_performance' => $this->analyzeTemplatePerformance($days),
        ];
    }
}
