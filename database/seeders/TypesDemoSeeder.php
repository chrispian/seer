<?php

namespace Database\Seeders;

use HollisLabs\UiBuilder\DTOs\Types\TypeField;
use HollisLabs\UiBuilder\DTOs\Types\TypeRelation;
use HollisLabs\UiBuilder\DTOs\Types\TypeSchema;
use HollisLabs\UiBuilder\Services\Types\TypeRegistry;
use Illuminate\Database\Seeder;

class TypesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $registry = app(TypeRegistry::class);

        $agentSchema = new TypeSchema(
            alias: 'Agent',
            sourceType: 'eloquent',
            fields: [
                new TypeField(
                    name: 'id',
                    type: 'integer',
                    label: 'ID',
                    searchable: false,
                    sortable: true,
                    filterable: false,
                    order: 0
                ),
                new TypeField(
                    name: 'designation',
                    type: 'string',
                    label: 'Designation',
                    required: true,
                    searchable: true,
                    sortable: true,
                    filterable: true,
                    order: 1
                ),
                new TypeField(
                    name: 'name',
                    type: 'string',
                    label: 'Name',
                    required: true,
                    searchable: true,
                    sortable: true,
                    filterable: true,
                    order: 2
                ),
                new TypeField(
                    name: 'role',
                    type: 'string',
                    label: 'Role',
                    required: false,
                    searchable: true,
                    sortable: true,
                    filterable: true,
                    order: 3
                ),
                new TypeField(
                    name: 'status',
                    type: 'string',
                    label: 'Status',
                    required: true,
                    searchable: false,
                    sortable: true,
                    filterable: true,
                    order: 4
                ),
                new TypeField(
                    name: 'created_at',
                    type: 'datetime',
                    label: 'Created At',
                    searchable: false,
                    sortable: true,
                    filterable: false,
                    order: 5
                ),
            ],
            relations: [],
            capabilities: ['search', 'sort', 'filter', 'paginate'],
            config: [
                'model' => \App\Models\Agent::class,
                'table' => 'agents',
                'primary_key' => 'id',
            ],
            metadata: [
                'description' => 'Agent type for testing FE Types system',
                'icon' => 'users',
            ],
            enabled: true
        );

        $registry->register($agentSchema);

        $this->command->info('✓ Agent type schema registered');
    }
}
