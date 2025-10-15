<?php

namespace Database\Seeders;

use App\Models\FeUiComponent;
use Illuminate\Database\Seeder;

class LayoutComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'key' => 'component.card.default',
                'type' => 'card',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'footer', 'className'],
                    'children' => true,
                ],
                'defaults_json' => [],
                'capabilities_json' => ['container', 'nested_components', 'header', 'footer'],
                'version' => 1,
            ],
            [
                'key' => 'component.scroll-area.default',
                'type' => 'scroll-area',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['height', 'maxHeight', 'orientation', 'className'],
                    'children' => true,
                ],
                'defaults_json' => [
                    'height' => '400px',
                    'orientation' => 'vertical',
                ],
                'capabilities_json' => ['scrollable', 'overflow_management', 'custom_scrollbar'],
                'version' => 1,
            ],
            [
                'key' => 'component.resizable.default',
                'type' => 'resizable',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['direction', 'panels', 'withHandle', 'className'],
                    'panels' => ['id', 'defaultSize', 'minSize', 'maxSize', 'content'],
                ],
                'defaults_json' => [
                    'direction' => 'horizontal',
                    'withHandle' => true,
                ],
                'capabilities_json' => ['resizable_panels', 'draggable_handle', 'size_constraints', 'horizontal_vertical'],
                'version' => 1,
            ],
            [
                'key' => 'component.aspect-ratio.default',
                'type' => 'aspect-ratio',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['ratio', 'className'],
                    'children' => true,
                ],
                'defaults_json' => [
                    'ratio' => '16/9',
                ],
                'capabilities_json' => ['aspect_ratio_preservation', 'responsive', 'ratio_variants'],
                'version' => 1,
            ],
            [
                'key' => 'component.collapsible.default',
                'type' => 'collapsible',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'defaultOpen', 'disabled', 'triggerClassName', 'contentClassName', 'className'],
                    'children' => true,
                ],
                'defaults_json' => [
                    'defaultOpen' => false,
                    'disabled' => false,
                ],
                'capabilities_json' => ['collapsible', 'animated', 'stateful', 'keyboard_accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.accordion.single',
                'type' => 'accordion',
                'kind' => 'layout',
                'variant' => 'single',
                'config' => [],
                'schema_json' => [
                    'props' => ['type', 'collapsible', 'defaultValue', 'items', 'className'],
                    'items' => ['value', 'title', 'content', 'disabled'],
                ],
                'defaults_json' => [
                    'type' => 'single',
                    'collapsible' => true,
                ],
                'capabilities_json' => ['accordion', 'single_selection', 'animated', 'keyboard_accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.accordion.multiple',
                'type' => 'accordion',
                'kind' => 'layout',
                'variant' => 'multiple',
                'config' => [],
                'schema_json' => [
                    'props' => ['type', 'defaultValue', 'items', 'className'],
                    'items' => ['value', 'title', 'content', 'disabled'],
                ],
                'defaults_json' => [
                    'type' => 'multiple',
                ],
                'capabilities_json' => ['accordion', 'multiple_selection', 'animated', 'keyboard_accessible'],
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

        $this->command->info('Layout components seeded successfully.');
    }
}
