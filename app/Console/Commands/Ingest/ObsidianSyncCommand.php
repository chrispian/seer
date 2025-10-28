<?php

namespace App\Console\Commands\Ingest;

use App\Services\Obsidian\ObsidianImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ObsidianSyncCommand extends Command
{
    protected $signature = 'obsidian:sync
        {--vault-path= : Path to Obsidian vault directory}
        {--dry-run : Preview import without writing fragments}
        {--enrich : Run AI enrichment pipeline}
        {--force : Force re-import all files regardless of modification time}';

    protected $description = 'Sync Obsidian vault notes into Fragments Engine.';

    public function handle(ObsidianImportService $service): int
    {
        $vaultPath = $this->option('vault-path');
        $dryRun = (bool) $this->option('dry-run');
        $enrichFlag = $this->option('enrich');
        $force = (bool) $this->option('force');

        $enrich = $enrichFlag !== null ? (bool) $enrichFlag : null;

        try {
            $stats = $service->import($vaultPath ?: null, $dryRun, $enrich, $force);
        } catch (\Throwable $e) {
            $this->error('Obsidian sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->outputSummary($stats);

        if ($dryRun) {
            $this->warn('Dry-run complete. No fragments were created or updated.');
        }

        return self::SUCCESS;
    }

    private function outputSummary(array $stats): void
    {
        $this->info('Obsidian Sync Summary');

        $rows = [
            ['Files (total)', Arr::get($stats, 'files_total', 0)],
            ['Files imported', Arr::get($stats, 'files_imported', 0)],
            ['Files updated', Arr::get($stats, 'files_updated', 0)],
            ['Files skipped', Arr::get($stats, 'files_skipped', 0)],
            ['Force mode', Arr::get($stats, 'force') ? 'Enabled' : 'Disabled'],
            ['AI enrichment', Arr::get($stats, 'enrich') ? 'Enabled' : 'Disabled'],
        ];

        $this->table(['Metric', 'Value'], $rows);

        if (! empty($stats['types_inferred'])) {
            $this->newLine();
            $this->info('Type Distribution:');
            $typeRows = [];
            foreach ($stats['types_inferred'] as $type => $count) {
                $typeRows[] = [$type, $count];
            }
            arsort($typeRows);
            $this->table(['Type', 'Count'], $typeRows);
        }

        if (isset($stats['links_resolved']) || isset($stats['links_orphaned'])) {
            $this->newLine();
            $this->info('Link Resolution:');
            $linkRows = [
                ['Links resolved', Arr::get($stats, 'links_resolved', 0)],
                ['Links orphaned', Arr::get($stats, 'links_orphaned', 0)],
            ];
            $this->table(['Metric', 'Count'], $linkRows);
        }
    }
}
