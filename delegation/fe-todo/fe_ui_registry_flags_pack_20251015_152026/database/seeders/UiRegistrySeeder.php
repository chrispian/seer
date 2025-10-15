<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeUiRegistry;
use App\Models\FeUiFeatureFlag;

class UiRegistrySeeder extends Seeder
{
    public function run(): void
    {
        FeUiRegistry::updateOrCreate(
            ['key' => 'component.layout.columns.3'],
            ['type' => 'component', 'resource_key' => 'component.layout.columns.3', 'version' => '0.1.0', 'enabled' => true, 'tags_json' => ['layout','columns']]
        );

        FeUiFeatureFlag::updateOrCreate(
            ['key' => 'ui.halloween_haunt'],
            [
                'description' => 'Subtle haunted UI effects in October',
                'enabled' => true,
                'rollout' => 5,
                'conditions_json' => ['paths' => ['/v2/*']],
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
            ]
        );
    }
}
