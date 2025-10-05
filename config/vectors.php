<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Vector Store Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default vector store driver that will be used
    | for embedding storage and retrieval. You may set this to 'auto' to
    | automatically detect the best available driver based on your database.
    |
    | Supported: "auto", "postgresql", "sqlite"
    |
    */
    'default' => env('VECTOR_STORE_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Vector Store Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the vector store drivers and their options.
    | Each driver may have different capabilities and performance characteristics.
    |
    */
    'drivers' => [
        'postgresql' => [
            'connection' => env('VECTOR_POSTGRESQL_CONNECTION', 'pgsql'),
            'index_type' => env('VECTOR_POSTGRESQL_INDEX', 'hnsw'), // hnsw, ivfflat
            'index_options' => [
                'hnsw' => [
                    'm' => 16,
                    'ef_construction' => 64,
                ],
                'ivfflat' => [
                    'lists' => 100,
                ],
            ],
            'similarity_function' => env('VECTOR_POSTGRESQL_SIMILARITY', 'cosine'), // cosine, l2, inner_product
        ],

        'sqlite' => [
            'connection' => env('VECTOR_SQLITE_CONNECTION', 'sqlite'),
            'extension_path' => env('SQLITE_VEC_EXTENSION_PATH'),
            'auto_load_extension' => env('SQLITE_VEC_AUTO_LOAD', true),
            'index_type' => env('VECTOR_SQLITE_INDEX', 'vec0'),
            'similarity_function' => env('VECTOR_SQLITE_SIMILARITY', 'cosine'), // cosine, l2
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hybrid Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for hybrid search that combines vector similarity
    | with text search capabilities.
    |
    */
    'hybrid_search' => [
        'default_vector_weight' => env('HYBRID_SEARCH_VECTOR_WEIGHT', 0.7),
        'default_text_weight' => env('HYBRID_SEARCH_TEXT_WEIGHT', 0.3),
        'similarity_threshold' => env('HYBRID_SEARCH_THRESHOLD', 0.5),
        'max_results' => env('HYBRID_SEARCH_MAX_RESULTS', 50),
        'enable_fallback' => env('HYBRID_SEARCH_ENABLE_FALLBACK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Text Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for full-text search capabilities per database.
    |
    */
    'text_search' => [
        'postgresql' => [
            'language' => env('POSTGRESQL_FTS_LANGUAGE', 'english'),
            'ranking_function' => env('POSTGRESQL_FTS_RANKING', 'ts_rank_cd'),
        ],
        'sqlite' => [
            'enable_fts5' => env('SQLITE_FTS5_ENABLED', true),
            'tokenizer' => env('SQLITE_FTS5_TOKENIZER', 'porter'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Dimensions and Models
    |--------------------------------------------------------------------------
    |
    | Configuration for embedding dimensions and supported models.
    |
    */
    'embeddings' => [
        'default_dimensions' => env('VECTOR_DIMENSIONS', 1536),
        'supported_models' => [
            'openai' => [
                'text-embedding-ada-002' => 1536,
                'text-embedding-3-small' => 1536,
                'text-embedding-3-large' => 3072,
            ],
            'ollama' => [
                'nomic-embed-text' => 768,
                'mxbai-embed-large' => 1024,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Detection and Capabilities
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic feature detection and capability management.
    |
    */
    'capabilities' => [
        'cache_detection_results' => env('VECTOR_CACHE_CAPABILITIES', true),
        'cache_ttl' => env('VECTOR_CAPABILITIES_CACHE_TTL', 3600), // 1 hour
        'detection_timeout' => env('VECTOR_DETECTION_TIMEOUT', 5), // seconds
        'retry_failed_detection' => env('VECTOR_RETRY_DETECTION', true),
        'log_capability_changes' => env('VECTOR_LOG_CAPABILITY_CHANGES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance and Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for performance optimization and resource management.
    |
    */
    'performance' => [
        'batch_size' => env('VECTOR_BATCH_SIZE', 100),
        'enable_concurrent_operations' => env('VECTOR_ENABLE_CONCURRENT', true),
        'max_concurrent_operations' => env('VECTOR_MAX_CONCURRENT', 5),
        'query_timeout' => env('VECTOR_QUERY_TIMEOUT', 30), // seconds
        'enable_query_cache' => env('VECTOR_ENABLE_QUERY_CACHE', false),
        'query_cache_ttl' => env('VECTOR_QUERY_CACHE_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Development and Debugging
    |--------------------------------------------------------------------------
    |
    | Settings for development, debugging, and diagnostics.
    |
    */
    'debug' => [
        'log_queries' => env('VECTOR_LOG_QUERIES', false),
        'log_performance' => env('VECTOR_LOG_PERFORMANCE', false),
        'enable_diagnostics' => env('VECTOR_ENABLE_DIAGNOSTICS', true),
        'benchmark_queries' => env('VECTOR_BENCHMARK_QUERIES', false),
    ],
];
