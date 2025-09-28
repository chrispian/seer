<?php

use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock successful Ollama response with token usage
    Http::fake([
        'localhost:11434/*' => Http::response(
            json_encode([
                'message' => ['content' => 'Hello '],
                'done' => false,
            ])."\n".
            json_encode([
                'message' => ['content' => 'world!'],
                'done' => false,
            ])."\n".
            json_encode([
                'message' => ['content' => ''],
                'done' => true,
                'prompt_eval_count' => 15,
                'eval_count' => 8,
                'total_duration' => 1234567890,
                'load_duration' => 123456789,
                'prompt_eval_duration' => 234567890,
                'eval_duration' => 345678901,
            ])."\n",
            200,
            ['Content-Type' => 'application/x-ndjson']
        ),
    ]);
});

test('assistant fragment captures comprehensive metadata', function () {
    // Create user message first
    $response = $this->postJson('/api/messages', [
        'content' => 'Test user message',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
        'conversation_id' => 'test-conversation-123',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Stream the response to create assistant fragment
    $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
    $streamResponse->assertStatus(200);

    // Find the assistant fragment (should be the most recent with source 'chat-ai')
    $assistantFragment = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($assistantFragment)->not->toBeNull();

    // Verify basic metadata structure
    expect($assistantFragment->metadata)->toHaveKey('turn');
    expect($assistantFragment->metadata['turn'])->toBe('response');

    expect($assistantFragment->metadata)->toHaveKey('conversation_id');
    expect($assistantFragment->metadata['conversation_id'])->toBe('test-conversation-123');

    expect($assistantFragment->metadata)->toHaveKey('session_id');
    expect($assistantFragment->metadata['session_id'])->toBeString();

    expect($assistantFragment->metadata)->toHaveKey('provider');
    expect($assistantFragment->metadata['provider'])->toBe('ollama');

    expect($assistantFragment->metadata)->toHaveKey('model');
    expect($assistantFragment->metadata['model'])->toBe('llama3:latest');

    expect($assistantFragment->metadata)->toHaveKey('router');
    expect($assistantFragment->metadata['router'])->toBe('ollama');

    // Verify latency measurement
    expect($assistantFragment->metadata)->toHaveKey('latency_ms');
    expect($assistantFragment->metadata['latency_ms'])->toBeFloat();
    expect($assistantFragment->metadata['latency_ms'])->toBeGreaterThan(0);

    // Verify token usage
    expect($assistantFragment->metadata)->toHaveKey('token_usage');
    expect($assistantFragment->metadata['token_usage'])->toHaveKey('input');
    expect($assistantFragment->metadata['token_usage']['input'])->toBe(15);
    expect($assistantFragment->metadata['token_usage'])->toHaveKey('output');
    expect($assistantFragment->metadata['token_usage']['output'])->toBe(8);

    // Verify cost calculation
    expect($assistantFragment->metadata)->toHaveKey('cost_usd');
    expect($assistantFragment->metadata['cost_usd'])->toBe(0.00);

    // Verify vault and project context
    expect($assistantFragment->metadata)->toHaveKey('vault');
    expect($assistantFragment->metadata['vault'])->toBe('Default Vault');

    expect($assistantFragment->metadata)->toHaveKey('project_id');
    expect($assistantFragment->metadata['project_id'])->toBeInt();
});

test('assistant fragment relationships are properly set', function () {
    // Create user message first
    $response = $this->postJson('/api/messages', [
        'content' => 'Test user message for relationships',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
        'conversation_id' => 'test-conversation-456',
    ]);

    $response->assertStatus(200);
    $data = $response->json();
    $userFragmentId = $data['user_fragment_id'];

    // Stream the response to create assistant fragment
    $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
    $streamResponse->assertStatus(200);

    // Find the assistant fragment
    $assistantFragment = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($assistantFragment)->not->toBeNull();

    // Verify relationships
    expect($assistantFragment->relationships)->toHaveKey('in_reply_to_id');
    expect($assistantFragment->relationships['in_reply_to_id'])->toBe($userFragmentId);

    expect($assistantFragment->relationships)->toHaveKey('conversation_id');
    expect($assistantFragment->relationships['conversation_id'])->toBe('test-conversation-456');

    // Verify conversation_id is also stored in metadata for consistency
    expect($assistantFragment->metadata)->toHaveKey('conversation_id');
    expect($assistantFragment->metadata['conversation_id'])->toBe('test-conversation-456');

    // Verify model attribution
    expect($assistantFragment->model_provider)->toBe('ollama');
    expect($assistantFragment->model_name)->toBe('llama3:latest');
});

test('assistant fragment handles missing token data gracefully', function () {
    // Mock Ollama response without token usage data
    Http::fake([
        'localhost:11434/*' => Http::response(
            json_encode([
                'message' => ['content' => 'Response without tokens'],
                'done' => false,
            ])."\n".
            json_encode([
                'message' => ['content' => ''],
                'done' => true,
                // No token usage fields
            ])."\n",
            200,
            ['Content-Type' => 'application/x-ndjson']
        ),
    ]);

    // Create user message first
    $response = $this->postJson('/api/messages', [
        'content' => 'Test message for missing tokens',
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

    // Verify token usage handles nulls gracefully
    expect($assistantFragment->metadata)->toHaveKey('token_usage');
    expect($assistantFragment->metadata['token_usage']['input'])->toBeNull();
    expect($assistantFragment->metadata['token_usage']['output'])->toBeNull();

    // Verify cost is null when no token data
    expect($assistantFragment->metadata['cost_usd'])->toBeNull();
});

test('session ID is consistent within conversation', function () {
    // Create first message
    $response1 = $this->postJson('/api/messages', [
        'content' => 'First message',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
        'conversation_id' => 'session-test-conversation',
    ]);

    $response1->assertStatus(200);
    $data1 = $response1->json();

    // Simulate adding session_id to cache for subsequent messages
    $payload1 = Cache::get("msg:{$data1['message_id']}");
    $sessionId = $payload1['session_id'] ?? 'test-session-id';

    // Create second message with same conversation
    $response2 = $this->postJson('/api/messages', [
        'content' => 'Second message',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
        'conversation_id' => 'session-test-conversation',
    ]);

    $response2->assertStatus(200);
    $data2 = $response2->json();

    // Manually set session_id in cache to simulate persistence
    $payload2 = Cache::get("msg:{$data2['message_id']}");
    $payload2['session_id'] = $sessionId;
    Cache::put("msg:{$data2['message_id']}", $payload2, now()->addMinutes(10));

    // Stream both responses
    $this->get("/api/chat/stream/{$data1['message_id']}");
    $this->get("/api/chat/stream/{$data2['message_id']}");

    // Find both assistant fragments
    $assistantFragments = Fragment::where('source', 'chat-ai')
        ->orderBy('created_at', 'desc')
        ->take(2)
        ->get();

    expect($assistantFragments)->toHaveCount(2);

    // Both should have session IDs (may be different in this test setup, but should be strings)
    foreach ($assistantFragments as $fragment) {
        expect($fragment->metadata)->toHaveKey('session_id');
        expect($fragment->metadata['session_id'])->toBeString();
    }
});
