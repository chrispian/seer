<?php

return [
    'paths' => [
        'type_packs' => base_path('fragments/types'),
        'user_type_packs' => storage_path('fragments/types'),
        'command_packs' => base_path('fragments/commands'),
        'user_command_packs' => storage_path('fragments/commands'),
    ],
    'tools' => [
        'enabled' => true,
        'allowed' => [ // slugs allowed globally; further restricted per-command
            'shell', 'fs', 'mcp', 'gmail', 'todoist',
        ],
        'shell' => [
            'enabled' => false,              // default off; enable per env
            'workdir' => storage_path('app/agent'),
            'timeout_seconds' => 15,
            'memory_limit_mb' => 256,
            'allowlist' => ['ls', 'cat', 'php', 'node', 'composer', 'git'], // keep tight
        ],
        'fs' => [
            'root' => storage_path('app/agent'),
        ],
        'mcp' => [
            'endpoints' => [
                // 'gmail' => ['url' => 'http://localhost:9000', 'token' => env('MCP_GMAIL_TOKEN')],
            ],
        ],
    ],
];
