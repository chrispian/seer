<?php

namespace Database\Seeders;

use App\Models\FeUiComponent;
use Illuminate\Database\Seeder;

class NavigationComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'key' => 'component.tabs.default',
                'type' => 'tabs',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['defaultValue', 'tabs', 'className', 'listClassName'],
                    'tabs' => ['value', 'label', 'content', 'disabled'],
                    'children' => false,
                ],
                'defaults_json' => [
                    'defaultValue' => 'tab-1',
                ],
                'capabilities_json' => ['tabs', 'nested_content', 'disabled_tabs', 'keyboard_accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.breadcrumb.chevron',
                'type' => 'breadcrumb',
                'kind' => 'layout',
                'variant' => 'chevron',
                'config' => [],
                'schema_json' => [
                    'props' => ['items', 'separator', 'className'],
                    'items' => ['label', 'href', 'current'],
                ],
                'defaults_json' => [
                    'separator' => 'chevron',
                ],
                'capabilities_json' => ['breadcrumb', 'navigation_trail', 'current_page_highlight', 'accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.breadcrumb.slash',
                'type' => 'breadcrumb',
                'kind' => 'layout',
                'variant' => 'slash',
                'config' => [],
                'schema_json' => [
                    'props' => ['items', 'separator', 'className'],
                    'items' => ['label', 'href', 'current'],
                ],
                'defaults_json' => [
                    'separator' => 'slash',
                ],
                'capabilities_json' => ['breadcrumb', 'navigation_trail', 'current_page_highlight', 'accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.pagination.default',
                'type' => 'pagination',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['currentPage', 'totalPages', 'onPageChange', 'showFirstLast', 'showPrevNext', 'maxVisible', 'className'],
                    'onPageChange' => ['type', 'event', 'payload'],
                ],
                'defaults_json' => [
                    'currentPage' => 1,
                    'showFirstLast' => true,
                    'showPrevNext' => true,
                    'maxVisible' => 7,
                ],
                'capabilities_json' => ['pagination', 'page_numbers', 'ellipsis', 'event_emission', 'keyboard_accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.pagination.simple',
                'type' => 'pagination',
                'kind' => 'layout',
                'variant' => 'simple',
                'config' => [],
                'schema_json' => [
                    'props' => ['currentPage', 'totalPages', 'onPageChange', 'showFirstLast', 'showPrevNext', 'maxVisible', 'className'],
                    'onPageChange' => ['type', 'event', 'payload'],
                ],
                'defaults_json' => [
                    'currentPage' => 1,
                    'showFirstLast' => false,
                    'showPrevNext' => true,
                    'maxVisible' => 5,
                ],
                'capabilities_json' => ['pagination', 'minimal', 'prev_next_only', 'event_emission'],
                'version' => 1,
            ],
            [
                'key' => 'component.sidebar.default',
                'type' => 'sidebar',
                'kind' => 'layout',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['collapsible', 'defaultOpen', 'side', 'variant', 'items', 'groups', 'className'],
                    'items' => ['label', 'icon', 'href', 'badge', 'active', 'children'],
                    'groups' => ['label', 'items'],
                ],
                'defaults_json' => [
                    'collapsible' => true,
                    'defaultOpen' => true,
                    'side' => 'left',
                    'variant' => 'sidebar',
                ],
                'capabilities_json' => ['sidebar', 'collapsible', 'nested_navigation', 'icons', 'badges', 'grouped', 'keyboard_accessible'],
                'version' => 1,
            ],
            [
                'key' => 'component.sidebar.floating',
                'type' => 'sidebar',
                'kind' => 'layout',
                'variant' => 'floating',
                'config' => [],
                'schema_json' => [
                    'props' => ['collapsible', 'defaultOpen', 'side', 'variant', 'items', 'groups', 'className'],
                    'items' => ['label', 'icon', 'href', 'badge', 'active', 'children'],
                    'groups' => ['label', 'items'],
                ],
                'defaults_json' => [
                    'collapsible' => true,
                    'defaultOpen' => true,
                    'side' => 'left',
                    'variant' => 'floating',
                ],
                'capabilities_json' => ['sidebar', 'collapsible', 'floating', 'nested_navigation', 'icons', 'badges', 'grouped'],
                'version' => 1,
            ],
            [
                'key' => 'component.sidebar.inset',
                'type' => 'sidebar',
                'kind' => 'layout',
                'variant' => 'inset',
                'config' => [],
                'schema_json' => [
                    'props' => ['collapsible', 'defaultOpen', 'side', 'variant', 'items', 'groups', 'className'],
                    'items' => ['label', 'icon', 'href', 'badge', 'active', 'children'],
                    'groups' => ['label', 'items'],
                ],
                'defaults_json' => [
                    'collapsible' => true,
                    'defaultOpen' => true,
                    'side' => 'left',
                    'variant' => 'inset',
                ],
                'capabilities_json' => ['sidebar', 'collapsible', 'inset', 'nested_navigation', 'icons', 'badges', 'grouped'],
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

        $this->command->info('Navigation components seeded successfully.');
    }
}
