<?php

namespace Modules\UiBuilder\database\seeders;

use App\Models\FeType;
use App\Models\FeTypeField;
use Illuminate\Database\Seeder;

class V2TypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding v2 UI Types (Agent and Model)...');

        // Create Agent type
        $agentType = FeType::updateOrCreate(
            ['alias' => 'Agent'],
            [
                'source_type' => 'eloquent',
                'config' => [
                    'model' => \App\Models\Agent::class,
                ],
                'capabilities' => ['search', 'sort', 'filter', 'paginate'],
                'metadata' => [
                    'description' => 'Orchestration agents',
                    'icon' => 'users',
                    'display_name' => 'Agents'
                ],
                'enabled' => true,
            ]
        );

        // Create Agent fields
        $agentFields = [
            ['name' => 'id', 'type' => 'uuid', 'required' => true, 'searchable' => false, 'filterable' => false, 'sortable' => false],
            ['name' => 'name', 'type' => 'string', 'required' => true, 'searchable' => true, 'filterable' => false, 'sortable' => true],
            ['name' => 'designation', 'type' => 'string', 'required' => true, 'searchable' => true, 'filterable' => false, 'sortable' => true],
            ['name' => 'role', 'type' => 'string', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'status', 'type' => 'string', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'provider', 'type' => 'string', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'model', 'type' => 'string', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'agent_profile_id', 'type' => 'uuid', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'created_at', 'type' => 'datetime', 'required' => false, 'searchable' => false, 'filterable' => false, 'sortable' => true],
            ['name' => 'updated_at', 'type' => 'datetime', 'required' => false, 'searchable' => false, 'filterable' => false, 'sortable' => true],
        ];

        foreach ($agentFields as $order => $fieldData) {
            FeTypeField::updateOrCreate(
                [
                    'fe_type_id' => $agentType->id,
                    'name' => $fieldData['name']
                ],
                $fieldData
            );
        }

        // Create Model type (AIModel)
        $modelType = FeType::updateOrCreate(
            ['alias' => 'Model'],
            [
                'source_type' => 'eloquent',
                'config' => [
                    'model' => \App\Models\AIModel::class,
                ],
                'capabilities' => ['search', 'sort', 'filter', 'paginate'],
                'metadata' => [
                    'description' => 'AI Models configuration',
                    'icon' => 'cpu',
                    'display_name' => 'AI Models'
                ],
                'enabled' => true,
            ]
        );

        // Create Model fields
        $modelFields = [
            ['name' => 'id', 'type' => 'uuid', 'required' => true, 'searchable' => false, 'filterable' => false, 'sortable' => false],
            ['name' => 'name', 'type' => 'string', 'required' => true, 'searchable' => true, 'filterable' => false, 'sortable' => true],
            ['name' => 'model_id', 'type' => 'string', 'required' => true, 'searchable' => true, 'filterable' => false, 'sortable' => true],
            ['name' => 'provider', 'type' => 'string', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'type', 'type' => 'string', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'status', 'type' => 'string', 'required' => false, 'searchable' => false, 'filterable' => true, 'sortable' => false],
            ['name' => 'context_window', 'type' => 'integer', 'required' => false, 'searchable' => false, 'filterable' => false, 'sortable' => true],
            ['name' => 'max_tokens', 'type' => 'integer', 'required' => false, 'searchable' => false, 'filterable' => false, 'sortable' => true],
            ['name' => 'created_at', 'type' => 'datetime', 'required' => false, 'searchable' => false, 'filterable' => false, 'sortable' => true],
            ['name' => 'updated_at', 'type' => 'datetime', 'required' => false, 'searchable' => false, 'filterable' => false, 'sortable' => true],
        ];

        foreach ($modelFields as $order => $fieldData) {
            FeTypeField::updateOrCreate(
                [
                    'fe_type_id' => $modelType->id,
                    'name' => $fieldData['name']
                ],
                $fieldData
            );
        }

        $this->command->info("✓ Created Agent type with " . count($agentFields) . " fields");
        $this->command->info("✓ Created Model type with " . count($modelFields) . " fields");
        $this->command->info('');
        $this->command->info('Types are now available for the v2 UI system');
    }
}