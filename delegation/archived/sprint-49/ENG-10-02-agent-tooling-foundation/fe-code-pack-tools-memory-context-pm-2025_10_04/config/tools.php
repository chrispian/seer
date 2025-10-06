<?php

return [
    // Global feature toggles
    'enable_shell_exec' => false, // set true only with approval gates enabled
    'allow_write_paths' => [
        storage_path('app/exports'),
    ],
    // Capability scopes (attach to agent tokens)
    'scopes' => [
        'read/db.query',
        'export.read',
        'memory.read',
        'memory.write',
        'shell.exec',
        'fs.read',
        'fs.write',
        'repo.write',
        'scheduler',
        'web.read',
        'notify',
        'secrets.read',
        'metrics',
    ],
    // Contract location for runtime schema loading
    'contracts_path' => base_path('resources/tools/contracts'),
];
