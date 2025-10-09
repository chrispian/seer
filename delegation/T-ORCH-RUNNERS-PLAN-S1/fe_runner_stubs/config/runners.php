<?php

return [
    'default_quantum_seconds' => 90,
    'attached' => [
        'openhands' => [
            'ws_url' => env('OH_WS_URL', 'ws://localhost:3000'),
            'token'  => env('OH_TOKEN'),
            'heartbeat_seconds' => 10,
        ],
    ],
];
