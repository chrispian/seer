<?php

return [
    'timeout_minutes' => env('APPROVAL_TIMEOUT_MINUTES', 5),

    'inline_approval' => [
        'max_characters' => env('APPROVAL_INLINE_MAX_CHARS', 500),
        'max_words' => env('APPROVAL_INLINE_MAX_WORDS', 100),
        'max_lines' => env('APPROVAL_INLINE_MAX_LINES', 15),
    ],

    'modal_preview' => [
        'enabled' => env('APPROVAL_MODAL_ENABLED', true),
        'preview_words' => env('APPROVAL_MODAL_PREVIEW_WORDS', 50),
        'show_stats' => env('APPROVAL_MODAL_SHOW_STATS', true),
    ],

    'natural_language_approval' => [
        'enabled' => env('APPROVAL_NL_ENABLED', true),
        'confidence_threshold' => env('APPROVAL_NL_CONFIDENCE', 0.8),
        'approval_keywords' => [
            'yes',
            'approve',
            'approved',
            'go ahead',
            'do it',
            'proceed',
            'ok',
            'okay',
            'sure',
            'yep',
            'yeah',
            'affirmative',
        ],
        'rejection_keywords' => [
            'no',
            'nope',
            'reject',
            'rejected',
            'cancel',
            'cancelled',
            'stop',
            'don\'t',
            'do not',
            'negative',
            'abort',
        ],
    ],

    'auto_timeout' => [
        'enabled' => env('APPROVAL_AUTO_TIMEOUT_ENABLED', true),
        'cleanup_stale_hours' => env('APPROVAL_CLEANUP_HOURS', 24),
    ],
];
