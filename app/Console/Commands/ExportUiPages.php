<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use HollisLabs\UiBuilder\Models\Page;
use Illuminate\Support\Facades\File;

class ExportUiPages extends Command
{
    protected $signature = 'ui-builder:export-pages 
                            {--cache : Export to cache folder with timestamp}';

    protected $description = 'Export UI Builder page configs to JSON files';

    public function handle(): int
    {
        $useCache = $this->option('cache');
        
        if ($useCache) {
            $timestamp = now()->format('Y-m-d-His');
            $exportDir = storage_path("ui-builder/cache/{$timestamp}");
        } else {
            $exportDir = storage_path('ui-builder/pages');
        }

        if (!File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $pages = Page::all();
        
        if ($pages->isEmpty()) {
            $this->warn('No pages found in database');
            return 1;
        }

        $this->info("Exporting pages to: {$exportDir}");
        $this->line('');

        foreach ($pages as $page) {
            $filename = "{$page->key}.json";
            $filepath = "{$exportDir}/{$filename}";
            
            // Add metadata to config
            $config = $page->config;
            $config['_meta'] = [
                'version' => $page->version,
                'hash' => $page->hash,
                'last_updated' => $page->updated_at->toIso8601String(),
                'last_synced' => now()->toIso8601String(),
            ];
            
            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            File::put($filepath, $json);
            
            $this->info("✓ Exported: {$filename}");
            $this->line("  Version: {$page->version}");
            $this->line("  Hash: " . substr($page->hash, 0, 16) . '...');
        }

        $this->line('');
        
        if ($useCache) {
            $this->info("Cached to: {$exportDir}");
            $this->comment('These are backups only - do not edit');
        } else {
            $this->info("Exported to: {$exportDir}");
            $this->comment('Edit these files and run: php artisan db:seed --class=HollisLabs\\UiBuilder\\Database\\Seeders\\V2UiBuilderSeeder');
        }

        return 0;
    }
}
