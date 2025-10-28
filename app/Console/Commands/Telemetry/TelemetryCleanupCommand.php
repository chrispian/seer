<?php

namespace App\Console\Commands\Telemetry;

use App\Models\TelemetryCorrelationChain;
use App\Models\TelemetryEvent;
use App\Models\TelemetryHealthCheck;
use App\Models\TelemetryMetric;
use App\Models\TelemetryPerformanceSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TelemetryCleanupCommand extends Command
{
    protected $signature = 'telemetry:cleanup
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Skip confirmation prompts}
                           {--component= : Only cleanup data for specific component}
                           {--older-than= : Custom retention period (e.g., 30d, 7d, 24h)}';

    protected $description = 'Clean up old telemetry data according to retention policies';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');
        $component = $this->option('component');
        $olderThan = $this->option('older-than');

        $this->info('🧹 Telemetry Data Cleanup');
        $this->line('');

        try {
            $deletionPlan = $this->calculateDeletions($component, $olderThan);

            $this->displayDeletionPlan($deletionPlan);

            if ($isDryRun) {
                $this->warn('🔍 DRY RUN - No data will be deleted');

                return 0;
            }

            if (! $isForced && ! $this->confirm('Do you want to proceed with the cleanup?')) {
                $this->info('Cleanup cancelled');

                return 0;
            }

            $results = $this->performCleanup($deletionPlan);
            $this->displayResults($results);

        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function calculateDeletions(?string $component, ?string $olderThan): array
    {
        $retentionPolicies = $this->getRetentionPolicies($olderThan);
        $plan = [];

        // Calculate telemetry events cleanup
        $eventsQuery = TelemetryEvent::where('created_at', '<', $retentionPolicies['raw_events']);
        if ($component) {
            $eventsQuery->where('component', $component);
        }
        $plan['events'] = [
            'table' => 'telemetry_events',
            'count' => $eventsQuery->count(),
            'cutoff_date' => $retentionPolicies['raw_events'],
            'query' => clone $eventsQuery,
        ];

        // Calculate metrics cleanup
        $metricsQuery = TelemetryMetric::where('created_at', '<', $retentionPolicies['aggregated_metrics']);
        if ($component) {
            $metricsQuery->where('component', $component);
        }
        $plan['metrics'] = [
            'table' => 'telemetry_metrics',
            'count' => $metricsQuery->count(),
            'cutoff_date' => $retentionPolicies['aggregated_metrics'],
            'query' => clone $metricsQuery,
        ];

        // Calculate health checks cleanup
        $healthQuery = TelemetryHealthCheck::where('created_at', '<', $retentionPolicies['health_checks']);
        if ($component) {
            $healthQuery->where('component', $component);
        }
        $plan['health_checks'] = [
            'table' => 'telemetry_health_checks',
            'count' => $healthQuery->count(),
            'cutoff_date' => $retentionPolicies['health_checks'],
            'query' => clone $healthQuery,
        ];

        // Calculate performance snapshots cleanup
        $performanceQuery = TelemetryPerformanceSnapshot::where('created_at', '<', $retentionPolicies['performance_snapshots']);
        if ($component) {
            $performanceQuery->where('component', $component);
        }
        $plan['performance_snapshots'] = [
            'table' => 'telemetry_performance_snapshots',
            'count' => $performanceQuery->count(),
            'cutoff_date' => $retentionPolicies['performance_snapshots'],
            'query' => clone $performanceQuery,
        ];

        // Calculate correlation chains cleanup
        $chainsQuery = TelemetryCorrelationChain::where('created_at', '<', $retentionPolicies['correlation_chains']);
        $plan['correlation_chains'] = [
            'table' => 'telemetry_correlation_chains',
            'count' => $chainsQuery->count(),
            'cutoff_date' => $retentionPolicies['correlation_chains'],
            'query' => clone $chainsQuery,
        ];

        return $plan;
    }

    private function getRetentionPolicies(?string $olderThan): array
    {
        if ($olderThan) {
            $cutoff = $this->parseRetentionPeriod($olderThan);

            return [
                'raw_events' => $cutoff,
                'aggregated_metrics' => $cutoff,
                'health_checks' => $cutoff,
                'performance_snapshots' => $cutoff,
                'correlation_chains' => $cutoff,
            ];
        }

        return [
            'raw_events' => now()->subDays(config('telemetry.retention.raw_events_days', 14)),
            'aggregated_metrics' => now()->subDays(config('telemetry.retention.aggregated_metrics_days', 90)),
            'health_checks' => now()->subDays(config('telemetry.retention.health_checks_days', 30)),
            'performance_snapshots' => now()->subDays(config('telemetry.retention.performance_snapshots_days', 30)),
            'correlation_chains' => now()->subDays(config('telemetry.retention.correlation_chains_days', 30)),
        ];
    }

    private function parseRetentionPeriod(string $period): Carbon
    {
        if (preg_match('/^(\d+)([hdw])$/', $period, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];

            return match ($unit) {
                'h' => now()->subHours($value),
                'd' => now()->subDays($value),
                'w' => now()->subWeeks($value),
                default => throw new \InvalidArgumentException("Invalid time unit: {$unit}")
            };
        }

        throw new \InvalidArgumentException("Invalid retention period format: {$period}");
    }

    private function displayDeletionPlan(array $plan): void
    {
        $this->info('📋 Cleanup Plan:');
        $this->line('');

        $totalRecords = 0;
        foreach ($plan as $type => $details) {
            $count = $details['count'];
            $totalRecords += $count;
            $cutoff = $details['cutoff_date']->format('Y-m-d H:i:s');

            $status = $count > 0 ? '🗑️' : '✅';
            $this->line("{$status} {$details['table']}: {$count} records (older than {$cutoff})");
        }

        $this->line('');
        $this->info("Total records to delete: {$totalRecords}");
        $this->line('');
    }

    private function performCleanup(array $plan): array
    {
        $results = [];
        $totalDeleted = 0;

        $this->info('🚀 Starting cleanup...');

        foreach ($plan as $type => $details) {
            if ($details['count'] === 0) {
                $this->line("⏭️ Skipping {$type} (no records to delete)");
                $results[$type] = 0;

                continue;
            }

            $this->info("🧹 Cleaning up {$type}...");

            $startTime = microtime(true);

            try {
                DB::transaction(function () use ($details, &$deleted) {
                    $deleted = $details['query']->delete();
                });

                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);

                $results[$type] = $details['count']; // Use planned count as actual
                $totalDeleted += $details['count'];

                $this->line("  ✅ Deleted {$details['count']} records in {$duration}ms");

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to cleanup {$type}: {$e->getMessage()}");
                $results[$type] = 0;
            }
        }

        $results['total'] = $totalDeleted;

        return $results;
    }

    private function displayResults(array $results): void
    {
        $this->line('');
        $this->info('✨ Cleanup completed!');
        $this->line('');

        foreach ($results as $type => $count) {
            if ($type === 'total') {
                continue;
            }

            if ($count > 0) {
                $this->line("✅ {$type}: {$count} records deleted");
            } else {
                $this->line("⏭️ {$type}: No records deleted");
            }
        }

        $this->line('');
        $this->info("🎉 Total records cleaned up: {$results['total']}");

        // Show storage space information
        $this->showStorageInfo();
    }

    private function showStorageInfo(): void
    {
        try {
            $tables = [
                'telemetry_events',
                'telemetry_metrics',
                'telemetry_health_checks',
                'telemetry_performance_snapshots',
                'telemetry_correlation_chains',
            ];

            $this->line('');
            $this->info('📊 Current table sizes:');

            $driver = DB::getDriverName();

            foreach ($tables as $table) {
                if ($driver === 'pgsql') {
                    $size = DB::select('SELECT
                        ROUND(pg_total_relation_size(?) / 1024.0 / 1024.0, 2) AS mb
                        FROM pg_class WHERE relname = ?', [$table, $table]);
                } else {
                    $size = DB::select('SELECT
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS MB
                        FROM information_schema.TABLES
                        WHERE table_schema = DATABASE()
                        AND table_name = ?', [$table]);
                }

                if (! empty($size)) {
                    $sizeInMB = $driver === 'pgsql' ? ($size[0]->mb ?? 0) : ($size[0]->MB ?? 0);
                    $this->line("  {$table}: {$sizeInMB} MB");
                }
            }
        } catch (\Exception $e) {
            $this->warn("Could not retrieve storage information: {$e->getMessage()}");
        }
    }
}
