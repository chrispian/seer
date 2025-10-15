<?php

namespace Database\Seeders;

use App\Models\FeUiComponent;
use Illuminate\Database\Seeder;

class FeedbackComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'key' => 'component.alert.default',
                'type' => 'alert',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['variant', 'title', 'description', 'icon', 'dismissible', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'default',
                    'dismissible' => false,
                ],
                'capabilities_json' => ['dismissible', 'icon_support', 'accessible', 'aria_live'],
                'version' => 1,
            ],
            [
                'key' => 'component.alert.success',
                'type' => 'alert',
                'kind' => 'primitive',
                'variant' => 'success',
                'config' => [],
                'schema_json' => [
                    'props' => ['variant', 'title', 'description', 'icon', 'dismissible', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'success',
                    'dismissible' => false,
                ],
                'capabilities_json' => ['dismissible', 'icon_support', 'accessible', 'aria_live'],
                'version' => 1,
            ],
            [
                'key' => 'component.alert.warning',
                'type' => 'alert',
                'kind' => 'primitive',
                'variant' => 'warning',
                'config' => [],
                'schema_json' => [
                    'props' => ['variant', 'title', 'description', 'icon', 'dismissible', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'warning',
                    'dismissible' => false,
                ],
                'capabilities_json' => ['dismissible', 'icon_support', 'accessible', 'aria_live'],
                'version' => 1,
            ],
            [
                'key' => 'component.alert.destructive',
                'type' => 'alert',
                'kind' => 'primitive',
                'variant' => 'destructive',
                'config' => [],
                'schema_json' => [
                    'props' => ['variant', 'title', 'description', 'icon', 'dismissible', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'destructive',
                    'dismissible' => false,
                ],
                'capabilities_json' => ['dismissible', 'icon_support', 'accessible', 'aria_live'],
                'version' => 1,
            ],
            [
                'key' => 'component.progress.default',
                'type' => 'progress',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['value', 'showLabel', 'variant', 'size', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'value' => 0,
                    'showLabel' => false,
                    'variant' => 'default',
                    'size' => 'default',
                ],
                'capabilities_json' => ['progress_indication', 'accessible', 'aria_valuenow'],
                'version' => 1,
            ],
            [
                'key' => 'component.progress.success',
                'type' => 'progress',
                'kind' => 'primitive',
                'variant' => 'success',
                'config' => [],
                'schema_json' => [
                    'props' => ['value', 'showLabel', 'variant', 'size', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'value' => 0,
                    'showLabel' => false,
                    'variant' => 'success',
                    'size' => 'default',
                ],
                'capabilities_json' => ['progress_indication', 'accessible', 'aria_valuenow'],
                'version' => 1,
            ],
            [
                'key' => 'component.toast.default',
                'type' => 'toast',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'variant', 'duration'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'default',
                    'duration' => 5000,
                ],
                'capabilities_json' => ['notification', 'auto_dismiss', 'timed_display'],
                'version' => 1,
            ],
            [
                'key' => 'component.toast.success',
                'type' => 'toast',
                'kind' => 'primitive',
                'variant' => 'success',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'variant', 'duration'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'success',
                    'duration' => 5000,
                ],
                'capabilities_json' => ['notification', 'auto_dismiss', 'timed_display'],
                'version' => 1,
            ],
            [
                'key' => 'component.toast.error',
                'type' => 'toast',
                'kind' => 'primitive',
                'variant' => 'destructive',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'variant', 'duration'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'destructive',
                    'duration' => 7000,
                ],
                'capabilities_json' => ['notification', 'auto_dismiss', 'timed_display'],
                'version' => 1,
            ],
            [
                'key' => 'component.empty.default',
                'type' => 'empty',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['icon', 'title', 'description', 'action', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [],
                'capabilities_json' => ['empty_state', 'icon_support', 'action_button', 'accessible'],
                'version' => 1,
            ],
        ];

        foreach ($components as $component) {
            $component['hash'] = hash('sha256', json_encode($component));
            FeUiComponent::updateOrCreate(
                ['key' => $component['key']],
                $component
            );
        }

        $this->command->info('✓ Feedback components seeded successfully');
    }
}
