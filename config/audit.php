<?php

return [
    'notifications' => [
        'enabled' => env('AUDIT_NOTIFICATIONS_ENABLED', true),
        'mail_enabled' => env('AUDIT_MAIL_ENABLED', false),
        'slack_enabled' => env('AUDIT_SLACK_ENABLED', false),
        'slack_channel' => env('AUDIT_SLACK_CHANNEL', '#alerts'),
        'admin_email' => env('AUDIT_ADMIN_EMAIL', null),
    ],

    'destructive_commands' => [
        'migrate:fresh',
        'migrate:reset',
        'migrate:rollback',
        'db:wipe',
        'db:seed',
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
        'queue:flush',
        'queue:clear',
        'telescope:prune',
        'horizon:purge',
    ],

    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
];
