<?php

namespace Database\Seeders;

use App\Models\FeUiModule;
use App\Models\FeUiTheme;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModulesThemesSeeder extends Seeder
{
    public function run(): void
    {
        FeUiModule::create([
            'key' => 'core.agents',
            'title' => 'Agent Management',
            'description' => 'Manage AI agents, profiles, and configurations',
            'manifest_json' => [
                'pages' => ['page.agent.table.modal'],
                'datasources' => ['Agent'],
                'actions' => ['action.agent.create'],
                'navigation' => [
                    'label' => 'Agents',
                    'icon' => 'users',
                    'route' => '/agents',
                ],
            ],
            'version' => '1.0.0',
            'hash' => hash('sha256', 'core.agents.1.0.0'),
            'enabled' => true,
            'order' => 10,
            'capabilities' => ['search', 'filter', 'export'],
            'permissions' => ['view_agents'],
        ]);

        FeUiTheme::create([
            'key' => 'theme.default',
            'title' => 'Default Theme',
            'description' => 'Standard Fragments Engine theme',
            'design_tokens_json' => [
                'radius' => [
                    'sm' => '0.25rem',
                    'md' => '0.5rem',
                    'lg' => '0.75rem',
                ],
                'spacing' => [
                    'unit' => '4px',
                ],
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                ],
                'typography' => [
                    'fontFamily' => [
                        'sans' => ['Inter', 'system-ui'],
                    ],
                    'fontSize' => [
                        'base' => '1rem',
                    ],
                ],
            ],
            'tailwind_overrides_json' => null,
            'variants_json' => ['light', 'dark'],
            'version' => '1.0.0',
            'hash' => hash('sha256', 'theme.default.1.0.0'),
            'enabled' => true,
            'is_default' => true,
        ]);

        $this->command->info('✓ Created core.agents module');
        $this->command->info('✓ Created theme.default theme');
    }
}
