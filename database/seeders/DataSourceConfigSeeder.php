<?php

namespace Database\Seeders;

use Modules\UiBuilder\app\Models\Datasource;
use Illuminate\Database\Seeder;

class DataSourceConfigSeeder extends Seeder
{
    public function run(): void
    {
        $datasources = [
            [
                'alias' => 'Agent',
                'handler' => 'App\Models\Agent',
                'default_params_json' => [
                    'with' => ['agentProfile'],
                    'scopes' => [],
                    'default_sort' => ['updated_at', 'desc'],
                ],
                'capabilities_json' => [
                    'supports' => ['list', 'detail', 'search', 'paginate'],
                    'searchable' => ['name', 'designation'],
                    'filterable' => ['status', 'agent_profile_id'],
                    'sortable' => ['name', 'updated_at'],
                ],
                'schema_json' => [
                    'transform' => [
                        'id' => 'id',
                        'name' => 'name',
                        'role' => 'designation',
                        'type' => ['source' => 'agentProfile.type'],
                        'mode' => ['source' => 'agentProfile.mode'],
                        'status' => 'status',
                        'updated_at' => ['source' => 'updated_at', 'format' => 'iso8601'],
                        'avatar_url' => 'avatar_url',
                        'avatar_path' => 'avatar_path',
                    ],
                ],
            ],
            [
                'alias' => 'Model',
                'handler' => 'App\Models\AIModel',
                'default_params_json' => [
                    'with' => ['provider'],
                    'scopes' => [],
                    'default_sort' => ['updated_at', 'desc'],
                ],
                'capabilities_json' => [
                    'supports' => ['list', 'detail', 'search', 'paginate'],
                    'searchable' => ['name', 'model_id'],
                    'filterable' => ['enabled', 'provider_id'],
                    'sortable' => ['name', 'updated_at', 'priority'],
                ],
                'schema_json' => [
                    'transform' => [
                        'id' => 'id',
                        'name' => 'name',
                        'model_id' => 'model_id',
                        'provider' => ['source' => 'provider.provider'],
                        'provider_name' => ['source' => 'provider.name'],
                        'enabled' => 'enabled',
                        'priority' => 'priority',
                        'capabilities' => 'capabilities',
                        'pricing' => 'pricing',
                        'limits' => 'limits',
                        'updated_at' => ['source' => 'updated_at', 'format' => 'iso8601'],
                    ],
                ],
            ],
        ];

        foreach ($datasources as $datasourceConfig) {
            Datasource::updateOrCreate(
                ['alias' => $datasourceConfig['alias']],
                $datasourceConfig
            );
        }

        $this->command->info('✓ DataSource configurations migrated successfully');
    }
}
