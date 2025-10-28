<?php

namespace App\Console\Commands\Ingest;

use App\Services\Hardcover\HardcoverImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class HardcoverSyncCommand extends Command
{
    protected $signature = 'hardcover:sync
        {--offset= : Start from a specific offset}
        {--dry-run : Parse books without writing fragments}';

    protected $description = 'Sync Hardcover book library into Fragments Engine. Imports up to 500 books per day with rate limiting.';

    public function handle(HardcoverImportService $service): int
    {
        $offset = $this->option('offset') ? (int) $this->option('offset') : null;
        $dryRun = (bool) $this->option('dry-run');

        try {
            $stats = $service->import($offset, $dryRun);
        } catch (\Throwable $e) {
            $this->error('Hardcover sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->outputSummary($stats);

        if ($dryRun) {
            $this->warn('Dry-run complete. No fragments were created.');
        }

        if (Arr::get($stats, 'rate_limited')) {
            $this->warn('Daily limit or rate limit reached. Import stopped.');
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
        $this->info('Hardcover Sync Summary');

        $rows = [
            ['Books (total)', Arr::get($stats, 'books_total', 0)],
            ['Books imported', Arr::get($stats, 'books_imported', 0)],
            ['Books skipped', Arr::get($stats, 'books_skipped', 0)],
            ['Rate limited', Arr::get($stats, 'rate_limited') ? 'Yes' : 'No'],
            ['Last offset', Arr::get($stats, 'last_offset') ?? '—'],
        ];

        $this->table(['Metric', 'Value'], $rows);
    }
}
