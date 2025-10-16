<?php

namespace Modules\UiBuilder\database\seeders;

use App\Models\FeUiRegistry;
use App\Models\FeUiFeatureFlag;
use Illuminate\Database\Seeder;

class UiRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFeatureFlags();
        $this->seedRegistry();
    }

    protected function seedFeatureFlags(): void
    {
        $flags = [
            [
                'key' => 'ui.modal_v2',
                'name' => 'Modal V2 System',
                'description' => 'Enable new modal navigation and state management',
                'is_enabled' => true,
                'environment' => null,
            ],
            [
                'key' => 'ui.component_registry',
                'name' => 'Component Registry',
                'description' => 'Enable component registry for dynamic component loading',
                'is_enabled' => true,
                'environment' => null,
            ],
            [
                'key' => 'ui.type_system',
                'name' => 'Type System',
                'description' => 'Enable FE type system for dynamic querying',
                'is_enabled' => true,
                'environment' => null,
            ],
            [
                'key' => 'ui.generic_datasources',
                'name' => 'Generic Data Sources',
                'description' => 'Enable config-based generic data source resolution',
                'is_enabled' => false,
                'environment' => null,
            ],
            [
                'key' => 'ui.shadcn_components',
                'name' => 'Shadcn Component Library',
                'description' => 'Enable full shadcn component parity',
                'is_enabled' => false,
                'environment' => null,
            ],
            [
                'key' => 'ui.halloween_haunt',
                'name' => 'Halloween Theme',
                'description' => 'Seasonal Halloween theme',
                'is_enabled' => false,
                'percentage' => 10,
                'metadata' => ['seasonal' => true, 'start_date' => '2025-10-15', 'end_date' => '2025-11-01'],
                'environment' => null,
            ],
        ];

        foreach ($flags as $flag) {
            FeUiFeatureFlag::updateOrCreate(
                ['key' => $flag['key']],
                $flag
            );
        }

        $this->command->info('Seeded ' . count($flags) . ' feature flags');
    }

    protected function seedRegistry(): void
    {
        $items = [
            [
                'type' => 'component',
                'name' => 'Table Component',
                'slug' => 'component.table',
                'description' => 'Data table with sorting and filtering',
                'version' => '1.0.0',
                'metadata' => ['kind' => 'composite', 'category' => 'data-display'],
                'is_active' => true,
                'published_at' => now(),
            ],
            [
                'type' => 'component',
                'name' => 'Button Component',
                'slug' => 'component.button',
                'description' => 'Interactive button primitive',
                'version' => '1.0.0',
                'metadata' => ['kind' => 'primitive', 'category' => 'actions'],
                'is_active' => true,
                'published_at' => now(),
            ],
            [
                'type' => 'component',
                'name' => 'Modal Layout',
                'slug' => 'layout.modal',
                'description' => 'Modal dialog layout',
                'version' => '2.0.0',
                'metadata' => ['kind' => 'layout', 'category' => 'containers'],
                'is_active' => true,
                'published_at' => now(),
            ],
            [
                'type' => 'page',
                'name' => 'Agent Table Page',
                'slug' => 'page.agent.table.modal',
                'description' => 'Agent management page with table and modal',
                'version' => '1.0.0',
                'metadata' => ['module' => 'agent-management', 'route' => '/v2/pages/page.agent.table.modal'],
                'is_active' => true,
                'published_at' => now(),
            ],
            [
                'type' => 'datasource',
                'name' => 'Agent Data Source',
                'slug' => 'datasource.agent',
                'description' => 'Provides agent data with filtering',
                'version' => '1.0.0',
                'metadata' => ['model' => 'Agent', 'resolver' => 'generic'],
                'is_active' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($items as $item) {
            FeUiRegistry::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }

        $this->command->info('Seeded ' . count($items) . ' registry items');
    }
}
