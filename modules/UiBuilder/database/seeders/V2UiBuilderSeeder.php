<?php

namespace Modules\UiBuilder\database\seeders;

use Modules\UiBuilder\app\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class V2UiBuilderSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding UI Builder v2 demo pages...');

        $pages = [
            'page.agent.table.modal',
            'page.model.table.modal',
        ];

        foreach ($pages as $pageKey) {
            $configPath = storage_path("ui-builder/pages/{$pageKey}.json");
            
            if (! File::exists($configPath)) {
                $this->command->warn("Config file not found: {$configPath}");
                $this->command->warn("Run: php artisan ui-builder:export-pages to create config files");
                continue;
            }

            $config = json_decode(File::get($configPath), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->command->error("Invalid JSON in {$pageKey}: " . json_last_error_msg());
                continue;
            }

            // Extract and remove metadata (not stored in config)
            $meta = $config['_meta'] ?? null;
            unset($config['_meta']);

            $page = Page::updateOrCreate(
                ['key' => $pageKey],
                ['config' => $config]
            );
            
            // Verify if config changed
            if ($meta && $meta['hash'] !== $page->hash) {
                $this->command->warn("  Hash mismatch - config was modified");
                $this->command->line("    Expected: " . substr($meta['hash'], 0, 16) . '...');
                $this->command->line("    Got:      " . substr($page->hash, 0, 16) . '...');
            }

            $this->command->info("✓ Seeded page: {$pageKey}");
            $this->command->info("  Version: {$page->version}");
            $this->command->info("  Hash: {$page->hash}");
        }
        
        $this->command->line('');
        $this->command->info('Demo pages available at:');
        foreach ($pages as $pageKey) {
            $this->command->line("  → /v2/pages/{$pageKey}");
        }
    }
}
