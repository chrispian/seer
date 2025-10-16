<?php

namespace Database\Seeders;

use Modules\UiBuilder\app\Models\Component;
use Illuminate\Database\Seeder;

class AdvancedComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'key' => 'component.data-table.default',
                'type' => 'data-table',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['columns', 'data', 'pagination', 'selection', 'actions', 'loading', 'emptyText', 'className'],
                    'columns' => [
                        'key' => 'string',
                        'label' => 'string',
                        'sortable' => 'boolean',
                        'filterable' => 'boolean',
                        'render' => 'text|badge|avatar|actions|custom',
                        'width' => 'string',
                        'align' => 'left|center|right',
                    ],
                    'pagination' => [
                        'enabled' => 'boolean',
                        'pageSize' => 'number',
                    ],
                    'selection' => [
                        'enabled' => 'boolean',
                        'type' => 'single|multiple',
                    ],
                    'actions' => [
                        'rowClick' => 'action',
                        'rowActions' => 'component[]',
                    ],
                ],
                'defaults_json' => [
                    'pagination' => [
                        'enabled' => false,
                        'pageSize' => 10,
                    ],
                    'selection' => [
                        'enabled' => false,
                        'type' => 'multiple',
                    ],
                    'loading' => false,
                    'emptyText' => 'No data available',
                ],
                'capabilities_json' => [
                    'sorting',
                    'filtering',
                    'pagination',
                    'row_selection',
                    'row_actions',
                    'row_click',
                    'loading_state',
                    'empty_state',
                    'custom_renderers',
                    'badge_cells',
                    'avatar_cells',
                    'dropdown_actions',
                ],
                'version' => 1,
            ],
            [
                'key' => 'component.data-table.paginated',
                'type' => 'data-table',
                'kind' => 'composite',
                'variant' => 'paginated',
                'config' => [],
                'schema_json' => [
                    'props' => ['columns', 'data', 'pagination', 'selection', 'actions', 'loading', 'emptyText', 'className'],
                ],
                'defaults_json' => [
                    'pagination' => [
                        'enabled' => true,
                        'pageSize' => 10,
                    ],
                    'selection' => [
                        'enabled' => false,
                        'type' => 'multiple',
                    ],
                ],
                'capabilities_json' => ['sorting', 'filtering', 'pagination', 'row_actions', 'loading_state'],
                'version' => 1,
            ],
            [
                'key' => 'component.data-table.selectable',
                'type' => 'data-table',
                'kind' => 'composite',
                'variant' => 'selectable',
                'config' => [],
                'schema_json' => [
                    'props' => ['columns', 'data', 'pagination', 'selection', 'actions', 'loading', 'emptyText', 'className'],
                ],
                'defaults_json' => [
                    'pagination' => [
                        'enabled' => true,
                        'pageSize' => 10,
                    ],
                    'selection' => [
                        'enabled' => true,
                        'type' => 'multiple',
                    ],
                ],
                'capabilities_json' => ['sorting', 'pagination', 'row_selection', 'multiple_selection', 'row_actions'],
                'version' => 1,
            ],
            [
                'key' => 'component.chart.bar',
                'type' => 'chart',
                'kind' => 'composite',
                'variant' => 'bar',
                'config' => [],
                'schema_json' => [
                    'props' => ['chartType', 'data', 'title', 'legend', 'colors', 'height', 'xAxisKey', 'yAxisKey', 'showGrid', 'showTooltip', 'className'],
                    'data' => [
                        'label' => 'string',
                        'value' => 'number',
                    ],
                ],
                'defaults_json' => [
                    'chartType' => 'bar',
                    'legend' => true,
                    'height' => 350,
                    'xAxisKey' => 'label',
                    'yAxisKey' => 'value',
                    'showGrid' => true,
                    'showTooltip' => true,
                ],
                'capabilities_json' => ['bar_chart', 'responsive', 'legend', 'tooltip', 'grid', 'custom_colors', 'configurable_axes'],
                'version' => 1,
            ],
            [
                'key' => 'component.chart.line',
                'type' => 'chart',
                'kind' => 'composite',
                'variant' => 'line',
                'config' => [],
                'schema_json' => [
                    'props' => ['chartType', 'data', 'title', 'legend', 'colors', 'height', 'xAxisKey', 'yAxisKey', 'showGrid', 'showTooltip', 'className'],
                ],
                'defaults_json' => [
                    'chartType' => 'line',
                    'legend' => true,
                    'height' => 350,
                    'showGrid' => true,
                    'showTooltip' => true,
                ],
                'capabilities_json' => ['line_chart', 'responsive', 'legend', 'tooltip', 'grid', 'smooth_curves'],
                'version' => 1,
            ],
            [
                'key' => 'component.chart.pie',
                'type' => 'chart',
                'kind' => 'composite',
                'variant' => 'pie',
                'config' => [],
                'schema_json' => [
                    'props' => ['chartType', 'data', 'title', 'legend', 'colors', 'height', 'className'],
                ],
                'defaults_json' => [
                    'chartType' => 'pie',
                    'legend' => true,
                    'height' => 350,
                    'showTooltip' => true,
                ],
                'capabilities_json' => ['pie_chart', 'responsive', 'legend', 'tooltip', 'custom_colors', 'labels'],
                'version' => 1,
            ],
            [
                'key' => 'component.chart.donut',
                'type' => 'chart',
                'kind' => 'composite',
                'variant' => 'donut',
                'config' => [],
                'schema_json' => [
                    'props' => ['chartType', 'data', 'title', 'legend', 'colors', 'height', 'className'],
                ],
                'defaults_json' => [
                    'chartType' => 'donut',
                    'legend' => true,
                    'height' => 350,
                    'showTooltip' => true,
                ],
                'capabilities_json' => ['donut_chart', 'responsive', 'legend', 'tooltip', 'custom_colors', 'labels'],
                'version' => 1,
            ],
            [
                'key' => 'component.chart.area',
                'type' => 'chart',
                'kind' => 'composite',
                'variant' => 'area',
                'config' => [],
                'schema_json' => [
                    'props' => ['chartType', 'data', 'title', 'legend', 'colors', 'height', 'xAxisKey', 'yAxisKey', 'showGrid', 'showTooltip', 'className'],
                ],
                'defaults_json' => [
                    'chartType' => 'area',
                    'legend' => true,
                    'height' => 350,
                    'showGrid' => true,
                    'showTooltip' => true,
                ],
                'capabilities_json' => ['area_chart', 'responsive', 'legend', 'tooltip', 'grid', 'gradient_fill'],
                'version' => 1,
            ],
            [
                'key' => 'component.carousel.default',
                'type' => 'carousel',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['items', 'autoplay', 'interval', 'loop', 'showDots', 'showArrows', 'className'],
                    'items' => 'component[]',
                ],
                'defaults_json' => [
                    'autoplay' => false,
                    'interval' => 5000,
                    'loop' => true,
                    'showDots' => true,
                    'showArrows' => true,
                ],
                'capabilities_json' => [
                    'carousel',
                    'autoplay',
                    'loop',
                    'dots_navigation',
                    'arrow_navigation',
                    'touch_swipe',
                    'keyboard_nav',
                    'pause_on_hover',
                    'nested_components',
                ],
                'version' => 1,
            ],
            [
                'key' => 'component.carousel.autoplay',
                'type' => 'carousel',
                'kind' => 'composite',
                'variant' => 'autoplay',
                'config' => [],
                'schema_json' => [
                    'props' => ['items', 'autoplay', 'interval', 'loop', 'showDots', 'showArrows', 'className'],
                ],
                'defaults_json' => [
                    'autoplay' => true,
                    'interval' => 5000,
                    'loop' => true,
                    'showDots' => true,
                    'showArrows' => true,
                ],
                'capabilities_json' => ['carousel', 'autoplay', 'loop', 'pause_on_hover'],
                'version' => 1,
            ],
            [
                'key' => 'component.sonner.default',
                'type' => 'sonner',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['message', 'description', 'action', 'duration', 'position', 'variant', 'className'],
                    'action' => [
                        'label' => 'string',
                        'action' => 'action_config',
                    ],
                ],
                'defaults_json' => [
                    'duration' => 4000,
                    'variant' => 'default',
                ],
                'capabilities_json' => ['toast', 'notification', 'stacked', 'dismissible', 'action_button', 'auto_dismiss', 'position_control'],
                'version' => 1,
            ],
            [
                'key' => 'component.sonner.success',
                'type' => 'sonner',
                'kind' => 'composite',
                'variant' => 'success',
                'config' => [],
                'schema_json' => [
                    'props' => ['message', 'description', 'action', 'duration', 'className'],
                ],
                'defaults_json' => [
                    'variant' => 'success',
                    'duration' => 4000,
                ],
                'capabilities_json' => ['toast', 'notification', 'success_variant', 'icon', 'stacked'],
                'version' => 1,
            ],
            [
                'key' => 'component.sonner.error',
                'type' => 'sonner',
                'kind' => 'composite',
                'variant' => 'error',
                'config' => [],
                'schema_json' => [
                    'props' => ['message', 'description', 'action', 'duration', 'className'],
                ],
                'defaults_json' => [
                    'variant' => 'error',
                    'duration' => 6000,
                ],
                'capabilities_json' => ['toast', 'notification', 'error_variant', 'icon', 'stacked', 'longer_duration'],
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

        $this->command->info('✓ Advanced components seeded successfully');
    }
}
