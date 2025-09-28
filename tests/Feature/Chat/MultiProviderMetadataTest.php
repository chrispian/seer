<?php

use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('ollama provider metadata is consistent and complete', function () {
    Http::fake([
        'localhost:11434/*' => Http::response(
            'data: '.json_encode([
                'message' => ['content' => 'Ollama response'],
                'done' => false,
            ])."\n".
            'data: '.json_encode([
                'message' => ['content' => ''],
                'done' => true,
                'prompt_eval_count' => 20,
                'eval_count' => 15,
                'total_duration' => 2000000000,
                'load_duration' => 100000000,
                'prompt_eval_duration' => 500000000,
                'eval_duration' => 800000000,
            ])."\n",
            200,
            ['Content-Type' => 'text/plain']
        ),
    ]);

    // Create message with Ollama provider
    $response = $this->postJson('/api/messages', [
        'content' => 'Test Ollama provider metadata',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
        'conversation_id' => 'ollama-test-conversation',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Stream the response
    $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
    $streamResponse->assertStatus(200);

    // Find the assistant fragment
    $assistantFragment = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($assistantFragment)->not->toBeNull();

    // Verify Ollama-specific metadata structure
    expect($assistantFragment->metadata['provider'])->toBe('ollama');
    expect($assistantFragment->metadata['router'])->toBe('ollama');
    expect($assistantFragment->model_provider)->toBe('ollama');
    expect($assistantFragment->model_name)->toBe('llama3:latest');

    // Verify token usage is captured from Ollama response
    expect($assistantFragment->metadata['token_usage']['input'])->toBe(20);
    expect($assistantFragment->metadata['token_usage']['output'])->toBe(15);

    // Verify cost calculation for free provider
    expect($assistantFragment->metadata['cost_usd'])->toBe(0.00);

    // Verify latency measurement exists
    expect($assistantFragment->metadata['latency_ms'])->toBeFloat();
    expect($assistantFragment->metadata['latency_ms'])->toBeGreaterThan(0);
});

test('provider metadata handles missing token data gracefully', function () {
    // Mock response without token usage fields (simulating different provider behavior)
    Http::fake([
        'localhost:11434/*' => Http::response(
            'data: '.json_encode([
                'message' => ['content' => 'Response without token data'],
                'done' => false,
            ])."\n".
            'data: '.json_encode([
                'message' => ['content' => ''],
                'done' => true,
                // No token fields - simulating provider that doesn't provide this data
            ])."\n",
            200,
            ['Content-Type' => 'text/plain']
        ),
    ]);

    // Create message
    $response = $this->postJson('/api/messages', [
        'content' => 'Test missing token data handling',
        'provider' => 'ollama',
        'model' => 'custom-model',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Stream the response
    $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
    $streamResponse->assertStatus(200);

    // Find the assistant fragment
    $assistantFragment = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($assistantFragment)->not->toBeNull();

    // Verify token usage structure exists but values are null
    expect($assistantFragment->metadata)->toHaveKey('token_usage');
    expect($assistantFragment->metadata['token_usage']['input'])->toBeNull();
    expect($assistantFragment->metadata['token_usage']['output'])->toBeNull();

    // Verify cost is null when no token data available
    expect($assistantFragment->metadata['cost_usd'])->toBeNull();

    // Verify other metadata is still present and valid
    expect($assistantFragment->metadata['provider'])->toBe('ollama');
    expect($assistantFragment->metadata['model'])->toBe('custom-model');
    expect($assistantFragment->metadata['latency_ms'])->toBeFloat();
});

test('metadata structure is consistent across different models', function () {
    $models = ['llama3:latest', 'codellama:7b', 'mistral:7b'];

    foreach ($models as $model) {
        Http::fake([
            'localhost:11434/*' => Http::response(
                'data: '.json_encode([
                    'message' => ['content' => "Response from {$model}"],
                    'done' => false,
                ])."\n".
                'data: '.json_encode([
                    'message' => ['content' => ''],
                    'done' => true,
                    'prompt_eval_count' => 10,
                    'eval_count' => 5,
                ])."\n",
                200,
                ['Content-Type' => 'text/plain']
            ),
        ]);

        // Create message with different model
        $response = $this->postJson('/api/messages', [
            'content' => "Test with {$model}",
            'provider' => 'ollama',
            'model' => $model,
            'conversation_id' => "test-{$model}",
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Stream the response
        $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
        $streamResponse->assertStatus(200);

        // Find the assistant fragment for this model
        $assistantFragment = Fragment::where('source', 'chat-ai')
            ->where('model_name', $model)
            ->orderBy('created_at', 'desc')
            ->first();

        expect($assistantFragment)->not->toBeNull();

        // Verify consistent metadata structure across models
        $requiredFields = [
            'turn', 'conversation_id', 'session_id', 'provider', 'model',
            'router', 'latency_ms', 'token_usage', 'cost_usd', 'vault', 'project_id',
        ];

        foreach ($requiredFields as $field) {
            expect($assistantFragment->metadata)->toHaveKey($field);
        }

        // Verify model-specific values
        expect($assistantFragment->metadata['model'])->toBe($model);
        expect($assistantFragment->model_name)->toBe($model);
        expect($assistantFragment->metadata['conversation_id'])->toBe("test-{$model}");
    }
});

test('vault and project context is consistently applied', function () {
    Http::fake([
        'localhost:11434/*' => Http::response(
            'data: '.json_encode([
                'message' => ['content' => 'Testing vault context'],
                'done' => false,
            ])."\n".
            'data: '.json_encode([
                'message' => ['content' => ''],
                'done' => true,
                'prompt_eval_count' => 8,
                'eval_count' => 4,
            ])."\n",
            200,
            ['Content-Type' => 'text/plain']
        ),
    ]);

    // Create message
    $response = $this->postJson('/api/messages', [
        'content' => 'Test vault and project context',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Stream the response
    $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
    $streamResponse->assertStatus(200);

    // Find the assistant fragment
    $assistantFragment = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($assistantFragment)->not->toBeNull();

    // Verify vault context is captured
    expect($assistantFragment->metadata)->toHaveKey('vault');
    expect($assistantFragment->metadata['vault'])->toBeString();
    expect($assistantFragment->metadata['vault'])->not->toBeEmpty();

    // Verify project context is captured
    expect($assistantFragment->metadata)->toHaveKey('project_id');
    expect($assistantFragment->metadata['project_id'])->toBeInt();

    // Verify the fragment also has these fields directly
    expect($assistantFragment->vault)->toBeString();
    expect($assistantFragment->project_id)->toBeInt();

    // Verify consistency between direct fields and metadata
    expect($assistantFragment->vault)->toBe($assistantFragment->metadata['vault']);
    expect($assistantFragment->project_id)->toBe($assistantFragment->metadata['project_id']);
});

test('session tracking is properly maintained', function () {
    Http::fake([
        'localhost:11434/*' => Http::response(
            'data: '.json_encode([
                'message' => ['content' => 'Session tracking test'],
                'done' => false,
            ])."\n".
            'data: '.json_encode([
                'message' => ['content' => ''],
                'done' => true,
                'prompt_eval_count' => 6,
                'eval_count' => 3,
            ])."\n",
            200,
            ['Content-Type' => 'text/plain']
        ),
    ]);

    // Create message
    $response = $this->postJson('/api/messages', [
        'content' => 'Test session tracking',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
        'conversation_id' => 'session-tracking-test',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Stream the response
    $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
    $streamResponse->assertStatus(200);

    // Find the assistant fragment
    $assistantFragment = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($assistantFragment)->not->toBeNull();

    // Verify session tracking fields
    expect($assistantFragment->metadata)->toHaveKey('session_id');
    expect($assistantFragment->metadata['session_id'])->toBeString();
    expect($assistantFragment->metadata['session_id'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'); // UUID format

    expect($assistantFragment->metadata)->toHaveKey('conversation_id');
    expect($assistantFragment->metadata['conversation_id'])->toBe('session-tracking-test');

    // Verify relationship tracking
    expect($assistantFragment->relationships)->toHaveKey('conversation_id');
    expect($assistantFragment->relationships['conversation_id'])->toBe('session-tracking-test');
});

test('router field correctly identifies provider routing', function () {
    Http::fake([
        'localhost:11434/*' => Http::response(
            'data: '.json_encode([
                'message' => ['content' => 'Router identification test'],
                'done' => false,
            ])."\n".
            'data: '.json_encode([
                'message' => ['content' => ''],
                'done' => true,
                'prompt_eval_count' => 9,
                'eval_count' => 5,
            ])."\n",
            200,
            ['Content-Type' => 'text/plain']
        ),
    ]);

    // Test with explicit provider
    $response = $this->postJson('/api/messages', [
        'content' => 'Test router field',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Stream the response
    $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
    $streamResponse->assertStatus(200);

    // Find the assistant fragment
    $assistantFragment = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($assistantFragment)->not->toBeNull();

    // Verify router field matches provider
    expect($assistantFragment->metadata['router'])->toBe('ollama');
    expect($assistantFragment->metadata['provider'])->toBe('ollama');

    // For now, router and provider should be the same
    // Future implementation might have router logic (e.g., 'openrouter' routing to 'anthropic')
    expect($assistantFragment->metadata['router'])->toBe($assistantFragment->metadata['provider']);
});
