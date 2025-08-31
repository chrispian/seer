<?php

// config/fragments.php
return [
    'embeddings' => [
        'provider' => env('EMBEDDINGS_PROVIDER','openai'),
        'model'    => env('OPENAI_EMBEDDING_MODEL','text-embedding-3-small'),
        'version'  => env('EMBEDDINGS_VERSION','1'),
    ],
];



