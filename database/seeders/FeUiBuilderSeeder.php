<?php

namespace Database\Seeders;

use App\Models\FeUiDatasource;
use App\Models\FeUiPage;
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
                    'resolver' => 'DataSourceResolver::class',
                    'submit' => false,
                    'result' => ['target' => 'component.table.agent', 'open' => 'inline'],
                ],
                [
                    'id' => 'component.table.agent',
                    'type' => 'table',
                    'dataSource' => 'Agent',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                        ['key' => 'role', 'label' => 'Role', 'filterable' => true],
                        ['key' => 'provider', 'label' => 'Provider', 'filterable' => true],
                        ['key' => 'model', 'label' => 'Model', 'filterable' => true],
                        ['key' => 'status', 'label' => 'Status', 'filterable' => true],
                        ['key' => 'updated_at', 'label' => 'Updated', 'sortable' => true],
                    ],
                    'rowAction' => [
                        'type' => 'command',
                        'command' => '/orch-agent',
                        'params' => ['id' => '{{row.id}}'],
                    ],
                    'toolbar' => [
                        [
                            'id' => 'component.button.icon.add-agent',
                            'type' => 'button.icon',
                            'props' => ['icon' => 'plus', 'label' => 'New Agent'],
                            'actions' => [
                                'click' => ['type' => 'command', 'command' => '/orch-agent-new'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        FeUiPage::updateOrCreate(
            ['key' => 'page.agent.table.modal'],
            ['config' => $pageConfig]
        );

        FeUiDatasource::updateOrCreate(
            ['alias' => 'Agent'],
            [
                'model_class' => \App\Models\Agent::class,
                'resolver_class' => \App\Services\V2\AgentDataSourceResolver::class,
                'capabilities' => [
                    'searchable' => ['name', 'designation'],
                    'filterable' => ['status', 'agent_profile_id'],
                    'sortable' => ['name', 'updated_at'],
                ],
            ]
        );
    }
}
