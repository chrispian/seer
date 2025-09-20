<?php

use App\Models\Fragment;
use App\Models\Project;
use App\Models\Type;
use App\Services\AI\Embeddings;
use App\Services\AI\TypeInferenceService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Set up test configuration
    Config::set('fragments.models', [
        'default_provider' => 'openai',
        'default_text_model' => 'gpt-4o-mini',
        'fallback_provider' => 'ollama',
        'fallback_text_model' => 'llama3:latest',
        'providers' => [
            'openai' => [
                'name' => 'OpenAI',
                'text_models' => [
                    'gpt-4o-mini' => ['name' => 'GPT-4o Mini', 'context_length' => 128000],
                ],
                'embedding_models' => [
                    'text-embedding-3-small' => ['name' => 'Text Embedding 3 Small', 'dimensions' => 1536],
                ],
                'config_keys' => ['OPENAI_API_KEY'],
            ],
            'ollama' => [
                'name' => 'Ollama',
                'text_models' => [
                    'llama3:latest' => ['name' => 'Llama 3 Latest', 'context_length' => 8192],
                ],
                'embedding_models' => [
                    'nomic-embed-text' => ['name' => 'Nomic Embed Text', 'dimensions' => 768],
                ],
                'config_keys' => ['OLLAMA_BASE_URL'],
            ],
        ],
        'selection_strategy' => [
            'command_override' => 100,
            'project_preference' => 80,
            'vault_preference' => 60,
            'global_default' => 40,
            'fallback' => 20,
        ],
    ]);

    Config::set('services.openai.key', 'test-openai-key');
    Config::set('services.ollama.base', 'http://localhost:11434');

    // Create log type for testing
    Type::factory()->create([
        'value' => 'log',
        'label' => 'Log',
    ]);
});

test('embeddings service uses model selection and persists metadata', function () {
    Http::fake([
        'api.openai.com/v1/embeddings' => Http::response([
            'data' => [
                ['embedding' => array_fill(0, 1536, 0.1)],
            ],
        ]),
    ]);

    $embeddings = app(Embeddings::class);
    $result = $embeddings->embed('test text', ['vault' => 'default']);

    expect($result)->toHaveKey('provider', 'openai');
    expect($result)->toHaveKey('model', 'text-embedding-3-small');
    expect($result)->toHaveKey('vector');
    expect($result['dims'])->toBe(1536);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.openai.com/v1/embeddings' &&
               $request['model'] === 'text-embedding-3-small';
    });
});

test('type inference service uses model selection and persists metadata', function () {
    // Mock successful AI response
    Http::fake([
        'localhost:11434/api/generate' => Http::response([
            'response' => json_encode([
                'type' => 'log',
                'confidence' => 0.9,
                'reasoning' => 'This looks like a log entry',
            ]),
        ]),
    ]);

    $fragment = Fragment::factory()->create([
        'message' => 'Test fragment message',
        'vault' => 'default',
        'type' => null,
        'type_id' => null,
        'model_provider' => null,
        'model_name' => null,
    ]);

    $typeInference = app(TypeInferenceService::class);
    $result = $typeInference->applyTypeToFragment($fragment);

    $result->refresh();

    expect($result->type)->toBe('log');
    expect($result->model_provider)->toBe('ollama');
    expect($result->model_name)->toBe('llama3:latest');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'localhost:11434/api/generate');
    });
});

test('fragment processing pipeline persists model metadata', function () {
    // Mock AI responses
    Http::fake([
        'localhost:11434/api/generate' => Http::response([
            'response' => json_encode([
                'type' => 'log',
                'confidence' => 0.95,
                'reasoning' => 'This is clearly a log entry',
            ]),
        ]),
    ]);

    $fragment = Fragment::factory()->create([
        'message' => 'Application started successfully',
        'vault' => 'default',
        'type' => null,
        'type_id' => null,
        'model_provider' => null,
        'model_name' => null,
    ]);

    // Process the fragment (simulating the pipeline)
    $typeInference = app(TypeInferenceService::class);
    $processedFragment = $typeInference->applyTypeToFragment($fragment);

    expect($processedFragment->model_provider)->not()->toBeNull();
    expect($processedFragment->model_name)->not()->toBeNull();
    expect($processedFragment->type)->toBe('log');
});

test('model selection respects project preferences', function () {
    $project = Project::factory()->create([
        'metadata' => [
            'ai_model' => [
                'provider' => 'ollama',
                'model' => 'llama3:latest',
            ],
        ],
    ]);

    Http::fake([
        'localhost:11434/api/generate' => Http::response([
            'response' => json_encode([
                'type' => 'log',
                'confidence' => 0.8,
                'reasoning' => 'Processed with Ollama',
            ]),
        ]),
    ]);

    $fragment = Fragment::factory()->create([
        'message' => 'Test message for project',
        'vault' => 'default',
        'project_id' => $project->id,
        'type' => null,
        'type_id' => null,
        'model_provider' => null,
        'model_name' => null,
    ]);

    $typeInference = app(TypeInferenceService::class);
    $result = $typeInference->applyTypeToFragment($fragment);

    $result->refresh();

    expect($result->model_provider)->toBe('ollama');
    expect($result->model_name)->toBe('llama3:latest');
});

test('model selection falls back gracefully when provider unavailable', function () {
    // Disable OpenAI to force fallback
    Config::set('services.openai.key', null);

    Http::fake([
        'localhost:11434/api/generate' => Http::response([
            'response' => json_encode([
                'type' => 'log',
                'confidence' => 0.7,
                'reasoning' => 'Fallback provider used',
            ]),
        ]),
    ]);

    $fragment = Fragment::factory()->create([
        'message' => 'Test fallback scenario',
        'vault' => 'default',
        'type' => null,
        'type_id' => null,
        'model_provider' => null,
        'model_name' => null,
    ]);

    $typeInference = app(TypeInferenceService::class);
    $result = $typeInference->applyTypeToFragment($fragment);

    $result->refresh();

    // Should use fallback provider (Ollama)
    expect($result->model_provider)->toBe('ollama');
    expect($result->model_name)->toBe('llama3:latest');
});

test('embedding service respects model override', function () {
    Http::fake([
        'localhost:11434/api/embeddings' => Http::response([
            'embedding' => array_fill(0, 768, 0.2),
        ]),
    ]);

    $embeddings = app(Embeddings::class);
    $result = $embeddings->embed('test text', [
        'command_model_override' => 'ollama:nomic-embed-text',
    ]);

    expect($result['provider'])->toBe('ollama');
    expect($result['model'])->toBe('nomic-embed-text');
    expect($result['dims'])->toBe(768);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'localhost:11434/api/embeddings') &&
               $request['model'] === 'nomic-embed-text';
    });
});

test('ai services handle model selection failures gracefully', function () {
    // Mock API failure
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $fragment = Fragment::factory()->create([
        'message' => 'Test error handling',
        'vault' => 'default',
        'type' => null,
        'type_id' => null,
        'model_provider' => null,
        'model_name' => null,
    ]);

    $typeInference = app(TypeInferenceService::class);
    $result = $typeInference->inferType($fragment);

    // Should return fallback result with null model metadata
    expect($result['type'])->toBe('log');
    expect($result['confidence'])->toBe(0.0);
    expect($result['model_provider'])->toBeNull();
    expect($result['model_name'])->toBeNull();
});

test('model metadata is included in fragment queries', function () {
    $fragment = Fragment::factory()->create([
        'message' => 'Test fragment with model metadata',
        'vault' => 'default',
        'model_provider' => 'openai',
        'model_name' => 'gpt-4o-mini',
    ]);

    $retrieved = Fragment::find($fragment->id);

    expect($retrieved->model_provider)->toBe('openai');
    expect($retrieved->model_name)->toBe('gpt-4o-mini');
});

test('model metadata is properly cast in fragment model', function () {
    $fragment = Fragment::factory()->create([
        'model_provider' => 'anthropic',
        'model_name' => 'claude-3-5-sonnet-latest',
    ]);

    expect($fragment->model_provider)->toBeString();
    expect($fragment->model_name)->toBeString();
    expect($fragment->model_provider)->toBe('anthropic');
    expect($fragment->model_name)->toBe('claude-3-5-sonnet-latest');
});
