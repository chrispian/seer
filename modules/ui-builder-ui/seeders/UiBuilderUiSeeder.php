<?php

namespace HollisLabs\UiBuilder\Database\Seeders;

use Illuminate\Database\Seeder;

class UiBuilderUiSeeder extends Seeder
{
    /**
     * Seed the UI Builder UI management interface.
     * 
     * This seeder creates:
     * 1. Datasource entries for fe_ui_datasources table (UiPage, UiComponent, UiModule)
     * 2. Page configurations for managing pages and components
     * 3. Module definition for core.ui-builder
     * 
     * All seeders use updateOrCreate() to be idempotent - safe to run multiple times.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('  UI Builder UI - Management Interface');
        $this->command->info('===========================================');
        $this->command->info('');

        $this->call([
            UiBuilderDatasourcesSeeder::class,
            UiBuilderPagesSeeder::class,
            UiBuilderModuleSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('Clearing DataSource cache...');
        
        // Clear the DataSourceResolver cache so new datasources are immediately available
        \Illuminate\Support\Facades\Cache::flush();
        
        $this->command->info('✓ DataSource cache cleared');
        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('  ✓ UI Builder UI seeded successfully');
        $this->command->info('===========================================');
        $this->command->info('');
        $this->command->info('Available pages:');
        $this->command->info('  - page.ui-builder.pages.list');
        $this->command->info('  - page.ui-builder.components.list');
        $this->command->info('');
        $this->command->info('Module: core.ui-builder');
        $this->command->info('');
    }
}
