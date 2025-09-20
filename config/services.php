<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    ],

    'ollama' => [
        'base' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'base' => env('ANTHROPIC_BASE', 'https://api.anthropic.com'),
        // Used for the Messages API header; keep configurable
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        // Convenience defaults for your orchestrator/Prism wrapper
        'default_model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-latest'),
        'stream' => env('ANTHROPIC_STREAM', true),
        // Optional: org/team scoping if you use it
        'organization' => env('ANTHROPIC_ORG'),
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base' => env('OPENROUTER_BASE', 'https://openrouter.ai/api/v1'),
        // Optional headers they like for routing/attribution
        'referer' => env('OPENROUTER_REFERER'),  // e.g. https://yourapp.dev
        'title' => env('OPENROUTER_TITLE', 'Fragments Engine'),
    ],

];
