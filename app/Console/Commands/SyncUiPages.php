<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use HollisLabs\UiBuilder\Models\Page;
use Illuminate\Support\Facades\File;

class SyncUiPages extends Command
{
    protected $signature = 'ui-builder:sync 
                            {--check : Only check for drift, do not prompt}
                            {--force-export : Export DB to JSON (DB wins)}
                            {--force-import : Import JSON to DB (JSON wins)}';

    protected $description = 'Analyze and sync UI Builder pages between JSON files and database';

    public function handle(): int
    {
        $checkOnly = $this->option('check');
        $forceExport = $this->option('force-export');
        $forceImport = $this->option('force-import');

        if ($forceExport && $forceImport) {
            $this->error('Cannot use both --force-export and --force-import');
            return 1;
        }

        $pagesDir = storage_path('ui-builder/pages');
        
        if (!File::exists($pagesDir)) {
            $this->error("Pages directory not found: {$pagesDir}");
            $this->info('Run: php artisan ui-builder:export-pages');
            return 1;
        }

        $jsonFiles = File::glob("{$pagesDir}/*.json");
        $dbPages = Page::all()->keyBy('key');
        
        $this->info('🔍 Analyzing UI Builder Pages...');
        $this->line('');

        $driftCount = 0;
        $missingCount = 0;
        $syncedCount = 0;

        // Check each JSON file
        foreach ($jsonFiles as $filepath) {
            $filename = basename($filepath);
            $pageKey = str_replace('.json', '', $filename);
            
            $jsonConfig = json_decode(File::get($filepath), true);
            
            if (!$jsonConfig) {
                $this->error("✗ {$filename} - Invalid JSON");
                continue;
            }

            $jsonMeta = $jsonConfig['_meta'] ?? null;
            $dbPage = $dbPages->get($pageKey);

            if (!$dbPage) {
                $missingCount++;
                $this->warn("⚠ {$pageKey}");
                $this->line("  Status: Missing from database");
                $jsonVer = $jsonMeta['version'] ?? 'unknown';
                $this->line("  JSON version: {$jsonVer}");
                $this->line('');
                continue;
            }

            // Calculate hash from JSON config content (excluding _meta)
            // Use same encoding as Page model for consistent hashing
            $jsonConfigClean = $jsonConfig;
            unset($jsonConfigClean['_meta']);
            $jsonHash = hash('sha256', json_encode($jsonConfigClean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $jsonVersion = $jsonMeta['version'] ?? 0;
            
            $dbVersion = $dbPage->version;
            $dbHash = $dbPage->hash;

            if ($jsonVersion === $dbVersion && $jsonHash === $dbHash) {
                $syncedCount++;
                $this->info("✓ {$pageKey}");
                $this->line("  Status: In Sync");
                $this->line("  Version: {$dbVersion}");
                $this->line("  Hash: " . substr($dbHash, 0, 16) . '...');
                $this->line('');
                continue;
            }

            // Drift detected
            $driftCount++;
            $this->error("✗ {$pageKey} - DRIFT DETECTED");
            
            $this->line("  JSON:");
            $this->line("    Version: {$jsonVersion}");
            $this->line("    Hash: " . substr($jsonHash, 0, 16) . '...');
            $this->line("    Last synced: " . ($jsonMeta['last_synced'] ?? 'unknown'));
            
            $this->line("  Database:");
            $this->line("    Version: {$dbVersion}");
            $this->line("    Hash: " . substr($dbHash, 0, 16) . '...');
            $this->line("    Last updated: {$dbPage->updated_at}");

            if ($jsonVersion !== $dbVersion) {
                $this->warn("    ⚠ Version mismatch");
            }
            if ($jsonHash !== $dbHash) {
                $this->warn("    ⚠ Config differs");
            }

            $this->line('');
        }

        // Check for DB pages missing from JSON
        foreach ($dbPages as $pageKey => $dbPage) {
            $jsonPath = "{$pagesDir}/{$pageKey}.json";
            if (!File::exists($jsonPath)) {
                $missingCount++;
                $this->warn("⚠ {$pageKey}");
                $this->line("  Status: Missing from JSON");
                $this->line("  DB version: {$dbPage->version}");
                $this->line('');
            }
        }

        // Summary
        $this->info('═══════════════════════════════════════');
        $this->info('Summary:');
        $this->line("  ✓ In Sync: {$syncedCount}");
        
        if ($driftCount > 0) {
            $this->error("  ✗ Drift Detected: {$driftCount}");
        }
        
        if ($missingCount > 0) {
            $this->warn("  ⚠ Missing: {$missingCount}");
        }
        
        $this->info('═══════════════════════════════════════');
        $this->line('');

        // Handle actions
        if ($checkOnly) {
            return ($driftCount > 0 || $missingCount > 0) ? 1 : 0;
        }

        if ($forceExport) {
            $this->info('Force exporting database to JSON...');
            $this->call('ui-builder:export-pages');
            return 0;
        }

        if ($forceImport) {
            $this->info('Force importing JSON to database...');
                    $this->call('db:seed', ['--class' => 'HollisLabs\\UiBuilder\\database\\seeders\\UiBuilderSeeder']);
            return 0;
        }

        if ($driftCount > 0 || $missingCount > 0) {
            $this->line('What would you like to do?');
            $this->line('');
            $this->line('  1. Export DB to JSON (database wins)');
            $this->line('  2. Import JSON to DB (JSON wins)');
            $this->line('  3. Show detailed diff');
            $this->line('  4. Nothing (exit)');
            $this->line('');

            $choice = $this->ask('Choice [1-4]', '4');

            switch ($choice) {
                case '1':
                    $this->info('Exporting database to JSON...');
                    $this->call('ui-builder:export-pages');
                    $this->call('ui-builder:export-pages', ['--cache' => true]);
                    $this->info('✓ Exported and backed up');
                    break;

                case '2':
                    $this->info('Importing JSON to database...');
            $this->call('db:seed', ['--class' => 'HollisLabs\\UiBuilder\\database\\seeders\\UiBuilderSeeder']);
                    $this->info('✓ Imported');
                    break;

                case '3':
                    $this->showDetailedDiff($jsonFiles, $dbPages);
                    break;

                case '4':
                default:
                    $this->info('No action taken');
                    break;
            }
        }

        return 0;
    }

    private function showDetailedDiff(array $jsonFiles, $dbPages): void
    {
        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('Detailed Diff:');
        $this->info('═══════════════════════════════════════');

        foreach ($jsonFiles as $filepath) {
            $pageKey = str_replace('.json', '', basename($filepath));
            $jsonConfig = json_decode(File::get($filepath), true);
            $dbPage = $dbPages->get($pageKey);

            if (!$dbPage) {
                continue;
            }

            // Calculate hash from JSON config content (excluding _meta)
            // Use same encoding as Page model for consistent hashing
            $jsonConfigClean = $jsonConfig;
            unset($jsonConfigClean['_meta']);
            $jsonHash = hash('sha256', json_encode($jsonConfigClean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $dbHash = $dbPage->hash;

            if ($jsonHash !== $dbHash) {
                $this->line('');
                $this->error("Page: {$pageKey}");
                $this->line('JSON hash:  ' . $jsonHash);
                $this->line('DB hash:    ' . $dbHash);
                
                // Try to find specific differences
                $jsonConfigClean = $jsonConfig;
                unset($jsonConfigClean['_meta']);
                
                $dbConfig = $dbPage->config;
                
                $this->line('');
                $this->line('Tip: Use a JSON diff tool to compare:');
                $this->line("  storage/ui-builder/pages/{$pageKey}.json");
                $this->line("  vs database record for '{$pageKey}'");
            }
        }

        $this->line('');
    }
}
