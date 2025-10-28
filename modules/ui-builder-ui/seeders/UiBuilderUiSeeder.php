<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UiBuilderUiSeeder extends Seeder
{
    /**
     * Seed the UI Builder UI management interface.
     * 
     * This seeder creates:
     * 1. FeType definitions for fe_ui_* tables (UiPage, UiComponent, UiRegistry, UiModule)
     * 2. Datasource entries for fe_ui_datasources table
     * 3. Page configurations for managing pages, components, and registry
     * 4. Module definition for core.ui-builder
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
            UiBuilderTypesSeeder::class,
            UiBuilderDatasourcesSeeder::class,
            UiBuilderPagesSeeder::class,
            UiBuilderModuleSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('Refreshing TypeRegistry cache...');
        
        // Refresh the TypeRegistry cache so the new types are immediately available
        $registry = app(\App\Services\Types\TypeRegistry::class);
        $registry->refreshAll();
        
        $this->command->info('✓ TypeRegistry cache refreshed');
        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('  ✓ UI Builder UI seeded successfully');
        $this->command->info('===========================================');
        $this->command->info('');
        $this->command->info('Available pages:');
        $this->command->info('  - page.ui-builder.pages.list');
        $this->command->info('  - page.ui-builder.components.list');
        $this->command->info('  - page.ui-builder.registry.browser');
        $this->command->info('');
        $this->command->info('Module: core.ui-builder');
        $this->command->info('');
    }
}
