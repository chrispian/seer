<?php

namespace App\Console\Commands\Ingest;

use App\Services\Readwise\ReadwiseReaderImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ReadwiseReaderSyncCommand extends Command
{
    protected $signature = 'readwise:reader:sync
        {--since= : ISO8601 timestamp to fetch documents updated after}
        {--cursor= : Start from a specific Readwise page cursor}
        {--dry-run : Parse documents without writing fragments}';

    protected $description = 'Sync Readwise Reader documents (articles, RSS, etc.) into Fragments Engine. Handles rate limits by stopping when limit is approached.';

    public function handle(ReadwiseReaderImportService $service): int
    {
        $since = $this->option('since');
        $cursor = $this->option('cursor');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $stats = $service->import($since ?: null, $cursor ?: null, $dryRun);
        } catch (\Throwable $e) {
            $this->error('Readwise Reader sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->outputSummary($stats);

        if ($dryRun) {
            $this->warn('Dry-run complete. No fragments were created.');
        }

        if (Arr::get($stats, 'rate_limited')) {
            $this->warn('Rate limit approached. Import stopped to prevent hitting API limits.');
            $this->info('Run this command again tomorrow to continue importing.');
            $this->newLine();
            $this->info('Or schedule it with: php artisan schedule:work');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function outputSummary(array $stats): void
    {
        $this->info('Readwise Reader Sync Summary');

        $rows = [
            ['Documents (total)', Arr::get($stats, 'documents_total', 0)],
            ['Documents imported', Arr::get($stats, 'documents_imported', 0)],
            ['Documents skipped', Arr::get($stats, 'documents_skipped', 0)],
            ['Rate limited', Arr::get($stats, 'rate_limited') ? 'Yes' : 'No'],
            ['Last cursor', Arr::get($stats, 'last_cursor') ?? '—'],
            ['Last updated', Arr::get($stats, 'last_updated_at') ?? '—'],
        ];

        $this->table(['Metric', 'Value'], $rows);
    }
}
