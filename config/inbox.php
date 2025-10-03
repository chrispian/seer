<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inbox AI Assistant Configuration
    |--------------------------------------------------------------------------
    |
    | Configure AI-powered assistance for inbox processing. These features
    | can help automatically suggest titles, summaries, and categorization
    | for fragments in the inbox.
    |
    */

    'ai' => [
        // Enable/disable AI features
        'titles_enabled' => env('FRAG_INBOX_AI_TITLES', false),
        'summaries_enabled' => env('FRAG_INBOX_AI_SUMMARIES', false),
        'suggest_edits_enabled' => env('FRAG_INBOX_AI_SUGGEST_EDIT', false),

        // AI model configuration
        'model' => env('FRAG_INBOX_AI_MODEL', 'gpt-4o-mini'),
        'temperature' => env('FRAG_INBOX_AI_TEMPERATURE', 0.2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbox Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default behavior for inbox processing.
    |
    */

    'defaults' => [
        // Default pagination size
        'per_page' => 25,

        // Default sort order for inbox
        'sort_by' => 'inbox_at',
        'sort_direction' => 'desc',

        // Batch processing limits
        'max_bulk_operations' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fragment Type Mappings
    |--------------------------------------------------------------------------
    |
    | Available fragment types for AI suggestions and validation.
    |
    */

    'fragment_types' => [
        'todo',
        'note', 
        'link',
        'document',
        'event',
        'contact',
        'log',
        'ai_response',
        'media',
    ],
];