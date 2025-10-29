<?php

namespace HollisLabs\UiBuilder\Database\Seeders;

use HollisLabs\UiBuilder\Models\Datasource;
use Illuminate\Database\Seeder;

class BuilderDataSourceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Builder DataSource...');

        Datasource::updateOrCreate(
            ['alias' => 'BuilderPageComponents'],
            [
                'model_class' => 'HollisLabs\UiBuilder\Models\BuilderPageComponent',
                'primary_key' => 'id',
                'fields_json' => [
                    'id' => ['type' => 'integer', 'label' => 'ID'],
                    'component_id' => ['type' => 'string', 'label' => 'Component ID'],
                    'component_type' => ['type' => 'string', 'label' => 'Type'],
                    'order' => ['type' => 'integer', 'label' => 'Order'],
                    'parent_id' => ['type' => 'integer', 'label' => 'Parent ID'],
                ],
                'enabled' => true,
            ]
        );

        $this->command->info('✓ BuilderPageComponents datasource created');
    }
}
