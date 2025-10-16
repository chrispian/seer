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

        $configPath = base_path('delegation/tasks/ui-builder/frontend/page.agent.table.modal.json');
        
        if (! File::exists($configPath)) {
            $this->command->error("Config file not found: {$configPath}");
            return;
        }

        $config = json_decode(File::get($configPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON in config file: ' . json_last_error_msg());
            return;
        }

        $pageKey = $config['id'] ?? 'page.agent.table.modal';

        $page = Page::updateOrCreate(
            ['key' => $pageKey],
            ['layout_tree_json' => $config]
        );

        $this->command->info("✓ Seeded page: {$pageKey}");
        $this->command->info("  Version: {$page->version}");
        $this->command->info("  Hash: {$page->hash}");
        $this->command->line('');
        $this->command->info('Demo page available at:');
        $this->command->line("  → /v2/pages/{$pageKey}");
    }
}
