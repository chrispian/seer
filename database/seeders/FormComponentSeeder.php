<?php

namespace Database\Seeders;

use App\Models\FeUiComponent;
use Illuminate\Database\Seeder;

class FormComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'key' => 'component.checkbox.default',
                'type' => 'checkbox',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['label', 'checked', 'defaultChecked', 'disabled', 'required', 'name', 'value', 'className'],
                    'actions' => ['change', 'blur', 'focus'],
                ],
                'defaults_json' => [
                    'checked' => false,
                    'disabled' => false,
                    'required' => false,
                ],
                'capabilities_json' => ['editable', 'focusable', 'validatable', 'keyboard_accessible', 'two_state'],
                'version' => 1,
            ],
            [
                'key' => 'component.radio-group.default',
                'type' => 'radio-group',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['options', 'value', 'defaultValue', 'disabled', 'required', 'name', 'orientation', 'className'],
                    'actions' => ['change', 'blur', 'focus'],
                ],
                'defaults_json' => [
                    'options' => [],
                    'disabled' => false,
                    'required' => false,
                    'orientation' => 'vertical',
                ],
                'capabilities_json' => ['selectable', 'focusable', 'validatable', 'keyboard_accessible', 'single_choice'],
                'version' => 1,
            ],
            [
                'key' => 'component.switch.default',
                'type' => 'switch',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['label', 'checked', 'defaultChecked', 'disabled', 'required', 'name', 'className'],
                    'actions' => ['change', 'blur', 'focus'],
                ],
                'defaults_json' => [
                    'checked' => false,
                    'disabled' => false,
                    'required' => false,
                ],
                'capabilities_json' => ['editable', 'focusable', 'validatable', 'keyboard_accessible', 'toggle'],
                'version' => 1,
            ],
            [
                'key' => 'component.slider.default',
                'type' => 'slider',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['min', 'max', 'step', 'value', 'defaultValue', 'disabled', 'name', 'className'],
                    'actions' => ['change', 'focus'],
                ],
                'defaults_json' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 1,
                    'defaultValue' => [50],
                    'disabled' => false,
                ],
                'capabilities_json' => ['editable', 'focusable', 'keyboard_accessible', 'range_input', 'visual_feedback'],
                'version' => 1,
            ],
            [
                'key' => 'component.textarea.default',
                'type' => 'textarea',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['placeholder', 'value', 'defaultValue', 'disabled', 'readonly', 'required', 'rows', 'name', 'className'],
                    'actions' => ['change', 'blur', 'focus'],
                ],
                'defaults_json' => [
                    'rows' => 3,
                    'disabled' => false,
                    'readonly' => false,
                    'required' => false,
                ],
                'capabilities_json' => ['editable', 'focusable', 'validatable', 'keyboard_accessible', 'multiline'],
                'version' => 1,
            ],
            [
                'key' => 'component.select.default',
                'type' => 'select',
                'kind' => 'primitive',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['options', 'placeholder', 'value', 'defaultValue', 'disabled', 'required', 'name', 'className'],
                    'actions' => ['change', 'blur', 'focus'],
                ],
                'defaults_json' => [
                    'options' => [],
                    'disabled' => false,
                    'required' => false,
                ],
                'capabilities_json' => ['selectable', 'focusable', 'validatable', 'keyboard_accessible', 'dropdown', 'searchable'],
                'version' => 1,
            ],
            [
                'key' => 'component.field.default',
                'type' => 'field',
                'kind' => 'composite',
                'variant' => 'default',
                'config' => [],
                'schema_json' => [
                    'props' => ['label', 'required', 'error', 'helperText', 'className'],
                    'children' => ['input', 'textarea', 'select', 'checkbox', 'switch', 'radio-group', 'slider'],
                ],
                'defaults_json' => [
                    'required' => false,
                ],
                'capabilities_json' => ['composite', 'validatable', 'labeled', 'error_display', 'help_text'],
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

        $this->command->info('Seeded ' . count($components) . ' form components');
    }
}
