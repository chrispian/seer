<?php

// config/fragments.php
return [
    'embeddings' => [
        'enabled' => env('EMBEDDINGS_ENABLED', false),
        'provider' => env('EMBEDDINGS_PROVIDER', 'openai'),
        'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'version' => env('EMBEDDINGS_VERSION', '1'),
    ],

    'models' => [
        // Global model selection settings
        'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
        'default_text_model' => env('AI_DEFAULT_TEXT_MODEL', 'gpt-4o-mini'),
        'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'ollama'),
        'fallback_text_model' => env('AI_FALLBACK_TEXT_MODEL', 'llama3:latest'),

        // Operation-specific provider/model overrides
        'operations' => [
            'enrichment' => [
                'provider' => env('AI_ENRICHMENT_PROVIDER'), // null = use default
                'model' => env('AI_ENRICHMENT_MODEL'),       // null = use default
                'enabled' => env('AI_ENRICHMENT_ENABLED', true),
            ],
            'classification' => [
                'provider' => env('AI_CLASSIFICATION_PROVIDER'),
                'model' => env('AI_CLASSIFICATION_MODEL'),
                'enabled' => env('AI_CLASSIFICATION_ENABLED', true),
            ],
            'embedding' => [
                'provider' => env('AI_EMBEDDING_PROVIDER'),
                'model' => env('AI_EMBEDDING_MODEL'),
                'enabled' => env('AI_EMBEDDING_ENABLED', true),
            ],
            'tagging' => [
                'provider' => env('AI_TAGGING_PROVIDER'),
                'model' => env('AI_TAGGING_MODEL'),
                'enabled' => env('AI_TAGGING_ENABLED', false), // Currently rule-based
            ],
            'title_generation' => [
                'provider' => env('AI_TITLE_GENERATION_PROVIDER'),
                'model' => env('AI_TITLE_GENERATION_MODEL'),
                'enabled' => env('AI_TITLE_GENERATION_ENABLED', false),
            ],
        ],

        // Deterministic AI parameters per operation type
        'parameters' => [
            'classification' => [
                'temperature' => env('AI_CLASSIFICATION_TEMPERATURE', 0.1),
                'top_p' => env('AI_CLASSIFICATION_TOP_P', 0.95),
                'max_tokens' => env('AI_CLASSIFICATION_MAX_TOKENS', 500),
            ],
            'enrichment' => [
                'temperature' => env('AI_ENRICHMENT_TEMPERATURE', 0.3),
                'top_p' => env('AI_ENRICHMENT_TOP_P', 0.95),
                'max_tokens' => env('AI_ENRICHMENT_MAX_TOKENS', 1000),
            ],
            'embedding' => [
                // Embeddings don't use temperature/top_p but may have other params
                'dimensions' => env('AI_EMBEDDING_DIMENSIONS', null),
            ],
            'tagging' => [
                'temperature' => env('AI_TAGGING_TEMPERATURE', 0.2),
                'top_p' => env('AI_TAGGING_TOP_P', 0.95),
                'max_tokens' => env('AI_TAGGING_MAX_TOKENS', 300),
            ],
            'title_generation' => [
                'temperature' => env('AI_TITLE_GENERATION_TEMPERATURE', 0.1),
                'top_p' => env('AI_TITLE_GENERATION_TOP_P', 0.95),
                'max_tokens' => env('AI_TITLE_GENERATION_MAX_TOKENS', 100),
            ],
        ],

        // Provider catalog with capabilities
        'providers' => [
            'openai' => [
                'name' => 'OpenAI',
                'text_models' => [
                    'gpt-4o' => ['name' => 'GPT-4o', 'context_length' => 128000],
                    'gpt-4o-mini' => ['name' => 'GPT-4o Mini', 'context_length' => 128000],
                    'gpt-4-turbo' => ['name' => 'GPT-4 Turbo', 'context_length' => 128000],
                    'gpt-3.5-turbo' => ['name' => 'GPT-3.5 Turbo', 'context_length' => 16385],
                ],
                'embedding_models' => [
                    'text-embedding-3-large' => ['name' => 'Text Embedding 3 Large', 'dimensions' => 3072],
                    'text-embedding-3-small' => ['name' => 'Text Embedding 3 Small', 'dimensions' => 1536],
                    'text-embedding-ada-002' => ['name' => 'Text Embedding Ada 002', 'dimensions' => 1536],
                ],
                'config_keys' => ['OPENAI_API_KEY'],
            ],

            'anthropic' => [
                'name' => 'Anthropic',
                'text_models' => [
                    'claude-3-5-sonnet-latest' => ['name' => 'Claude 3.5 Sonnet', 'context_length' => 200000],
                    'claude-3-5-haiku-latest' => ['name' => 'Claude 3.5 Haiku', 'context_length' => 200000],
                    'claude-3-opus-latest' => ['name' => 'Claude 3 Opus', 'context_length' => 200000],
                ],
                'embedding_models' => [],
                'config_keys' => ['ANTHROPIC_API_KEY'],
            ],

            'ollama' => [
                'name' => 'Ollama',
                'text_models' => [
                    'llama3:latest' => ['name' => 'Llama 3 Latest', 'context_length' => 8192],
                    'llama3:8b' => ['name' => 'Llama 3 8B', 'context_length' => 8192],
                    'llama3:70b' => ['name' => 'Llama 3 70B', 'context_length' => 8192],
                    'codellama:latest' => ['name' => 'Code Llama Latest', 'context_length' => 16384],
                ],
                'embedding_models' => [
                    'nomic-embed-text' => ['name' => 'Nomic Embed Text', 'dimensions' => 768],
                    'all-minilm' => ['name' => 'All MiniLM', 'dimensions' => 384],
                ],
                'config_keys' => ['OLLAMA_BASE_URL'],
            ],

            'openrouter' => [
                'name' => 'OpenRouter',
                'text_models' => [
                    'anthropic/claude-3.5-sonnet' => ['name' => 'Claude 3.5 Sonnet', 'context_length' => 200000],
                    'openai/gpt-4o' => ['name' => 'GPT-4o', 'context_length' => 128000],
                    'meta-llama/llama-3.1-70b-instruct' => ['name' => 'Llama 3.1 70B', 'context_length' => 131072],
                ],
                'embedding_models' => [],
                'config_keys' => ['OPENROUTER_API_KEY'],
            ],
        ],

        // Selection strategy weights
        'selection_strategy' => [
            'command_override' => 100,    // Highest priority
            'project_preference' => 80,
            'vault_preference' => 60,
            'global_default' => 40,
            'fallback' => 20,             // Lowest priority
        ],

        // UI transparency settings
        'ui' => [
            'show_model_info' => env('AI_SHOW_MODEL_INFO', true),
            'show_in_toasts' => env('AI_SHOW_IN_TOASTS', true),
            'show_in_fragments' => env('AI_SHOW_IN_FRAGMENTS', true),
            'show_in_chat_sessions' => env('AI_SHOW_IN_CHAT_SESSIONS', true),
        ],
    ],

    'types' => [
        // Type system configuration
        'validation' => [
            'enabled' => env('FRAGMENT_TYPE_VALIDATION', false),
            'strict_mode' => env('FRAGMENT_TYPE_STRICT', false),
            'cache_ttl' => env('FRAGMENT_TYPE_CACHE_TTL', 3600),
        ],

        // Search paths for type packs (in precedence order)
        'search_paths' => [
            'storage/app/fragments/types',  // Storage override
            'fragments/types',              // Project types
            'modules/*/fragments/types',    // Module types
        ],

        // Performance optimization
        'hot_fields' => [
            'enabled' => env('FRAGMENT_HOT_FIELDS_ENABLED', true),
            'auto_index' => env('FRAGMENT_HOT_FIELDS_AUTO_INDEX', true),
        ],

        // Registry cache settings
        'registry' => [
            'auto_update' => env('FRAGMENT_REGISTRY_AUTO_UPDATE', true),
            'rebuild_on_deploy' => env('FRAGMENT_REGISTRY_REBUILD_DEPLOY', true),
        ],
    ],
];
