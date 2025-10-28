<?php

namespace App\Console\Commands\Telemetry;

use App\Models\TelemetryEvent;
use App\Services\Telemetry\TelemetryQueryService;
use App\Services\Telemetry\TelemetrySink;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TelemetryHealthCommand extends Command
{
    protected $signature = 'telemetry:health
                           {--watch : Continuously monitor health (refresh every 30s)}
                           {--component= : Focus on specific component}
                           {--time-range=1h : Time range for analysis (1h, 6h, 24h)}
                           {--threshold= : Custom alert threshold}
                           {--format=table : Output format (table, json)}';

    protected $description = 'Monitor system health across all telemetry components';

    protected TelemetryQueryService $queryService;

    protected TelemetrySink $sink;

    public function __construct(TelemetryQueryService $queryService, TelemetrySink $sink)
    {
        parent::__construct();
        $this->queryService = $queryService;
        $this->sink = $sink;
    }

    public function handle(): int
    {
        $watch = $this->option('watch');
        $component = $this->option('component');
        $timeRange = $this->option('time-range');
        $format = $this->option('format');

        $this->info('🏥 Telemetry Health Monitor');
        $this->line('');

        if ($watch) {
            $this->watchMode($component, $timeRange, $format);
        } else {
            $this->singleReport($component, $timeRange, $format);
        }

        return 0;
    }

    private function watchMode(?string $component = null, string $timeRange = '1h', string $format = 'table'): void
    {
        $this->info('👀 Entering watch mode (Ctrl+C to exit)');
        $this->line('Refreshing every 30 seconds...');
        $this->line('');

        while (true) {
            $this->clearScreen();
            $this->info('🏥 Telemetry Health Monitor - '.now()->format('Y-m-d H:i:s'));
            $this->line('');

            $this->singleReport($component, $timeRange, $format, false);

            $this->line('');
            $this->comment('Next refresh in 30 seconds... (Ctrl+C to exit)');

            sleep(30);
        }
    }

    private function singleReport(?string $component = null, string $timeRange = '1h', string $format = 'table', bool $showHeader = true): void
    {
        try {
            $filters = $component ? ['component' => $component] : [];
            $timeRangeFilter = $this->parseTimeRange($timeRange);

            // Get overall health status
            $healthStatus = $this->queryService->getHealthStatus($filters);

            // Get system metrics
            $systemMetrics = $this->getSystemMetrics($filters, $timeRangeFilter);

            // Get error analysis
            $errorAnalysis = $this->getErrorAnalysis($filters, $timeRangeFilter);

            // Get performance analysis
            $performanceAnalysis = $this->queryService->getPerformanceAnalysis(array_merge($filters, ['time_range' => $timeRangeFilter]));

            // Get telemetry sink status
            $sinkStatus = $this->sink->getBufferStatus();

            if ($format === 'json') {
                $this->outputJson([
                    'health_status' => $healthStatus,
                    'system_metrics' => $systemMetrics,
                    'error_analysis' => $errorAnalysis,
                    'performance_analysis' => $performanceAnalysis,
                    'sink_status' => $sinkStatus,
                ]);
            } else {
                if ($showHeader) {
                    $this->displayOverallHealth($healthStatus);
                }
                $this->displaySystemMetrics($systemMetrics, $timeRange);
                $this->displayErrorAnalysis($errorAnalysis);
                $this->displayPerformanceHealth($performanceAnalysis);
                $this->displaySinkStatus($sinkStatus);
            }

        } catch (\Exception $e) {
            $this->error("Health check failed: {$e->getMessage()}");
        }
    }

    private function parseTimeRange(string $range): array
    {
        $end = now();

        $start = match ($range) {
            '1h' => $end->copy()->subHour(),
            '6h' => $end->copy()->subHours(6),
            '24h' => $end->copy()->subDay(),
            '7d' => $end->copy()->subWeek(),
            default => $end->copy()->subHour()
        };

        return [
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
        ];
    }

    private function getSystemMetrics(array $filters, array $timeRange): array
    {
        $eventFilters = array_merge($filters, ['time_range' => $timeRange]);
        $stats = $this->queryService->getEventStatistics($eventFilters);

        return [
            'total_events' => $stats['total_events'],
            'error_rate' => $stats['error_rate'],
            'events_by_component' => $stats['events_by_component'],
            'events_by_level' => $stats['events_by_level'],
            'event_rate_per_minute' => $this->calculateEventRate($eventFilters),
        ];
    }

    private function getErrorAnalysis(array $filters, array $timeRange): array
    {
        $errorFilters = array_merge($filters, [
            'time_range' => $timeRange,
            'level' => ['error', 'critical'],
        ]);

        $errorEvents = TelemetryEvent::query();
        if (isset($filters['component'])) {
            $errorEvents->where('component', $filters['component']);
        }

        $start = Carbon::parse($timeRange['start']);
        $end = Carbon::parse($timeRange['end']);
        $errorEvents->timeRange($start, $end)->errors();

        $errors = $errorEvents->get();

        return [
            'total_errors' => $errors->count(),
            'errors_by_component' => $errors->groupBy('component')->map->count()->toArray(),
            'recent_errors' => $errors->take(5)->map(function ($error) {
                return [
                    'timestamp' => $error->timestamp->format('H:i:s'),
                    'component' => $error->component,
                    'message' => substr($error->message ?? '', 0, 50),
                ];
            })->toArray(),
            'error_patterns' => $this->identifyErrorPatterns($errors),
        ];
    }

    private function calculateEventRate(array $filters): float
    {
        $start = Carbon::parse($filters['time_range']['start']);
        $end = Carbon::parse($filters['time_range']['end']);
        $minutes = $start->diffInMinutes($end);

        if ($minutes === 0) {
            return 0.0;
        }

        $eventCount = TelemetryEvent::query();
        if (isset($filters['component'])) {
            $eventCount->where('component', $filters['component']);
        }
        $eventCount = $eventCount->timeRange($start, $end)->count();

        return $eventCount / $minutes;
    }

    private function identifyErrorPatterns(object $errors): array
    {
        $patterns = [];

        // Group by error message patterns
        $messageGroups = $errors->groupBy(function ($error) {
            // Extract first few words of error message for pattern matching
            $message = $error->message ?? '';
            $words = explode(' ', $message);

            return implode(' ', array_slice($words, 0, 3));
        });

        foreach ($messageGroups as $pattern => $groupErrors) {
            if ($groupErrors->count() > 1) {
                $patterns[] = [
                    'pattern' => $pattern,
                    'count' => $groupErrors->count(),
                    'components' => $groupErrors->pluck('component')->unique()->values()->toArray(),
                ];
            }
        }

        return $patterns;
    }

    private function displayOverallHealth(array $healthStatus): void
    {
        $icon = $healthStatus['overall_health'] ? '🟢' : '🔴';
        $status = $healthStatus['overall_health'] ? 'HEALTHY' : 'UNHEALTHY';

        $this->line($icon.' Overall System Health: '.$status);
        $this->line("✅ Healthy Components: {$healthStatus['healthy_checks']}/{$healthStatus['total_checks']}");
        $this->line('');
    }

    private function displaySystemMetrics(array $metrics, string $timeRange): void
    {
        $this->info("📊 System Metrics (Last {$timeRange}):");
        $this->line("Total Events: {$metrics['total_events']}");
        $this->line('Error Rate: '.number_format($metrics['error_rate'], 2).'%');
        $this->line('Event Rate: '.number_format($metrics['event_rate_per_minute'], 1).' events/min');
        $this->line('');

        if (! empty($metrics['events_by_component'])) {
            $this->info('Events by Component:');
            foreach ($metrics['events_by_component'] as $component => $count) {
                $this->line("  {$component}: {$count}");
            }
            $this->line('');
        }
    }

    private function displayErrorAnalysis(array $errorAnalysis): void
    {
        $this->info('🚨 Error Analysis:');
        $this->line("Total Errors: {$errorAnalysis['total_errors']}");

        if (! empty($errorAnalysis['recent_errors'])) {
            $this->line('');
            $this->info('Recent Errors:');
            $headers = ['Time', 'Component', 'Message'];
            $rows = array_map(function ($error) {
                return [
                    $error['timestamp'],
                    $error['component'],
                    $error['message'],
                ];
            }, $errorAnalysis['recent_errors']);

            $this->table($headers, $rows);
        }

        if (! empty($errorAnalysis['error_patterns'])) {
            $this->info('Error Patterns:');
            foreach ($errorAnalysis['error_patterns'] as $pattern) {
                $this->line("  {$pattern['pattern']}: {$pattern['count']} occurrences");
            }
        }

        $this->line('');
    }

    private function displayPerformanceHealth(array $performance): void
    {
        $this->info('⚡ Performance Health:');
        $this->line('Average Duration: '.number_format($performance['avg_duration_ms'], 1).'ms');
        $this->line('P95 Duration: '.number_format($performance['p95_duration_ms'] ?? 0, 1).'ms');
        $this->line('Average Memory: '.number_format($performance['avg_memory_usage_mb'], 1).'MB');

        if (! empty($performance['performance_distribution'])) {
            $this->line('');
            $this->info('Performance Distribution:');
            foreach ($performance['performance_distribution'] as $class => $count) {
                $icon = match ($class) {
                    'fast' => '🟢',
                    'normal' => '🟡',
                    'slow' => '🟠',
                    'critical' => '🔴',
                    default => '⚪'
                };
                $this->line("  {$icon} {$class}: {$count}");
            }
        }

        $this->line('');
    }

    private function displaySinkStatus(array $sinkStatus): void
    {
        $this->info('💾 Telemetry Sink Status:');
        $this->line("Event Buffer: {$sinkStatus['event_buffer_size']}/{$sinkStatus['max_buffer_size']}");
        $this->line("Metric Buffer: {$sinkStatus['metric_buffer_size']}/{$sinkStatus['max_buffer_size']}");
        $this->line('Async Processing: '.($sinkStatus['async_processing'] ? 'Enabled' : 'Disabled'));

        // Calculate buffer health
        $eventBufferPercent = ($sinkStatus['event_buffer_size'] / $sinkStatus['max_buffer_size']) * 100;
        $metricBufferPercent = ($sinkStatus['metric_buffer_size'] / $sinkStatus['max_buffer_size']) * 100;

        if ($eventBufferPercent > 80 || $metricBufferPercent > 80) {
            $this->warn('⚠️ High buffer usage detected - consider increasing flush frequency');
        }
    }

    private function outputJson(array $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function clearScreen(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }
}
