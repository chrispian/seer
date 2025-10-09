<?php

namespace App\Console\Commands;

use App\Services\Readwise\ReadwiseImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ReadwiseSyncCommand extends Command
{
    protected $signature = 'readwise:sync
        {--since= : ISO8601 timestamp to fetch highlights updated after}
        {--cursor= : Start from a specific Readwise page cursor}
        {--dry-run : Parse highlights without writing fragments}';

    protected $description = 'Sync Readwise highlights into Fragments Engine.';

    public function handle(ReadwiseImportService $service): int
    {
        $since = $this->option('since');
        $cursor = $this->option('cursor');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $stats = $service->import($since ?: null, $cursor ?: null, $dryRun);
        } catch (\Throwable $e) {
            $this->error('Readwise sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->outputSummary($stats);

        if ($dryRun) {
            $this->warn('Dry-run complete. No fragments were created.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function outputSummary(array $stats): void
    {
        $this->info('Readwise Sync Summary');

        $rows = [
            ['Highlights (total)', Arr::get($stats, 'highlights_total', 0)],
            ['Highlights imported', Arr::get($stats, 'highlights_imported', 0)],
            ['Highlights skipped', Arr::get($stats, 'highlights_skipped', 0)],
            ['Last cursor', Arr::get($stats, 'last_cursor') ?? '—'],
        ];

        $this->table(['Metric', 'Value'], $rows);
    }
}
