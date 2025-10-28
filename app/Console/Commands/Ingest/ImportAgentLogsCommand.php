<?php

namespace App\Console\Commands\Ingest;

use App\Services\AgentLogImportService;
use Illuminate\Console\Command;

class ImportAgentLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:import
        {--source=* : Specify log sources to import (opencode, claude, codex)}
        {--since= : Only import logs modified since this date (YYYY-MM-DD or relative like "yesterday")}
        {--dry-run : Preview what would be imported without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import agent logs from OpenCode, Claude Desktop, Claude Projects, and Codex CLI into the database';

    public function __construct(
        private readonly AgentLogImportService $importService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sources = $this->option('source');
        $since = $this->option('since');
        $dryRun = $this->option('dry-run');

        // Default to all sources if none specified
        if (empty($sources)) {
            $sources = ['opencode', 'claude', 'codex'];
        }

        $this->info(sprintf(
            'Starting agent log import%s for sources: %s%s',
            $dryRun ? ' (dry run)' : '',
            implode(', ', $sources),
            $since ? " (since: {$since})" : ''
        ));

        $startTime = microtime(true);

        try {
            $stats = $this->importService->import([
                'sources' => $sources,
                'since' => $since,
                'dry_run' => $dryRun,
            ]);

            $duration = round((microtime(true) - $startTime) * 1000);

            $this->displayResults($stats, $duration, $dryRun);

            if (! empty($stats['errors'])) {
                $this->error('Encountered errors during import:');
                foreach ($stats['errors'] as $error) {
                    $this->error(" - {$error}");
                }

                return self::FAILURE;
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            $this->error("Stack trace: {$e->getTraceAsString()}");

            return self::FAILURE;
        }
    }

    /**
     * Display import results
     */
    private function displayResults(array $stats, int $duration, bool $dryRun): void
    {
        $this->line('');
        $this->info('Import Results:');
        $this->line("Files processed: {$stats['files_processed']}");
        $this->line("Log entries imported: {$stats['entries_imported']}");
        $this->line("Entries skipped: {$stats['entries_skipped']}");
        $this->line("Duration: {$duration}ms");

        if ($dryRun) {
            $this->warn('This was a dry run - no data was actually imported');
        }

        if ($stats['entries_imported'] > 0) {
            $this->info("✅ Successfully imported {$stats['entries_imported']} log entries");
        } else {
            $this->comment('No new log entries found to import');
        }
    }
}
