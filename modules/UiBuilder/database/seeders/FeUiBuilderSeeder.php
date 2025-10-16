<?php

namespace Modules\UiBuilder\database\seeders;

use Modules\UiBuilder\app\Models\Datasource;
use Modules\UiBuilder\app\Models\Page;
use Illuminate\Database\Seeder;

class FeUiBuilderSeeder extends Seeder
{
    public function run(): void
    {
        $pageConfig = [
            'id' => 'page.agent.table.modal',
            'overlay' => 'modal',
            'title' => 'Agents',
            'components' => [
                [
                    'id' => 'component.search.bar.agent',
                    'type' => 'search.bar',
                    'dataSource' => 'Agent',
                    'props' => [
                        'placeholder' => 'Search agents...'
                    ],
                    'result' => [
                        'target' => 'component.table.agent',
                        'open' => 'inline'
                    ],
                ],
                [
                    'id' => 'component.table.agent',
                    'type' => 'data-table',
                    'props' => [
                        'columns' => [
                            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                            ['key' => 'designation', 'label' => 'Designation', 'sortable' => true],
                            ['key' => 'role', 'label' => 'Role', 'filterable' => true],
                            ['key' => 'provider', 'label' => 'Provider', 'filterable' => true],
                            ['key' => 'model', 'label' => 'Model', 'filterable' => true],
                            ['key' => 'status', 'label' => 'Status', 'filterable' => true],
                            ['key' => 'updated_at', 'label' => 'Updated', 'sortable' => true],
                        ],
                        'dataSource' => 'Agent',
                        'rowAction' => [
                            'type' => 'modal',
                            'title' => 'Agent Details',
                            'url' => '/api/v2/ui/types/Agent/{{row.id}}',
                            'fields' => [
                                ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                                ['key' => 'designation', 'label' => 'Designation', 'type' => 'text'],
                                ['key' => 'role', 'label' => 'Role', 'type' => 'text'],
                                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
                                ['key' => 'provider', 'label' => 'Provider', 'type' => 'text'],
                                ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
                                ['key' => 'agent_profile_id', 'label' => 'Profile ID', 'type' => 'text'],
                                ['key' => 'created_at', 'label' => 'Created', 'type' => 'date'],
                                ['key' => 'updated_at', 'label' => 'Updated', 'type' => 'date'],
                            ],
                        ],
                        'toolbar' => [
                            [
                                'id' => 'component.button.icon.add-agent',
                                'type' => 'button.icon',
                                'props' => ['icon' => 'plus', 'label' => 'New Agent'],
                                'actions' => [
                                    'click' => [
                                        'type' => 'modal',
                                        'modal' => 'form',
                                        'title' => 'Add New Agent',
                                        'fields' => [
                                            [
                                                'name' => 'name',
                                                'label' => 'Agent Name',
                                                'type' => 'text',
                                                'required' => true,
                                                'placeholder' => 'e.g., Research Assistant'
                                            ],
                                            [
                                                'name' => 'designation',
                                                'label' => 'Designation',
                                                'type' => 'text',
                                                'required' => true,
                                                'placeholder' => 'e.g., research-assistant'
                                            ],
                                            [
                                                'name' => 'role',
                                                'label' => 'Role',
                                                'type' => 'select',
                                                'required' => true,
                                                'options' => [
                                                    ['value' => 'assistant', 'label' => 'Assistant'],
                                                    ['value' => 'reviewer', 'label' => 'Reviewer'],
                                                    ['value' => 'developer', 'label' => 'Developer'],
                                                    ['value' => 'analyst', 'label' => 'Analyst'],
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
                                        'submitUrl' => '/api/v2/ui/datasources/Agent',
                                        'submitMethod' => 'POST',
                                        'submitLabel' => 'Create Agent',
                                        'refreshTarget' => 'component.table.agent'
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Page::updateOrCreate(
            ['key' => 'page.agent.table.modal'],
            ['layout_tree_json' => $pageConfig]
        );

        Datasource::updateOrCreate(
            ['alias' => 'Agent'],
            [
                'model_class' => \App\Models\Agent::class,
                'handler' => \App\Models\Agent::class, // Same as model_class for now
                'resolver_class' => \App\Services\V2\AgentDataSourceResolver::class,
                'capabilities_json' => [
                    'searchable' => ['name', 'designation'],
                    'filterable' => ['status', 'agent_profile_id'],
                    'sortable' => ['name', 'updated_at'],
                ],
                'schema_json' => [
                    'transform' => [
                        'id' => 'id',
                        'name' => 'name',
                        'designation' => 'designation',
                        'status' => 'status',
                        'agent_profile_id' => 'agent_profile_id',
                        'updated_at' => ['source' => 'updated_at', 'format' => 'iso8601'],
                    ]
                ],
                'default_params_json' => [
                    'with' => [],
                    'scopes' => [],
                    'default_sort' => ['updated_at', 'desc'],
                ],
            ]
        );
    }
}
