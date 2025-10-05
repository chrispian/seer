<?php

namespace App\Services\Telemetry;

use App\Models\TelemetryCorrelationChain;
use App\Models\TelemetryEvent;
use App\Models\TelemetryHealthCheck;
use App\Models\TelemetryMetric;
use App\Models\TelemetryPerformanceSnapshot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TelemetryQueryService
{
    private int $defaultLimit;

    private int $maxLimit;

    private int $cacheTtl;

    public function __construct()
    {
        $this->defaultLimit = config('telemetry.query.default_limit', 100);
        $this->maxLimit = config('telemetry.query.max_limit', 10000);
        $this->cacheTtl = config('telemetry.query.cache_ttl_minutes', 15);
    }

    /**
     * Query telemetry events with filters
     */
    public function queryEvents(array $filters = [], ?int $limit = null): Collection
    {
        $limit = min($limit ?? $this->defaultLimit, $this->maxLimit);

        $query = TelemetryEvent::query()
            ->orderBy('timestamp', 'desc');

        $this->applyEventFilters($query, $filters);

        return $query->limit($limit)->get();
    }

    /**
     * Query events by correlation ID
     */
    public function getEventsByCorrelationId(string $correlationId): Collection
    {
        return TelemetryEvent::where('correlation_id', $correlationId)
            ->orderBy('timestamp', 'asc')
            ->get();
    }

    /**
     * Query events within a time range
     */
    public function getEventsInTimeRange(Carbon $start, Carbon $end, array $filters = []): Collection
    {
        $query = TelemetryEvent::timeRange($start, $end)
            ->orderBy('timestamp', 'desc');

        $this->applyEventFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get event statistics
     */
    public function getEventStatistics(array $filters = []): array
    {
        $cacheKey = 'telemetry_event_stats_'.md5(serialize($filters));

        return Cache::remember($cacheKey, $this->cacheTtl * 60, function () use ($filters) {
            $baseQuery = TelemetryEvent::query();
            $this->applyEventFilters($baseQuery, $filters);

            $stats = [
                'total_events' => (clone $baseQuery)->count(),
                'events_by_type' => (clone $baseQuery)->select('event_type', DB::raw('count(*) as count'))
                    ->groupBy('event_type')
                    ->pluck('count', 'event_type'),
                'events_by_level' => (clone $baseQuery)->select('level', DB::raw('count(*) as count'))
                    ->groupBy('level')
                    ->pluck('count', 'level'),
                'events_by_component' => (clone $baseQuery)->select('component', DB::raw('count(*) as count'))
                    ->groupBy('component')
                    ->pluck('count', 'component'),
                'error_rate' => $this->calculateErrorRate(clone $baseQuery),
                'recent_activity' => $this->getRecentActivity(clone $baseQuery),
            ];

            return $stats;
        });
    }

    /**
     * Query telemetry metrics
     */
    public function queryMetrics(array $filters = [], ?int $limit = null): Collection
    {
        $limit = min($limit ?? $this->defaultLimit, $this->maxLimit);

        $query = TelemetryMetric::query()
            ->orderBy('timestamp', 'desc');

        $this->applyMetricFilters($query, $filters);

        return $query->limit($limit)->get();
    }

    /**
     * Get metric aggregations
     */
    public function getMetricAggregations(string $metricName, string $aggregation, array $filters = []): array
    {
        $query = TelemetryMetric::where('metric_name', $metricName);
        $this->applyMetricFilters($query, $filters);

        $result = match ($aggregation) {
            'avg' => $query->avg('value'),
            'sum' => $query->sum('value'),
            'min' => $query->min('value'),
            'max' => $query->max('value'),
            'count' => $query->count(),
            'percentiles' => $this->calculatePercentiles($query),
            default => null
        };

        return [
            'metric_name' => $metricName,
            'aggregation' => $aggregation,
            'result' => $result,
            'filters' => $filters,
        ];
    }

    /**
     * Get time series data for a metric
     */
    public function getMetricTimeSeries(string $metricName, string $interval = '1h', array $filters = []): array
    {
        $query = TelemetryMetric::where('metric_name', $metricName);
        $this->applyMetricFilters($query, $filters);

        $driver = DB::getDriverName();

        $groupBy = match ($interval) {
            '1m' => $driver === 'pgsql'
                ? "TO_CHAR(timestamp, 'YYYY-MM-DD HH24:MI')"
                : "DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i')",
            '5m' => $driver === 'pgsql'
                ? "TO_CHAR(timestamp, 'YYYY-MM-DD HH24:') || LPAD((EXTRACT(MINUTE FROM timestamp)::int/5)*5::text, 2, '0')"
                : "DATE_FORMAT(timestamp, '%Y-%m-%d %H:') || FLOOR(MINUTE(timestamp)/5)*5",
            '15m' => $driver === 'pgsql'
                ? "TO_CHAR(timestamp, 'YYYY-MM-DD HH24:') || LPAD((EXTRACT(MINUTE FROM timestamp)::int/15)*15::text, 2, '0')"
                : "DATE_FORMAT(timestamp, '%Y-%m-%d %H:') || FLOOR(MINUTE(timestamp)/15)*15",
            '1h' => $driver === 'pgsql'
                ? "TO_CHAR(timestamp, 'YYYY-MM-DD HH24:00')"
                : "DATE_FORMAT(timestamp, '%Y-%m-%d %H:00')",
            '1d' => $driver === 'pgsql'
                ? "TO_CHAR(timestamp, 'YYYY-MM-DD')"
                : "DATE_FORMAT(timestamp, '%Y-%m-%d')",
            default => $driver === 'pgsql'
                ? "TO_CHAR(timestamp, 'YYYY-MM-DD HH24:00')"
                : "DATE_FORMAT(timestamp, '%Y-%m-%d %H:00')"
        };

        return $query->select(
            DB::raw($groupBy.' as time_bucket'),
            DB::raw('AVG(value) as avg_value'),
            DB::raw('MIN(value) as min_value'),
            DB::raw('MAX(value) as max_value'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy(DB::raw($groupBy))
            ->orderBy(DB::raw($groupBy))
            ->get()
            ->toArray();
    }

    /**
     * Get health check status
     */
    public function getHealthStatus(array $filters = []): array
    {
        $query = TelemetryHealthCheck::recent(60); // Last hour

        if (isset($filters['component'])) {
            $query->where('component', $filters['component']);
        }

        $healthChecks = $query->get();

        $componentStatus = $healthChecks->groupBy('component')->map(function ($checks) {
            $latestCheck = $checks->sortByDesc('checked_at')->first();
            $healthyCount = $checks->where('is_healthy', true)->count();
            $totalCount = $checks->count();

            return [
                'component' => $latestCheck->component,
                'is_healthy' => $latestCheck->is_healthy,
                'last_check' => $latestCheck->checked_at,
                'success_rate' => $totalCount > 0 ? ($healthyCount / $totalCount) * 100 : 0,
                'avg_response_time' => $checks->avg('response_time_ms'),
                'latest_error' => $latestCheck->error_message,
            ];
        });

        return [
            'overall_health' => $healthChecks->where('is_healthy', false)->isEmpty(),
            'components' => $componentStatus->values()->toArray(),
            'total_checks' => $healthChecks->count(),
            'healthy_checks' => $healthChecks->where('is_healthy', true)->count(),
        ];
    }

    /**
     * Get performance analysis
     */
    public function getPerformanceAnalysis(array $filters = []): array
    {
        $query = TelemetryPerformanceSnapshot::query();

        if (isset($filters['component'])) {
            $query->where('component', $filters['component']);
        }

        if (isset($filters['time_range'])) {
            $start = Carbon::parse($filters['time_range']['start']);
            $end = Carbon::parse($filters['time_range']['end']);
            $query->timeRange($start, $end);
        } else {
            $query->where('recorded_at', '>=', now()->subHours(24));
        }

        $snapshots = $query->get();

        return [
            'performance_distribution' => $snapshots->groupBy('performance_class')
                ->map->count()
                ->toArray(),
            'avg_duration_ms' => $snapshots->avg('duration_ms'),
            'p95_duration_ms' => $this->calculatePercentile($snapshots->pluck('duration_ms'), 95),
            'avg_memory_usage_mb' => $snapshots->avg('memory_usage_bytes') / 1024 / 1024,
            'slow_operations' => $snapshots->where('performance_class', 'slow')
                ->groupBy('operation')
                ->map->count()
                ->toArray(),
            'critical_operations' => $snapshots->where('performance_class', 'critical')
                ->groupBy('operation')
                ->map->count()
                ->toArray(),
        ];
    }

    /**
     * Get correlation chain analysis
     */
    public function getCorrelationChainAnalysis(?string $chainId = null): array
    {
        if ($chainId) {
            $chain = TelemetryCorrelationChain::where('chain_id', $chainId)->first();
            if (! $chain) {
                return ['error' => 'Chain not found'];
            }

            $events = $this->getEventsByCorrelationId($chain->root_correlation_id);

            return [
                'chain_info' => $chain->toArray(),
                'events' => $events->toArray(),
                'duration_ms' => $chain->getDurationMs(),
                'event_timeline' => $this->buildEventTimeline($events),
            ];
        }

        // Return summary of all chains
        $chains = TelemetryCorrelationChain::recent(24)->get();

        return [
            'total_chains' => $chains->count(),
            'active_chains' => $chains->where('status', 'active')->count(),
            'completed_chains' => $chains->where('status', 'completed')->count(),
            'failed_chains' => $chains->where('status', 'failed')->count(),
            'avg_chain_duration_ms' => $chains->where('status', 'completed')->avg(function ($chain) {
                return $chain->getDurationMs();
            }),
            'depth_distribution' => $chains->groupBy('depth')->map->count()->toArray(),
        ];
    }

    /**
     * Export telemetry data
     */
    public function exportData(array $filters, string $format = 'json'): array
    {
        $events = $this->queryEvents($filters, $this->maxLimit);

        return match ($format) {
            'json' => $events->toArray(),
            'csv' => $this->convertToCsv($events),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}")
        };
    }

    /**
     * Apply filters to event query
     */
    private function applyEventFilters(Builder $query, array $filters): void
    {
        if (isset($filters['correlation_id'])) {
            $query->where('correlation_id', $filters['correlation_id']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['component'])) {
            $query->where('component', $filters['component']);
        }

        if (isset($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (isset($filters['time_range'])) {
            $start = Carbon::parse($filters['time_range']['start']);
            $end = Carbon::parse($filters['time_range']['end']);
            $query->timeRange($start, $end);
        }

        if (isset($filters['search'])) {
            $query->where('message', 'like', '%'.$filters['search'].'%');
        }
    }

    /**
     * Apply filters to metric query
     */
    private function applyMetricFilters(Builder $query, array $filters): void
    {
        if (isset($filters['component'])) {
            $query->where('component', $filters['component']);
        }

        if (isset($filters['metric_type'])) {
            $query->where('metric_type', $filters['metric_type']);
        }

        if (isset($filters['time_range'])) {
            $start = Carbon::parse($filters['time_range']['start']);
            $end = Carbon::parse($filters['time_range']['end']);
            $query->timeRange($start, $end);
        }

        if (isset($filters['labels'])) {
            foreach ($filters['labels'] as $key => $value) {
                $query->whereJsonContains('labels->'.$key, $value);
            }
        }
    }

    /**
     * Calculate error rate
     */
    private function calculateErrorRate(Builder $query): float
    {
        $total = $query->count();
        if ($total === 0) {
            return 0.0;
        }

        $errors = $query->whereIn('level', ['error', 'critical'])->count();

        return ($errors / $total) * 100;
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity(Builder $query): array
    {
        $driver = DB::getDriverName();
        $hourFormat = $driver === 'pgsql'
            ? "TO_CHAR(timestamp, 'YYYY-MM-DD HH24:00')"
            : "DATE_FORMAT(timestamp, '%Y-%m-%d %H:00')";

        return $query->select(
            DB::raw("{$hourFormat} as hour"),
            DB::raw('COUNT(*) as count')
        )
            ->where('timestamp', '>=', now()->subHours(24))
            ->groupBy(DB::raw($hourFormat))
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Calculate percentiles for metrics
     */
    private function calculatePercentiles(Builder $query): array
    {
        $values = $query->pluck('value')->sort()->values();

        return [
            'p50' => $this->calculatePercentile($values, 50),
            'p90' => $this->calculatePercentile($values, 90),
            'p95' => $this->calculatePercentile($values, 95),
            'p99' => $this->calculatePercentile($values, 99),
        ];
    }

    /**
     * Calculate a specific percentile
     */
    private function calculatePercentile(Collection $values, float $percentile): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $index = ($percentile / 100) * ($values->count() - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower == $upper) {
            return $values[$lower];
        }

        return $values[$lower] + (($values[$upper] - $values[$lower]) * ($index - $lower));
    }

    /**
     * Build event timeline
     */
    private function buildEventTimeline(Collection $events): array
    {
        return $events->map(function ($event) {
            return [
                'timestamp' => $event->timestamp->toISOString(),
                'event_name' => $event->event_name,
                'component' => $event->component,
                'level' => $event->level,
                'duration_ms' => $event->getDurationMs(),
                'message' => $event->message,
            ];
        })->toArray();
    }

    /**
     * Convert events to CSV format
     */
    private function convertToCsv(Collection $events): string
    {
        if ($events->isEmpty()) {
            return '';
        }

        $headers = ['timestamp', 'correlation_id', 'event_type', 'component', 'level', 'message'];
        $csv = implode(',', $headers)."\n";

        foreach ($events as $event) {
            $row = [
                $event->timestamp->toISOString(),
                $event->correlation_id,
                $event->event_type,
                $event->component,
                $event->level,
                '"'.str_replace('"', '""', $event->message ?? '').'"',
            ];
            $csv .= implode(',', $row)."\n";
        }

        return $csv;
    }
}
