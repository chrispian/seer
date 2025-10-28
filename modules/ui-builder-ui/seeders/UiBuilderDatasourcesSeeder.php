<?php

namespace HollisLabs\UiBuilder\Database\Seeders;

use HollisLabs\UiBuilder\Models\Datasource;
use Illuminate\Database\Seeder;

class UiBuilderDatasourcesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding UI Builder datasources...');

        $this->createUiPageDataSource();
        $this->createUiComponentDataSource();
        $this->createUiModuleDataSource();

        $this->command->info('✓ UI Builder datasources created successfully');
    }

    protected function createUiPageDataSource(): void
    {
        Datasource::updateOrCreate(
            ['alias' => 'UiPage'],
            [
                'model_class' => \HollisLabs\UiBuilder\Models\Page::class,
                'handler' => \HollisLabs\UiBuilder\Models\Page::class,
                'resolver_class' => \HollisLabs\UiBuilder\Services\DataSourceResolver::class,
                'capabilities' => [
                    'searchable' => ['key', 'route'],
                    'filterable' => ['module_key', 'enabled'],
                    'sortable' => ['key', 'route', 'enabled', 'version', 'created_at', 'updated_at'],
                ],
                'default_params_json' => [
                    'with' => [],
                    'scopes' => [],
                    'default_sort' => ['updated_at', 'desc'],
                ],
                'capabilities_json' => [
                    'searchable' => ['key', 'route'],
                    'filterable' => ['module_key', 'enabled'],
                    'sortable' => ['key', 'route', 'enabled', 'version', 'created_at', 'updated_at'],
                ],
                'schema_json' => [
                    'transform' => [
                        'id' => 'id',
                        'key' => 'key',
                        'route' => 'route',
                        'module_key' => 'module_key',
                        'enabled' => 'enabled',
                        'version' => 'version',
                        'hash' => 'hash',
                        'created_at' => [
                            'source' => 'created_at',
                            'format' => 'iso8601',
                        ],
                        'updated_at' => [
                            'source' => 'updated_at',
                            'format' => 'iso8601',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('  ✓ UiPage datasource');
    }

    protected function createUiComponentDataSource(): void
    {
        Datasource::updateOrCreate(
            ['alias' => 'UiComponent'],
            [
                'model_class' => \HollisLabs\UiBuilder\Models\Component::class,
                'handler' => \HollisLabs\UiBuilder\Models\Component::class,
                'resolver_class' => \HollisLabs\UiBuilder\Services\DataSourceResolver::class,
                'capabilities' => [
                    'searchable' => ['key', 'type'],
                    'filterable' => ['type', 'kind', 'variant'],
                    'sortable' => ['key', 'type', 'kind', 'variant', 'version', 'created_at', 'updated_at'],
                ],
                'default_params_json' => [
                    'with' => [],
                    'scopes' => [],
                    'default_sort' => ['updated_at', 'desc'],
                ],
                'capabilities_json' => [
                    'searchable' => ['key', 'type'],
                    'filterable' => ['type', 'kind', 'variant'],
                    'sortable' => ['key', 'type', 'kind', 'variant', 'version', 'created_at', 'updated_at'],
                ],
                'schema_json' => [
                    'transform' => [
                        'id' => 'id',
                        'key' => 'key',
                        'type' => 'type',
                        'kind' => 'kind',
                        'variant' => 'variant',
                        'version' => 'version',
                        'created_at' => [
                            'source' => 'created_at',
                            'format' => 'iso8601',
                        ],
                        'updated_at' => [
                            'source' => 'updated_at',
                            'format' => 'iso8601',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('  ✓ UiComponent datasource');
    }

    protected function createUiModuleDataSource(): void
    {
        Datasource::updateOrCreate(
            ['alias' => 'UiModule'],
            [
                'model_class' => \HollisLabs\UiBuilder\Models\Module::class,
                'handler' => \HollisLabs\UiBuilder\Models\Module::class,
                'resolver_class' => \HollisLabs\UiBuilder\Services\DataSourceResolver::class,
                'capabilities' => [
                    'searchable' => ['key', 'title', 'description'],
                    'filterable' => ['enabled'],
                    'sortable' => ['key', 'title', 'enabled', 'order', 'version', 'created_at'],
                ],
                'default_params_json' => [
                    'with' => [],
                    'scopes' => [],
                    'default_sort' => ['order', 'asc'],
                ],
                'capabilities_json' => [
                    'searchable' => ['key', 'title', 'description'],
                    'filterable' => ['enabled'],
                    'sortable' => ['key', 'title', 'enabled', 'order', 'version', 'created_at'],
                ],
                'schema_json' => [
                    'transform' => [
                        'id' => 'id',
                        'key' => 'key',
                        'title' => 'title',
                        'description' => 'description',
                        'enabled' => 'enabled',
                        'order' => 'order',
                        'version' => 'version',
                        'created_at' => [
                            'source' => 'created_at',
                            'format' => 'iso8601',
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('  ✓ UiModule datasource');
    }
}
