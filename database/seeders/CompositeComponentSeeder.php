<?php

namespace Database\Seeders;

use Modules\UiBuilder\app\Models\Component;
use Illuminate\Database\Seeder;

class CompositeComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'key' => 'component.dialog.default',
                'type' => 'dialog',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'trigger', 'content', 'footer', 'size', 'closeButton', 'defaultOpen', 'className'],
                    'content' => true,
                    'footer' => true,
                    'trigger' => true,
                ],
                'defaults_json' => [
                    'size' => 'lg',
                    'closeButton' => true,
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['modal', 'overlay', 'portal', 'focus_trap', 'esc_close', 'backdrop_close', 'nested_components'],
                'version' => 1,
            ],
            [
                'key' => 'component.dialog.sm',
                'type' => 'dialog',
                'kind' => 'composite',
                'variant' => 'sm',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'trigger', 'content', 'footer', 'size', 'closeButton', 'defaultOpen', 'className'],
                    'content' => true,
                    'footer' => true,
                    'trigger' => true,
                ],
                'defaults_json' => [
                    'size' => 'sm',
                    'closeButton' => true,
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['modal', 'overlay', 'portal', 'focus_trap', 'esc_close', 'backdrop_close', 'nested_components'],
                'version' => 1,
            ],
            [
                'key' => 'component.dialog.full',
                'type' => 'dialog',
                'kind' => 'composite',
                'variant' => 'full',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'trigger', 'content', 'footer', 'size', 'closeButton', 'defaultOpen', 'className'],
                    'content' => true,
                    'footer' => true,
                    'trigger' => true,
                ],
                'defaults_json' => [
                    'size' => 'full',
                    'closeButton' => true,
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['modal', 'overlay', 'portal', 'focus_trap', 'esc_close', 'backdrop_close', 'nested_components'],
                'version' => 1,
            ],
            [
                'key' => 'component.popover.default',
                'type' => 'popover',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['trigger', 'content', 'side', 'align', 'defaultOpen', 'className'],
                    'content' => true,
                    'trigger' => true,
                ],
                'defaults_json' => [
                    'side' => 'bottom',
                    'align' => 'center',
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['floating', 'portal', 'positioning', 'arrow', 'nested_components'],
                'version' => 1,
            ],
            [
                'key' => 'component.tooltip.default',
                'type' => 'tooltip',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['content', 'side', 'delay', 'className'],
                    'children' => true,
                ],
                'defaults_json' => [
                    'side' => 'top',
                    'delay' => 0,
                ],
                'capabilities_json' => ['floating', 'portal', 'positioning', 'delayed_show', 'hover_trigger'],
                'version' => 1,
            ],
            [
                'key' => 'component.sheet.right',
                'type' => 'sheet',
                'kind' => 'composite',
                'variant' => 'right',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'side', 'trigger', 'content', 'footer', 'defaultOpen', 'className'],
                    'content' => true,
                    'footer' => true,
                    'trigger' => true,
                ],
                'defaults_json' => [
                    'side' => 'right',
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['overlay', 'portal', 'slide_animation', 'esc_close', 'backdrop_close', 'nested_components', 'full_height'],
                'version' => 1,
            ],
            [
                'key' => 'component.sheet.left',
                'type' => 'sheet',
                'kind' => 'composite',
                'variant' => 'left',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'side', 'trigger', 'content', 'footer', 'defaultOpen', 'className'],
                    'content' => true,
                    'footer' => true,
                    'trigger' => true,
                ],
                'defaults_json' => [
                    'side' => 'left',
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['overlay', 'portal', 'slide_animation', 'esc_close', 'backdrop_close', 'nested_components', 'full_height'],
                'version' => 1,
            ],
            [
                'key' => 'component.drawer.bottom',
                'type' => 'drawer',
                'kind' => 'composite',
                'variant' => 'bottom',
                'config' => [],
                'schema_json' => [
                    'props' => ['title', 'description', 'trigger', 'content', 'footer', 'direction', 'defaultOpen', 'className'],
                    'content' => true,
                    'footer' => true,
                    'trigger' => true,
                ],
                'defaults_json' => [
                    'direction' => 'bottom',
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['overlay', 'portal', 'slide_animation', 'esc_close', 'backdrop_close', 'nested_components', 'mobile_friendly'],
                'version' => 1,
            ],
            [
                'key' => 'component.navigation-menu.default',
                'type' => 'navigation-menu',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['items', 'orientation', 'className'],
                    'items' => [
                        'label' => 'string',
                        'trigger' => 'hover|click',
                        'content' => 'component[]',
                        'href' => 'string',
                        'items' => [
                            'label' => 'string',
                            'href' => 'string',
                            'description' => 'string',
                            'icon' => 'string',
                        ],
                    ],
                ],
                'defaults_json' => [
                    'orientation' => 'horizontal',
                ],
                'capabilities_json' => ['mega_menu', 'hover_trigger', 'click_trigger', 'nested_items', 'keyboard_navigation', 'icons', 'descriptions', 'rich_content'],
                'version' => 1,
            ],
            [
                'key' => 'component.command.default',
                'type' => 'command',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['placeholder', 'emptyText', 'groups', 'open', 'defaultOpen', 'showShortcut', 'className'],
                    'groups' => [
                        'heading' => 'string',
                        'items' => [
                            'label' => 'string',
                            'icon' => 'string',
                            'shortcut' => 'string',
                            'value' => 'string',
                            'disabled' => 'boolean',
                        ],
                    ],
                    'actions' => true,
                ],
                'defaults_json' => [
                    'placeholder' => 'Type a command or search...',
                    'emptyText' => 'No results found.',
                    'showShortcut' => true,
                    'defaultOpen' => false,
                ],
                'capabilities_json' => ['fuzzy_search', 'keyboard_shortcuts', 'grouped_commands', 'icons', 'portal', 'keyboard_navigation', 'cmd_k_trigger', 'actions'],
                'version' => 1,
            ],
            [
                'key' => 'component.combobox.default',
                'type' => 'combobox',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['placeholder', 'emptyText', 'searchPlaceholder', 'options', 'value', 'defaultValue', 'searchable', 'disabled', 'className'],
                    'options' => [
                        'value' => 'string',
                        'label' => 'string',
                        'icon' => 'string',
                        'disabled' => 'boolean',
                    ],
                    'actions' => true,
                ],
                'defaults_json' => [
                    'placeholder' => 'Select option...',
                    'emptyText' => 'No option found.',
                    'searchPlaceholder' => 'Search...',
                    'searchable' => true,
                    'disabled' => false,
                ],
                'capabilities_json' => ['searchable', 'autocomplete', 'keyboard_navigation', 'icons', 'disabled_options', 'actions', 'popover'],
                'version' => 1,
            ],
            [
                'key' => 'component.dropdown-menu.default',
                'type' => 'dropdown-menu',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['trigger', 'items', 'align', 'side', 'className'],
                    'trigger' => 'component',
                    'items' => ['type', 'label', 'icon', 'shortcut', 'disabled', 'checked', 'value', 'items', 'action'],
                ],
                'defaults_json' => [
                    'align' => 'center',
                    'side' => 'bottom',
                ],
                'capabilities_json' => [
                    'dropdown',
                    'menu_items',
                    'checkbox_items',
                    'radio_items',
                    'separators',
                    'labels',
                    'submenus',
                    'icons',
                    'shortcuts',
                    'actions',
                    'keyboard_nav',
                ],
                'version' => 1,
            ],
            [
                'key' => 'component.context-menu.default',
                'type' => 'context-menu',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['items', 'className'],
                    'children' => true,
                    'items' => ['type', 'label', 'icon', 'shortcut', 'disabled', 'checked', 'value', 'items', 'action'],
                ],
                'defaults_json' => [],
                'capabilities_json' => [
                    'context_menu',
                    'right_click',
                    'menu_items',
                    'checkbox_items',
                    'radio_items',
                    'separators',
                    'labels',
                    'submenus',
                    'icons',
                    'shortcuts',
                    'actions',
                    'keyboard_nav',
                ],
                'version' => 1,
            ],
            [
                'key' => 'component.menubar.default',
                'type' => 'menubar',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['menus', 'className'],
                    'menus' => ['label', 'items'],
                    'items' => ['type', 'label', 'icon', 'shortcut', 'disabled', 'checked', 'value', 'items', 'action'],
                ],
                'defaults_json' => [],
                'capabilities_json' => [
                    'menubar',
                    'application_menu',
                    'multiple_menus',
                    'menu_items',
                    'checkbox_items',
                    'radio_items',
                    'separators',
                    'labels',
                    'submenus',
                    'icons',
                    'shortcuts',
                    'actions',
                    'keyboard_nav',
                ],
                'version' => 1,
            ],
            [
                'key' => 'component.hover-card.default',
                'type' => 'hover-card',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['trigger', 'content', 'openDelay', 'closeDelay', 'side', 'align', 'className'],
                    'trigger' => 'component',
                    'content' => 'component_array',
                ],
                'defaults_json' => [
                    'openDelay' => 200,
                    'closeDelay' => 300,
                    'side' => 'bottom',
                    'align' => 'center',
                ],
                'capabilities_json' => [
                    'hover_card',
                    'rich_content',
                    'delays',
                    'positioning',
                    'animations',
                    'nested_components',
                ],
                'version' => 1,
            ],
        ];

        foreach ($components as $componentData) {
            $componentData['hash'] = md5(json_encode($componentData['schema_json']) . json_encode($componentData['defaults_json']));
            
            Component::updateOrCreate(
                ['key' => $componentData['key']],
                $componentData
            );
        }

        $this->command->info('✓ Composite components seeded successfully');
    }
}
