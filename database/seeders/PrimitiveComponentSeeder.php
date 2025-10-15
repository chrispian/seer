<?php

namespace Database\Seeders;

use App\Models\FeUiComponent;
use Illuminate\Database\Seeder;

class PrimitiveComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'key' => 'component.button.default',
                'type' => 'button',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['label', 'icon', 'variant', 'size', 'disabled', 'loading', 'className'],
                    'actions' => ['click'],
                ],
                'defaults_json' => [
                    'variant' => 'default',
                    'size' => 'default',
                    'disabled' => false,
                    'loading' => false,
                ],
                'capabilities_json' => ['clickable', 'focusable', 'keyboard_accessible', 'loading_state'],
                'version' => 1,
            ],
            [
                'key' => 'component.button.icon',
                'type' => 'button',
                'kind' => 'primitive',
                'variant' => 'icon',
                'config' => [],
                'schema_json' => [
                    'props' => ['icon', 'variant', 'size', 'disabled', 'loading', 'className'],
                    'actions' => ['click'],
                ],
                'defaults_json' => [
                    'variant' => 'default',
                    'size' => 'icon',
                    'disabled' => false,
                    'loading' => false,
                ],
                'capabilities_json' => ['clickable', 'focusable', 'keyboard_accessible', 'icon_only'],
                'version' => 1,
            ],
            [
                'key' => 'component.input.text',
                'type' => 'input',
                'kind' => 'primitive',
                'variant' => 'text',
                'config' => [],
                'schema_json' => [
                    'props' => ['placeholder', 'value', 'defaultValue', 'disabled', 'readonly', 'required', 'type', 'name', 'className'],
                    'actions' => ['change', 'blur', 'focus'],
                ],
                'defaults_json' => [
                    'type' => 'text',
                    'disabled' => false,
                    'readonly' => false,
                    'required' => false,
                ],
                'capabilities_json' => ['editable', 'focusable', 'validatable', 'keyboard_accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.label.default',
                'type' => 'label',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['text', 'htmlFor', 'required', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'required' => false,
                ],
                'capabilities_json' => ['semantic', 'accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.badge.default',
                'type' => 'badge',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['text', 'variant', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'default',
                ],
                'capabilities_json' => ['visual_indicator', 'status_display'],
                'version' => 1,
            ],
            [
                'key' => 'component.avatar.default',
                'type' => 'avatar',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['src', 'alt', 'fallback', 'size', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'size' => 'md',
                    'fallback' => '?',
                ],
                'capabilities_json' => ['image_display', 'fallback_support'],
                'version' => 1,
            ],
            [
                'key' => 'component.skeleton.default',
                'type' => 'skeleton',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['variant', 'width', 'height', 'lines', 'animate', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'rectangular',
                    'lines' => 1,
                    'animate' => true,
                ],
                'capabilities_json' => ['loading_placeholder', 'animated'],
                'version' => 1,
            ],
            [
                'key' => 'component.spinner.default',
                'type' => 'spinner',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['size', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'size' => 'md',
                ],
                'capabilities_json' => ['loading_indicator', 'animated'],
                'version' => 1,
            ],
            [
                'key' => 'component.separator.horizontal',
                'type' => 'separator',
                'kind' => 'primitive',
                'variant' => 'horizontal',
                'config' => [],
                'schema_json' => [
                    'props' => ['orientation', 'decorative', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'orientation' => 'horizontal',
                    'decorative' => true,
                ],
                'capabilities_json' => ['visual_divider', 'semantic'],
                'version' => 1,
            ],
            [
                'key' => 'component.separator.vertical',
                'type' => 'separator',
                'kind' => 'primitive',
                'variant' => 'vertical',
                'config' => [],
                'schema_json' => [
                    'props' => ['orientation', 'decorative', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'orientation' => 'vertical',
                    'decorative' => true,
                ],
                'capabilities_json' => ['visual_divider', 'semantic'],
                'version' => 1,
            ],
            [
                'key' => 'component.kbd.default',
                'type' => 'kbd',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['keys', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [],
                'capabilities_json' => ['keyboard_shortcut_display', 'visual_indicator'],
                'version' => 1,
            ],
            [
                'key' => 'component.typography.h1',
                'type' => 'typography',
                'kind' => 'primitive',
                'variant' => 'h1',
                'config' => [],
                'schema_json' => [
                    'props' => ['text', 'variant', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'h1',
                ],
                'capabilities_json' => ['text_display', 'semantic', 'responsive'],
                'version' => 1,
            ],
            [
                'key' => 'component.typography.h2',
                'type' => 'typography',
                'kind' => 'primitive',
                'variant' => 'h2',
                'config' => [],
                'schema_json' => [
                    'props' => ['text', 'variant', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'h2',
                ],
                'capabilities_json' => ['text_display', 'semantic', 'responsive'],
                'version' => 1,
            ],
            [
                'key' => 'component.typography.h3',
                'type' => 'typography',
                'kind' => 'primitive',
                'variant' => 'h3',
                'config' => [],
                'schema_json' => [
                    'props' => ['text', 'variant', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'h3',
                ],
                'capabilities_json' => ['text_display', 'semantic', 'responsive'],
                'version' => 1,
            ],
            [
                'key' => 'component.typography.p',
                'type' => 'typography',
                'kind' => 'primitive',
                'variant' => 'p',
                'config' => [],
                'schema_json' => [
                    'props' => ['text', 'variant', 'className'],
                    'actions' => [],
                ],
                'defaults_json' => [
                    'variant' => 'p',
                ],
                'capabilities_json' => ['text_display', 'semantic'],
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

        $this->command->info('Seeded ' . count($components) . ' primitive components');
    }
}
