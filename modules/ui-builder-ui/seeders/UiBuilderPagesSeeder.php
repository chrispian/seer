<?php

namespace HollisLabs\UiBuilder\Database\Seeders;

use HollisLabs\UiBuilder\Models\Page;
use Illuminate\Database\Seeder;

class UiBuilderPagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding UI Builder management pages...');

        $this->createPagesListPage();
        $this->createComponentsListPage();

        $this->command->info('✓ UI Builder pages created successfully');
    }

    protected function createPagesListPage(): void
    {
        $config = [
            'id' => 'page.ui-builder.pages.list',
            'overlay' => 'modal',
            'title' => 'UI Pages',
            'layout' => [
                'type' => 'rows',
                'id' => 'root-layout',
                'children' => [
                    [
                        'id' => 'component.search.bar.pages',
                        'type' => 'search.bar',
                        'props' => [
                            'placeholder' => 'Search pages...',
                        ],
                        'result' => [
                            'target' => 'component.table.pages',
                            'open' => 'inline',
                        ],
                    ],
                    [
                        'id' => 'component.table.pages',
                        'type' => 'data-table',
                        'props' => [
                            'dataSource' => 'UiPage',
                            'columns' => [
                                [
                                    'key' => 'key',
                                    'label' => 'Page Key',
                                    'sortable' => true,
                                ],
                                [
                                    'key' => 'route',
                                    'label' => 'Route',
                                    'sortable' => true,
                                ],
                                [
                                    'key' => 'module_key',
                                    'label' => 'Module',
                                    'filterable' => true,
                                ],
                                [
                                    'key' => 'enabled',
                                    'label' => 'Enabled',
                                    'filterable' => true,
                                    'render' => 'badge',
                                ],
                                [
                                    'key' => 'version',
                                    'label' => 'Version',
                                    'sortable' => true,
                                ],
                                [
                                    'key' => 'updated_at',
                                    'label' => 'Updated',
                                    'sortable' => true,
                                ],
                            ],
                            'rowAction' => [
                                'type' => 'modal',
                                'title' => 'Page Details',
                                'url' => '/api/ui/types/UiPage/{{row.id}}',
                                'fields' => [
                                    [
                                        'key' => 'id',
                                        'label' => 'ID',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'key',
                                        'label' => 'Page Key',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'route',
                                        'label' => 'Route',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'module_key',
                                        'label' => 'Module',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'enabled',
                                        'label' => 'Enabled',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'version',
                                        'label' => 'Version',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'hash',
                                        'label' => 'Hash',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'created_at',
                                        'label' => 'Created',
                                        'type' => 'date',
                                    ],
                                    [
                                        'key' => 'updated_at',
                                        'label' => 'Updated',
                                        'type' => 'date',
                                    ],
                                ],
                            ],
                            'toolbar' => [
                                [
                                    'id' => 'component.button.icon.add-page',
                                    'type' => 'button.icon',
                                    'props' => [
                                        'icon' => 'plus',
                                        'label' => 'New Page',
                                    ],
                                    'actions' => [
                                        'click' => [
                                            'type' => 'modal',
                                            'modal' => 'form',
                                            'title' => 'Create New Page',
                                            'fields' => [
                                                [
                                                    'name' => 'key',
                                                    'label' => 'Page Key',
                                                    'type' => 'text',
                                                    'required' => true,
                                                    'placeholder' => 'page.module.name.type',
                                                ],
                                                [
                                                    'name' => 'title',
                                                    'label' => 'Title',
                                                    'type' => 'text',
                                                    'required' => false,
                                                    'placeholder' => 'e.g., User Management',
                                                ],
                                                [
                                                    'name' => 'overlay',
                                                    'label' => 'Display Type',
                                                    'type' => 'select',
                                                    'required' => true,
                                                    'options' => [
                                                        [
                                                            'value' => 'modal',
                                                            'label' => 'Modal',
                                                        ],
                                                        [
                                                            'value' => 'sheet',
                                                            'label' => 'Sheet',
                                                        ],
                                                        [
                                                            'value' => 'drawer',
                                                            'label' => 'Drawer',
                                                        ],
                                                        [
                                                            'value' => 'fullscreen',
                                                            'label' => 'Full Screen',
                                                        ],
                                                    ],
                                                ],
                                                [
                                                    'name' => 'route',
                                                    'label' => 'Route (optional)',
                                                    'type' => 'text',
                                                    'required' => false,
                                                    'placeholder' => '/admin/pages',
                                                ],
                                                [
                                                    'name' => 'module_key',
                                                    'label' => 'Module',
                                                    'type' => 'select',
                                                    'required' => false,
                                                    'options' => [
                                                        [
                                                            'value' => 'core.ui-builder',
                                                            'label' => 'UI Builder',
                                                        ],
                                                        [
                                                            'value' => 'core.agents',
                                                            'label' => 'Agent Management',
                                                        ],
                                                    ],
                                                ],
                                                [
                                                    'name' => 'enabled',
                                                    'label' => 'Enabled',
                                                    'type' => 'checkbox',
                                                    'required' => false,
                                                    'defaultValue' => true,
                                                ],
                                            ],
                                            'submitUrl' => '/api/ui/datasources/UiPage',
                                            'submitMethod' => 'POST',
                                            'submitLabel' => 'Create Page',
                                            'refreshTarget' => 'component.table.pages',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Page::updateOrCreate(
            ['key' => 'page.ui-builder.pages.list'],
            [
                'config' => $config,
                'route' => null,
                'module_key' => 'core.ui-builder',
                'enabled' => true,
            ]
        );

        $this->command->info('  ✓ page.ui-builder.pages.list created');
    }

    protected function createComponentsListPage(): void
    {
        $config = [
            'id' => 'page.ui-builder.components.list',
            'overlay' => 'modal',
            'title' => 'UI Components',
            'layout' => [
                'type' => 'rows',
                'id' => 'root-layout',
                'children' => [
                    [
                        'id' => 'component.search.bar.components',
                        'type' => 'search.bar',
                        'props' => [
                            'placeholder' => 'Search components...',
                        ],
                        'result' => [
                            'target' => 'component.table.components',
                            'open' => 'inline',
                        ],
                    ],
                    [
                        'id' => 'component.table.components',
                        'type' => 'data-table',
                        'props' => [
                            'dataSource' => 'UiComponent',
                            'columns' => [
                                [
                                    'key' => 'key',
                                    'label' => 'Component Key',
                                    'sortable' => true,
                                ],
                                [
                                    'key' => 'type',
                                    'label' => 'Type',
                                    'filterable' => true,
                                ],
                                [
                                    'key' => 'kind',
                                    'label' => 'Kind',
                                    'filterable' => true,
                                ],
                                [
                                    'key' => 'variant',
                                    'label' => 'Variant',
                                    'sortable' => true,
                                ],
                                [
                                    'key' => 'version',
                                    'label' => 'Version',
                                    'sortable' => true,
                                ],
                                [
                                    'key' => 'updated_at',
                                    'label' => 'Updated',
                                    'sortable' => true,
                                ],
                            ],
                            'rowAction' => [
                                'type' => 'modal',
                                'title' => 'Component Details',
                                'url' => '/api/ui/types/UiComponent/{{row.id}}',
                                'fields' => [
                                    [
                                        'key' => 'id',
                                        'label' => 'ID',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'key',
                                        'label' => 'Component Key',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'type',
                                        'label' => 'Type',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'kind',
                                        'label' => 'Kind',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'variant',
                                        'label' => 'Variant',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'version',
                                        'label' => 'Version',
                                        'type' => 'text',
                                    ],
                                    [
                                        'key' => 'created_at',
                                        'label' => 'Created',
                                        'type' => 'date',
                                    ],
                                    [
                                        'key' => 'updated_at',
                                        'label' => 'Updated',
                                        'type' => 'date',
                                    ],
                                ],
                            ],
                            'toolbar' => [
                                [
                                    'id' => 'component.button.icon.add-component',
                                    'type' => 'button.icon',
                                    'props' => [
                                        'icon' => 'plus',
                                        'label' => 'New Component',
                                    ],
                                    'actions' => [
                                        'click' => [
                                            'type' => 'modal',
                                            'modal' => 'form',
                                            'title' => 'Create New Component',
                                            'fields' => [
                                                [
                                                    'name' => 'key',
                                                    'label' => 'Component Key',
                                                    'type' => 'text',
                                                    'required' => true,
                                                    'placeholder' => 'component.module.name',
                                                ],
                                                [
                                                    'name' => 'type',
                                                    'label' => 'Component Type',
                                                    'type' => 'text',
                                                    'required' => true,
                                                    'placeholder' => 'e.g., button, input, card',
                                                ],
                                                [
                                                    'name' => 'kind',
                                                    'label' => 'Kind',
                                                    'type' => 'select',
                                                    'required' => false,
                                                    'options' => [
                                                        [
                                                            'value' => 'primitive',
                                                            'label' => 'Primitive',
                                                        ],
                                                        [
                                                            'value' => 'composite',
                                                            'label' => 'Composite',
                                                        ],
                                                        [
                                                            'value' => 'layout',
                                                            'label' => 'Layout',
                                                        ],
                                                        [
                                                            'value' => 'advanced',
                                                            'label' => 'Advanced',
                                                        ],
                                                    ],
                                                ],
                                                [
                                                    'name' => 'variant',
                                                    'label' => 'Variant (optional)',
                                                    'type' => 'text',
                                                    'required' => false,
                                                    'placeholder' => 'e.g., default, outline',
                                                ],
                                            ],
                                            'submitUrl' => '/api/ui/datasources/UiComponent',
                                            'submitMethod' => 'POST',
                                            'submitLabel' => 'Create Component',
                                            'refreshTarget' => 'component.table.components',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Page::updateOrCreate(
            ['key' => 'page.ui-builder.components.list'],
            [
                'config' => $config,
                'route' => null,
                'module_key' => 'core.ui-builder',
                'enabled' => true,
            ]
        );

        $this->command->info('  ✓ page.ui-builder.components.list created');
    }


}
