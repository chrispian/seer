<?php

namespace App\Console\Commands;

use App\Services\Telemetry\TelemetryQueryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use ZipArchive;

class TelemetryExportCommand extends Command
{
    protected $signature = 'telemetry:export 
                           {--format=json : Export format (json, csv, parquet)}
                           {--output= : Output file path}
                           {--component= : Filter by component}
                           {--event-type= : Filter by event type}
                           {--time-range= : Time range (1h, 6h, 24h, 7d, 30d)}
                           {--start= : Start date (YYYY-MM-DD HH:MM:SS)}
                           {--end= : End date (YYYY-MM-DD HH:MM:SS)}
                           {--compress : Compress the export file}
                           {--split-by= : Split export by (component, event_type, day)}
                           {--include-metrics : Include metrics in export}
                           {--include-health : Include health checks in export}
                           {--include-performance : Include performance snapshots in export}';

    protected $description = 'Export telemetry data for external analysis';

    protected TelemetryQueryService $queryService;

    public function __construct(TelemetryQueryService $queryService)
    {
        parent::__construct();
        $this->queryService = $queryService;
    }

    public function handle(): int
    {
        $format = $this->option('format');
        $output = $this->option('output');
        $compress = $this->option('compress');
        $splitBy = $this->option('split-by');

        $this->info('📦 Telemetry Data Export');
        $this->line('');

        try {
            $filters = $this->buildFilters();
            $this->displayExportPlan($filters, $format);

            if (! $this->confirm('Do you want to proceed with the export?')) {
                $this->info('Export cancelled');

                return 0;
            }

            $exportPath = $output ?? $this->generateOutputPath($format);
            $this->ensureOutputDirectory($exportPath);

            if ($splitBy) {
                $this->performSplitExport($filters, $format, $exportPath, $splitBy, $compress);
            } else {
                $this->performSingleExport($filters, $format, $exportPath, $compress);
            }

            $this->info('✅ Export completed successfully!');
            $this->line("📁 Output location: {$exportPath}");

        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function buildFilters(): array
    {
        $filters = [];

        if ($component = $this->option('component')) {
            $filters['component'] = $component;
        }

        if ($eventType = $this->option('event-type')) {
            $filters['event_type'] = $eventType;
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
        } else {
            // Default to last 24 hours
            $filters['time_range'] = [
                'start' => now()->subDay()->toDateTimeString(),
                'end' => now()->toDateTimeString(),
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

    private function displayExportPlan(array $filters, string $format): void
    {
        $this->info('📋 Export Plan:');
        $this->line('');

        // Display filters
        foreach ($filters as $key => $value) {
            if ($key === 'time_range') {
                $this->line("Time Range: {$value['start']} to {$value['end']}");
            } else {
                $this->line("{$key}: {$value}");
            }
        }

        $this->line("Format: {$format}");

        // Show what will be included
        $includes = [];
        $includes[] = 'Events';

        if ($this->option('include-metrics')) {
            $includes[] = 'Metrics';
        }

        if ($this->option('include-health')) {
            $includes[] = 'Health Checks';
        }

        if ($this->option('include-performance')) {
            $includes[] = 'Performance Snapshots';
        }

        $this->line('Data Types: '.implode(', ', $includes));
        $this->line('');

        // Estimate data size
        $eventCount = $this->queryService->queryEvents($filters, 1)->count();
        $this->line("Estimated events: {$eventCount}");
    }

    private function generateOutputPath(string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "telemetry_export_{$timestamp}.{$format}";

        return storage_path("telemetry/exports/{$filename}");
    }

    private function ensureOutputDirectory(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function performSingleExport(array $filters, string $format, string $outputPath, bool $compress): void
    {
        $this->info('🚀 Starting export...');

        $progressBar = $this->output->createProgressBar(4); // 4 data types max
        $progressBar->start();

        $exportData = [];

        // Export events
        $events = $this->queryService->queryEvents($filters, 50000); // Large limit for export
        $exportData['events'] = $events->map(function ($event) {
            return [
                'timestamp' => $event->timestamp->toISOString(),
                'correlation_id' => $event->correlation_id,
                'event_type' => $event->event_type,
                'event_name' => $event->event_name,
                'component' => $event->component,
                'operation' => $event->operation,
                'level' => $event->level,
                'message' => $event->message,
                'metadata' => $event->metadata,
                'context' => $event->context,
                'performance' => $event->performance,
            ];
        })->toArray();
        $progressBar->advance();

        // Export metrics if requested
        if ($this->option('include-metrics')) {
            $metrics = $this->queryService->queryMetrics($filters, 50000);
            $exportData['metrics'] = $metrics->map(function ($metric) {
                return [
                    'timestamp' => $metric->timestamp->toISOString(),
                    'metric_name' => $metric->metric_name,
                    'component' => $metric->component,
                    'metric_type' => $metric->metric_type,
                    'value' => $metric->value,
                    'labels' => $metric->labels,
                    'aggregation_period' => $metric->aggregation_period,
                ];
            })->toArray();
        }
        $progressBar->advance();

        // Export health checks if requested
        if ($this->option('include-health')) {
            $health = $this->queryService->getHealthStatus($filters);
            $exportData['health'] = $health;
        }
        $progressBar->advance();

        // Export performance snapshots if requested
        if ($this->option('include-performance')) {
            $performance = $this->queryService->getPerformanceAnalysis($filters);
            $exportData['performance'] = $performance;
        }
        $progressBar->advance();

        $progressBar->finish();
        $this->line('');

        // Write export data
        $this->writeExportData($exportData, $format, $outputPath);

        // Compress if requested
        if ($compress) {
            $this->compressFile($outputPath);
        }

        $this->displayExportStats($exportData, $outputPath);
    }

    private function performSplitExport(array $filters, string $format, string $outputPath, string $splitBy, bool $compress): void
    {
        $this->info("🚀 Starting split export by {$splitBy}...");

        $baseDir = pathinfo($outputPath, PATHINFO_DIRNAME);
        $baseName = pathinfo($outputPath, PATHINFO_FILENAME);

        // Get unique values for splitting
        $splitValues = $this->getSplitValues($filters, $splitBy);

        $progressBar = $this->output->createProgressBar(count($splitValues));
        $progressBar->start();

        foreach ($splitValues as $value) {
            $splitFilters = $filters;
            $splitFilters[$splitBy] = $value;

            $splitOutputPath = "{$baseDir}/{$baseName}_{$splitBy}_{$value}.{$format}";

            $this->performSingleExport($splitFilters, $format, $splitOutputPath, false);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');

        // Create combined zip if compress is requested
        if ($compress) {
            $this->createSplitArchive($baseDir, $baseName, $splitBy, $format);
        }
    }

    private function getSplitValues(array $filters, string $splitBy): array
    {
        switch ($splitBy) {
            case 'component':
                return $this->queryService->queryEvents($filters, 10000)
                    ->pluck('component')
                    ->unique()
                    ->values()
                    ->toArray();

            case 'event_type':
                return $this->queryService->queryEvents($filters, 10000)
                    ->pluck('event_type')
                    ->unique()
                    ->values()
                    ->toArray();

            case 'day':
                $start = Carbon::parse($filters['time_range']['start']);
                $end = Carbon::parse($filters['time_range']['end']);
                $days = [];

                for ($date = $start->copy(); $date <= $end; $date->addDay()) {
                    $days[] = $date->format('Y-m-d');
                }

                return $days;

            default:
                throw new \InvalidArgumentException("Unsupported split type: {$splitBy}");
        }
    }

    private function writeExportData(array $data, string $format, string $path): void
    {
        $content = match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'csv' => $this->convertToCsv($data),
            'parquet' => $this->convertToParquet($data),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };

        file_put_contents($path, $content);
    }

    private function convertToCsv(array $data): string
    {
        $csv = '';

        if (isset($data['events']) && ! empty($data['events'])) {
            $csv .= "# EVENTS\n";
            $events = $data['events'];
            $headers = array_keys($events[0]);
            $csv .= implode(',', $headers)."\n";

            foreach ($events as $event) {
                $row = array_map(function ($value) {
                    if (is_array($value) || is_object($value)) {
                        return '"'.str_replace('"', '""', json_encode($value)).'"';
                    }

                    return '"'.str_replace('"', '""', (string) $value).'"';
                }, $event);
                $csv .= implode(',', $row)."\n";
            }
            $csv .= "\n";
        }

        if (isset($data['metrics']) && ! empty($data['metrics'])) {
            $csv .= "# METRICS\n";
            $metrics = $data['metrics'];
            $headers = array_keys($metrics[0]);
            $csv .= implode(',', $headers)."\n";

            foreach ($metrics as $metric) {
                $row = array_map(function ($value) {
                    if (is_array($value) || is_object($value)) {
                        return '"'.str_replace('"', '""', json_encode($value)).'"';
                    }

                    return '"'.str_replace('"', '""', (string) $value).'"';
                }, $metric);
                $csv .= implode(',', $row)."\n";
            }
        }

        return $csv;
    }

    private function convertToParquet(array $data): string
    {
        // For now, convert to JSON as parquet requires additional libraries
        // In a real implementation, you'd use a library like php-parquet
        $this->warn('Parquet format not fully implemented, using JSON instead');

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function compressFile(string $path): void
    {
        $zip = new ZipArchive;
        $zipPath = $path.'.zip';

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $zip->addFile($path, basename($path));
            $zip->close();

            // Remove original file after compression
            unlink($path);

            $this->info("📦 File compressed to: {$zipPath}");
        } else {
            $this->warn('Failed to create compressed file');
        }
    }

    private function createSplitArchive(string $baseDir, string $baseName, string $splitBy, string $format): void
    {
        $zip = new ZipArchive;
        $zipPath = "{$baseDir}/{$baseName}_split_by_{$splitBy}.zip";

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $files = glob("{$baseDir}/{$baseName}_{$splitBy}_*.{$format}");

            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }

            $zip->close();

            // Remove individual files after archiving
            foreach ($files as $file) {
                unlink($file);
            }

            $this->info("📦 Split files archived to: {$zipPath}");
        }
    }

    private function displayExportStats(array $data, string $path): void
    {
        $this->line('');
        $this->info('📊 Export Statistics:');

        if (isset($data['events'])) {
            $this->line('Events exported: '.count($data['events']));
        }

        if (isset($data['metrics'])) {
            $this->line('Metrics exported: '.count($data['metrics']));
        }

        $fileSize = filesize($path);
        $this->line('File size: '.$this->formatBytes($fileSize));
    }

    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }
}
