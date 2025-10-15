<?php

return [
    'cache_ttl' => env('FEATURE_FLAGS_CACHE_TTL', 300),

    'flags' => [
        'ui.modal_v2' => [
            'name' => 'Modal V2 System',
            'description' => 'Enable new modal navigation and state management',
            'enabled' => env('FF_MODAL_V2', false),
        ],

        'ui.component_registry' => [
            'name' => 'Component Registry',
            'description' => 'Enable component registry for dynamic component loading',
            'enabled' => env('FF_COMPONENT_REGISTRY', true),
        ],

        'ui.type_system' => [
            'name' => 'Type System',
            'description' => 'Enable FE type system for dynamic querying',
            'enabled' => env('FF_TYPE_SYSTEM', true),
        ],

        'ui.generic_datasources' => [
            'name' => 'Generic Data Sources',
            'description' => 'Enable config-based generic data source resolution',
            'enabled' => env('FF_GENERIC_DATASOURCES', false),
        ],

        'ui.shadcn_components' => [
            'name' => 'Shadcn Component Library',
            'description' => 'Enable full shadcn component parity',
            'enabled' => env('FF_SHADCN_COMPONENTS', false),
        ],

        'ui.theme_system' => [
            'name' => 'Theme System',
            'description' => 'Enable theme tokens and customization',
            'enabled' => env('FF_THEME_SYSTEM', false),
        ],
    ],

    'environments' => [
        'local' => [
            'enabled' => true,
        ],
        'staging' => [
            'enabled' => true,
        ],
        'production' => [
            'enabled' => true,
        ],
    ],
];
