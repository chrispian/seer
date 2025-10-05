<?php

namespace App\Console\Commands;

use App\Services\Telemetry\TelemetryQueryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TelemetryQueryCommand extends Command
{
    protected $signature = 'telemetry:query 
                           {--type=events : Type of data to query (events, metrics, health, performance, chains)}
                           {--correlation-id= : Filter by correlation ID}
                           {--component= : Filter by component}
                           {--event-type= : Filter by event type}
                           {--level= : Filter by log level}
                           {--time-range= : Time range (1h, 6h, 24h, 7d, 30d)}
                           {--start= : Start date (YYYY-MM-DD HH:MM:SS)}
                           {--end= : End date (YYYY-MM-DD HH:MM:SS)}
                           {--limit=100 : Number of results to return}
                           {--format=table : Output format (table, json, csv)}
                           {--export= : Export to file path}
                           {--search= : Search in message content}';

    protected $description = 'Query telemetry data with various filters and output options';

    protected TelemetryQueryService $queryService;

    public function __construct(TelemetryQueryService $queryService)
    {
        parent::__construct();
        $this->queryService = $queryService;
    }

    public function handle(): int
    {
        $type = $this->option('type');
        $filters = $this->buildFilters();
        $limit = (int) $this->option('limit');
        $format = $this->option('format');

        try {
            $data = match ($type) {
                'events' => $this->queryEvents($filters, $limit),
                'metrics' => $this->queryMetrics($filters, $limit),
                'health' => $this->queryHealth($filters),
                'performance' => $this->queryPerformance($filters),
                'chains' => $this->queryChains($filters),
                'stats' => $this->queryStats($filters),
                default => throw new \InvalidArgumentException("Unknown query type: {$type}")
            };

            $this->outputData($data, $format, $type);

            if ($exportPath = $this->option('export')) {
                $this->exportData($data, $exportPath, $format);
            }

        } catch (\Exception $e) {
            $this->error("Query failed: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function buildFilters(): array
    {
        $filters = [];

        if ($correlationId = $this->option('correlation-id')) {
            $filters['correlation_id'] = $correlationId;
        }

        if ($component = $this->option('component')) {
            $filters['component'] = $component;
        }

        if ($eventType = $this->option('event-type')) {
            $filters['event_type'] = $eventType;
        }

        if ($level = $this->option('level')) {
            $filters['level'] = $level;
        }

        if ($search = $this->option('search')) {
            $filters['search'] = $search;
        }

        // Handle time range
        if ($timeRange = $this->option('time-range')) {
            $filters['time_range'] = $this->parseTimeRange($timeRange);
        } elseif ($start = $this->option('start')) {
            $end = $this->option('end') ?? now()->toDateTimeString();
            $filters['time_range'] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $filters;
    }

    private function parseTimeRange(string $range): array
    {
        $end = now();

        $start = match ($range) {
            '1h' => $end->copy()->subHour(),
            '6h' => $end->copy()->subHours(6),
            '24h' => $end->copy()->subDay(),
            '7d' => $end->copy()->subWeek(),
            '30d' => $end->copy()->subMonth(),
            default => throw new \InvalidArgumentException("Invalid time range: {$range}")
        };

        return [
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
        ];
    }

    private function queryEvents(array $filters, int $limit): array
    {
        $events = $this->queryService->queryEvents($filters, $limit);

        return [
            'type' => 'events',
            'count' => $events->count(),
            'data' => $events->map(function ($event) {
                return [
                    'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
                    'correlation_id' => $event->correlation_id,
                    'event_type' => $event->event_type,
                    'component' => $event->component,
                    'level' => $event->level,
                    'message' => $event->message,
                    'duration_ms' => $event->getDurationMs(),
                ];
            })->toArray(),
        ];
    }

    private function queryMetrics(array $filters, int $limit): array
    {
        $metrics = $this->queryService->queryMetrics($filters, $limit);

        return [
            'type' => 'metrics',
            'count' => $metrics->count(),
            'data' => $metrics->map(function ($metric) {
                return [
                    'timestamp' => $metric->timestamp->format('Y-m-d H:i:s'),
                    'metric_name' => $metric->metric_name,
                    'component' => $metric->component,
                    'value' => $metric->value,
                    'type' => $metric->metric_type,
                    'labels' => $metric->labels,
                ];
            })->toArray(),
        ];
    }

    private function queryHealth(array $filters): array
    {
        return [
            'type' => 'health',
            'data' => $this->queryService->getHealthStatus($filters),
        ];
    }

    private function queryPerformance(array $filters): array
    {
        return [
            'type' => 'performance',
            'data' => $this->queryService->getPerformanceAnalysis($filters),
        ];
    }

    private function queryChains(array $filters): array
    {
        $chainId = $filters['correlation_id'] ?? null;

        return [
            'type' => 'chains',
            'data' => $this->queryService->getCorrelationChainAnalysis($chainId),
        ];
    }

    private function queryStats(array $filters): array
    {
        return [
            'type' => 'statistics',
            'data' => $this->queryService->getEventStatistics($filters),
        ];
    }

    private function outputData(array $data, string $format, string $type): void
    {
        match ($format) {
            'json' => $this->outputJson($data),
            'table' => $this->outputTable($data, $type),
            'csv' => $this->outputCsv($data),
            default => throw new \InvalidArgumentException("Unknown format: {$format}")
        };
    }

    private function outputJson(array $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function outputTable(array $data, string $type): void
    {
        if (! isset($data['data'])) {
            $this->info('No data to display');

            return;
        }

        switch ($type) {
            case 'events':
                $this->outputEventsTable($data['data']);
                break;
            case 'metrics':
                $this->outputMetricsTable($data['data']);
                break;
            case 'health':
                $this->outputHealthTable($data['data']);
                break;
            case 'performance':
                $this->outputPerformanceTable($data['data']);
                break;
            case 'stats':
                $this->outputStatsTable($data['data']);
                break;
            default:
                $this->outputJson($data);
        }
    }

    private function outputEventsTable(array $events): void
    {
        if (empty($events)) {
            $this->info('No events found');

            return;
        }

        $headers = ['Timestamp', 'Type', 'Component', 'Level', 'Duration', 'Message'];
        $rows = array_map(function ($event) {
            return [
                $event['timestamp'],
                $event['event_type'],
                $event['component'],
                $event['level'],
                $event['duration_ms'] ? number_format($event['duration_ms'], 1).'ms' : 'N/A',
                substr($event['message'] ?? '', 0, 50).(strlen($event['message'] ?? '') > 50 ? '...' : ''),
            ];
        }, $events);

        $this->table($headers, $rows);
    }

    private function outputMetricsTable(array $metrics): void
    {
        if (empty($metrics)) {
            $this->info('No metrics found');

            return;
        }

        $headers = ['Timestamp', 'Metric', 'Component', 'Value', 'Type'];
        $rows = array_map(function ($metric) {
            return [
                $metric['timestamp'],
                $metric['metric_name'],
                $metric['component'],
                number_format($metric['value'], 2),
                $metric['type'],
            ];
        }, $metrics);

        $this->table($headers, $rows);
    }

    private function outputHealthTable(array $health): void
    {
        if (empty($health['components'])) {
            $this->info('No health data found');

            return;
        }

        $this->info('Overall Health: '.($health['overall_health'] ? '✅ Healthy' : '❌ Unhealthy'));
        $this->line("Total Checks: {$health['total_checks']}");
        $this->line("Healthy Checks: {$health['healthy_checks']}");
        $this->line('');

        $headers = ['Component', 'Status', 'Success Rate', 'Avg Response Time', 'Last Check'];
        $rows = array_map(function ($component) {
            return [
                $component['component'],
                $component['is_healthy'] ? '✅ Healthy' : '❌ Unhealthy',
                number_format($component['success_rate'], 1).'%',
                $component['avg_response_time'] ? number_format($component['avg_response_time'], 1).'ms' : 'N/A',
                Carbon::parse($component['last_check'])->format('Y-m-d H:i:s'),
            ];
        }, $health['components']);

        $this->table($headers, $rows);
    }

    private function outputPerformanceTable(array $performance): void
    {
        $this->info('Performance Analysis');
        $this->line('Average Duration: '.number_format($performance['avg_duration_ms'], 1).'ms');
        $this->line('P95 Duration: '.number_format($performance['p95_duration_ms'] ?? 0, 1).'ms');
        $this->line('Average Memory: '.number_format($performance['avg_memory_usage_mb'], 1).'MB');
        $this->line('');

        if (! empty($performance['performance_distribution'])) {
            $this->info('Performance Distribution:');
            foreach ($performance['performance_distribution'] as $class => $count) {
                $this->line("  {$class}: {$count}");
            }
        }
    }

    private function outputStatsTable(array $stats): void
    {
        $this->info("Total Events: {$stats['total_events']}");
        $this->line('Error Rate: '.number_format($stats['error_rate'], 2).'%');
        $this->line('');

        if (! empty($stats['events_by_type'])) {
            $this->info('Events by Type:');
            foreach ($stats['events_by_type'] as $type => $count) {
                $this->line("  {$type}: {$count}");
            }
        }
    }

    private function outputCsv(array $data): void
    {
        // For CSV output, convert data to flat structure
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $row) {
                $this->line(implode(',', array_values($row)));
            }
        }
    }

    private function exportData(array $data, string $path, string $format): void
    {
        $content = match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'csv' => $this->convertToCsv($data),
            default => json_encode($data, JSON_PRETTY_PRINT)
        };

        file_put_contents($path, $content);
        $this->info("Data exported to: {$path}");
    }

    private function convertToCsv(array $data): string
    {
        if (! isset($data['data']) || empty($data['data'])) {
            return '';
        }

        $first = reset($data['data']);
        $headers = array_keys($first);
        $csv = implode(',', $headers)."\n";

        foreach ($data['data'] as $row) {
            $values = array_map(function ($value) {
                return is_array($value) ? json_encode($value) : $value;
            }, array_values($row));
            $csv .= implode(',', $values)."\n";
        }

        return $csv;
    }
}
