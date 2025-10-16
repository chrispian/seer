<?php

namespace Modules\UiBuilder\database\seeders;

use Modules\UiBuilder\app\Models\Page;
use Modules\UiBuilder\app\Models\Datasource;
use Illuminate\Database\Seeder;

class V2ModelPageSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Model page for UI Builder v2...');

        // Model page configuration
        $modelPageConfig = [
            'id' => 'page.model.table.modal',
            'overlay' => 'modal',
            'title' => 'AI Models',
            'components' => [
                [
                    'id' => 'component.search.bar.model',
                    'type' => 'search.bar',
                    'dataSource' => 'Model',
                    'props' => [
                        'placeholder' => 'Search models...'
                    ],
                    'result' => [
                        'target' => 'component.table.model',
                        'open' => 'inline'
                    ],
                ],
                [
                    'id' => 'component.table.model',
                    'type' => 'data-table',
                    'dataSource' => 'Model',
                    'props' => [
                        'columns' => [
                            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                            ['key' => 'model_id', 'label' => 'Model ID', 'sortable' => true],
                            ['key' => 'provider_name', 'label' => 'Provider', 'filterable' => true],
                            ['key' => 'enabled', 'label' => 'Enabled', 'filterable' => true],
                            ['key' => 'priority', 'label' => 'Priority', 'sortable' => true],
                            ['key' => 'updated_at', 'label' => 'Updated', 'sortable' => true],
                        ],
                        'dataSource' => 'Model',
                        'rowAction' => [
                            'type' => 'modal',
                            'title' => 'Model Details',
                            'url' => '/api/v2/ui/types/Model/{{row.id}}',
                            'fields' => [
                                ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                                ['key' => 'model_id', 'label' => 'Model ID', 'type' => 'text'],
                                ['key' => 'provider_name', 'label' => 'Provider', 'type' => 'text'],
                                ['key' => 'description', 'label' => 'Description', 'type' => 'text'],
                                ['key' => 'enabled', 'label' => 'Enabled', 'type' => 'text'],
                                ['key' => 'priority', 'label' => 'Priority', 'type' => 'text'],
                                ['key' => 'created_at', 'label' => 'Created', 'type' => 'date'],
                                ['key' => 'updated_at', 'label' => 'Updated', 'type' => 'date'],
                            ],
                        ],
                        'toolbar' => [
                            [
                                'id' => 'component.button.icon.add-model',
                                'type' => 'button.icon',
                                'props' => ['icon' => 'plus', 'label' => 'New Model'],
                                'actions' => [
                                    'click' => [
                                        'type' => 'modal',
                                        'modal' => 'form',
                                        'title' => 'Add AI Model',
                                        'fields' => [
                                            [
                                                'name' => 'name',
                                                'label' => 'Model Name',
                                                'type' => 'text',
                                                'required' => true,
                                                'placeholder' => 'e.g., GPT-4'
                                            ],
                                            [
                                                'name' => 'model_id',
                                                'label' => 'Model ID',
                                                'type' => 'text',
                                                'required' => true,
                                                'placeholder' => 'e.g., gpt-4-turbo'
                                            ],
                                            [
                                                'name' => 'provider',
                                                'label' => 'Provider',
                                                'type' => 'select',
                                                'required' => true,
                                                'options' => [
                                                    ['value' => 'openai', 'label' => 'OpenAI'],
                                                    ['value' => 'anthropic', 'label' => 'Anthropic'],
                                                    ['value' => 'google', 'label' => 'Google'],
                                                    ['value' => 'ollama', 'label' => 'Ollama'],
                                                ]
                                            ],
                                            [
                                                'name' => 'type',
                                                'label' => 'Type',
                                                'type' => 'select',
                                                'required' => true,
                                                'options' => [
                                                    ['value' => 'chat', 'label' => 'Chat'],
                                                    ['value' => 'completion', 'label' => 'Completion'],
                                                    ['value' => 'embedding', 'label' => 'Embedding'],
                                                ]
                                            ],
                                            [
                                                'name' => 'status',
                                                'label' => 'Status',
                                                'type' => 'select',
                                                'required' => true,
                                                'options' => [
                                                    ['value' => 'active', 'label' => 'Active'],
                                                    ['value' => 'inactive', 'label' => 'Inactive'],
                                                ]
                                            ],
                                        ],
                                        'submitUrl' => '/api/v2/ui/datasources/Model',
                                        'submitMethod' => 'POST',
                                        'submitLabel' => 'Add Model',
                                        'refreshTarget' => 'component.table.model'
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Create/update the Model page
        $modelPage = Page::updateOrCreate(
            ['key' => 'page.model.table.modal'],
            ['layout_tree_json' => $modelPageConfig]
        );

        $this->command->info("✓ Created Model page: page.model.table.modal");
        $this->command->info("  Version: {$modelPage->version}");

        // Create/update Model datasource for GenericDataSourceResolver
        Datasource::updateOrCreate(
            ['alias' => 'Model'],
            [
                'model_class' => \App\Models\AIModel::class,
                'handler' => \App\Models\AIModel::class,
                'resolver_class' => \App\Services\V2\GenericDataSourceResolver::class, // Use generic resolver
                'capabilities_json' => [
                    'searchable' => ['name', 'model_id'],
                    'filterable' => ['provider_id', 'status'], // Use actual column names
                    'sortable' => ['name', 'model_id', 'updated_at'],
                ],
                'schema_json' => [
                    'transform' => [
                        'id' => 'id',
                        'name' => 'name',
                        'model_id' => 'model_id',
                        'provider_id' => 'provider_id',
                        'provider_name' => ['source' => 'provider.name'], // Access provider name through relationship
                        'description' => 'description',
                        'enabled' => 'enabled',
                        'priority' => 'priority',
                        'created_at' => ['source' => 'created_at', 'format' => 'iso8601'],
                        'updated_at' => ['source' => 'updated_at', 'format' => 'iso8601'],
                    ]
                ],
                'default_params_json' => [
                    'with' => ['provider'], // Eager load the provider relationship
                    'scopes' => [],
                    'default_sort' => ['updated_at', 'desc'],
                ],
            ]
        );

        $this->command->info("✓ Created Model datasource");
        $this->command->info('');
        $this->command->info('Demo pages available at:');
        $this->command->line('  → /v2/pages/page.model.table.modal');
    }
}