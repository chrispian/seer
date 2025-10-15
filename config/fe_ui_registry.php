<?php

return [
    'types' => [
        'page' => [
            'name' => 'Page',
            'description' => 'Full page configuration',
            'reference_table' => null,
        ],
        'component' => [
            'name' => 'Component',
            'description' => 'Reusable UI component',
            'reference_table' => 'fe_ui_components',
        ],
        'module' => [
            'name' => 'Module',
            'description' => 'Feature module grouping pages and components',
            'reference_table' => 'fe_ui_modules',
        ],
        'theme' => [
            'name' => 'Theme',
            'description' => 'Visual theme with token overrides',
            'reference_table' => 'fe_ui_themes',
        ],
        'datasource' => [
            'name' => 'Data Source',
            'description' => 'Data source configuration',
            'reference_table' => 'fe_ui_datasources',
        ],
        'action' => [
            'name' => 'Action',
            'description' => 'Interactive action definition',
            'reference_table' => 'fe_ui_actions',
        ],
    ],

    'auto_register' => env('FE_UI_AUTO_REGISTER', true),

    'versioning' => [
        'enabled' => env('FE_UI_VERSIONING', true),
        'strategy' => 'semver',
    ],

    'validation' => [
        'enabled' => env('FE_UI_VALIDATION', true),
        'strict_mode' => env('FE_UI_VALIDATION_STRICT', false),
    ],

    'cache' => [
        'enabled' => env('FE_UI_CACHE_ENABLED', true),
        'ttl' => env('FE_UI_CACHE_TTL', 3600),
    ],
];
