<?php

return [
    'ai' => [
        'titles' => env('FRAG_INBOX_AI_TITLES', false),
        'summaries' => env('FRAG_INBOX_AI_SUMMARIES', false),
        'suggest_edit' => env('FRAG_INBOX_AI_SUGGEST_EDIT', false),
        'model' => env('FRAG_INBOX_AI_MODEL', 'gpt-4o-mini'),
        'temperature' => (float) env('FRAG_INBOX_AI_TEMPERATURE', 0.2),
    ],
];
