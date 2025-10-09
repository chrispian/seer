<?php

namespace App\Console\Commands;

use App\Services\DocumentationImportService;
use Illuminate\Console\Command;

class ImportDocumentation extends Command
{
    protected $signature = 'documentation:import
                           {path? : Path to documentation directory (defaults to docs/)}
                           {--force : Force reimport even if unchanged}';

    protected $description = 'Import documentation from markdown files into database';

    public function handle(DocumentationImportService $importer)
    {
        $path = $this->argument('path') ?? base_path('docs');

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return Command::FAILURE;
        }

        $this->info("Importing documentation from: {$path}");
        $this->newLine();

        $stats = $importer->importFromDirectory($path);

        $this->newLine();
        $this->info('Import Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $stats['processed']],
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->warn("Encountered {$stats['errors']} errors. Check logs for details.");

            return Command::FAILURE;
        }

        $this->info('✓ Documentation import completed successfully');

        return Command::SUCCESS;
    }
}
