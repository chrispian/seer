<?php

namespace HollisLabs\UiBuilder\Database\Seeders;

use HollisLabs\UiBuilder\Models\Module;
use Illuminate\Database\Seeder;

class UiBuilderModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding UI Builder module...');

        Module::updateOrCreate(
            ['key' => 'core.ui-builder'],
            [
                'title' => 'UI Builder',
                'description' => 'Visual interface for creating and managing UI pages and components',
                'manifest_json' => [
                    'pages' => [
                        'page.ui-builder.pages.list',
                        'page.ui-builder.components.list',
                    ],
                    'datasources' => [
                        'UiPage',
                        'UiComponent',
                        'UiModule',
                    ],
                    'navigation' => [
                        'label' => 'UI Builder',
                        'icon' => 'layout',
                        'route' => '/ui-builder',
                        'children' => [
                            [
                                'label' => 'Pages',
                                'icon' => 'file',
                                'page' => 'page.ui-builder.pages.list',
                            ],
                            [
                                'label' => 'Components',
                                'icon' => 'box',
                                'page' => 'page.ui-builder.components.list',
                            ],
                        ],
                    ],
                ],
                'version' => '1.0.0',
                'hash' => hash('sha256', 'core.ui-builder.1.0.0'),
                'enabled' => true,
                'order' => 100,
                'capabilities' => ['page-management', 'component-management'],
                'permissions' => ['manage_ui_builder'],
            ]
        );

        $this->command->info('✓ core.ui-builder module created');
    }
}
