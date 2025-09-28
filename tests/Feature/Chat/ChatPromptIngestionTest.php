<?php

use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('chat prompt creates fragment through RouteFragment pipeline', function () {
    $response = $this->postJson('/api/messages', [
        'content' => 'This is a test chat message',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message_id',
            'conversation_id',
            'user_fragment_id',
        ]);

    $data = $response->json();

    // Verify fragment was created via RouteFragment
    $fragment = Fragment::find($data['user_fragment_id']);
    expect($fragment)->not->toBeNull();
    expect($fragment->message)->toBe('This is a test chat message');
    expect($fragment->source)->toBe('chat-user');
    expect($fragment->type)->toBe('log');
    expect($fragment->vault)->toBe('Default Vault');

    // Verify RouteFragment sets hash fields
    expect($fragment->input_hash)->not->toBeNull();
    expect($fragment->hash_bucket)->not->toBeNull();
    expect($fragment->input_hash)->toHaveLength(64); // SHA256 length

    // Verify chat-specific metadata
    expect($fragment->metadata)->toHaveKey('turn');
    expect($fragment->metadata['turn'])->toBe('prompt');
    expect($fragment->metadata)->toHaveKey('conversation_id');
    expect($fragment->metadata['conversation_id'])->toBe($data['conversation_id']);
    expect($fragment->metadata)->toHaveKey('provider');
    expect($fragment->metadata['provider'])->toBe('ollama');
    expect($fragment->metadata)->toHaveKey('model');
    expect($fragment->metadata['model'])->toBe('llama3:latest');
});

test('chat prompt uses RouteFragment to avoid chaos parsing', function () {
    // Test that chat prompts use RouteFragment instead of chaos parsing pipeline
    $response = $this->postJson('/api/messages', [
        'content' => 'Call the doctor today. Also buy groceries and schedule a meeting with the team.',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Verify the fragment was created via RouteFragment (not chaos parsing)
    $fragment = Fragment::find($data['user_fragment_id']);
    expect($fragment)->not->toBeNull();
    expect($fragment->message)->toBe('Call the doctor today. Also buy groceries and schedule a meeting with the team.');
    expect($fragment->type)->toBe('log');
    expect($fragment->source)->toBe('chat-user');
    expect($fragment->vault)->toBe('Default Vault'); // Should use default vault

    // Verify hash fields are set by RouteFragment
    expect($fragment->input_hash)->not->toBeNull();
    expect($fragment->hash_bucket)->not->toBeNull();
    expect($fragment->input_hash)->toHaveLength(64); // SHA256 hash length

    // Verify no chaos parsing occurred (no child fragments)
    expect($fragment->metadata)->not->toHaveKey('children');
    expect($fragment->metadata)->not->toHaveKey('chaos_parsed_at');
});

test('cache payload structure remains compatible with streaming', function () {
    $response = $this->postJson('/api/messages', [
        'content' => 'Test message for cache verification',
        'provider' => 'custom-provider',
        'model' => 'custom-model',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Verify cache contains expected structure
    $cachedPayload = Cache::get("msg:{$data['message_id']}");

    expect($cachedPayload)->not->toBeNull();
    expect($cachedPayload)->toHaveKeys([
        'messages',
        'provider',
        'model',
        'user_fragment_id',
        'conversation_id',
    ]);

    // Verify messages structure
    expect($cachedPayload['messages'])->toHaveCount(2);
    expect($cachedPayload['messages'][0]['role'])->toBe('system');
    expect($cachedPayload['messages'][1]['role'])->toBe('user');
    expect($cachedPayload['messages'][1]['content'])->toBe('Test message for cache verification');

    // Verify provider/model preserved
    expect($cachedPayload['provider'])->toBe('custom-provider');
    expect($cachedPayload['model'])->toBe('custom-model');

    // Verify fragment ID is valid
    expect($cachedPayload['user_fragment_id'])->toBe($data['user_fragment_id']);
    expect($cachedPayload['conversation_id'])->toBe($data['conversation_id']);
});

test('fragments receive proper source attribution for chat context', function () {
    // Test user fragment
    $response = $this->postJson('/api/messages', [
        'content' => 'User message for source testing',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    $userFragment = Fragment::find($data['user_fragment_id']);
    expect($userFragment->source)->toBe('chat-user');
    expect($userFragment->metadata['turn'])->toBe('prompt');

    // Note: Assistant fragment creation happens in stream() method during actual chat interaction
    // For testing assistant fragment creation, we would need to mock the streaming response
});

test('fragment enrichment metadata includes conversation context', function () {
    $conversationId = 'test-conversation-123';

    $response = $this->postJson('/api/messages', [
        'content' => 'Message with specific conversation ID',
        'conversation_id' => $conversationId,
        'provider' => 'test-provider',
        'model' => 'test-model',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    $fragment = Fragment::find($data['user_fragment_id']);

    // Verify conversation context preserved
    expect($fragment->metadata['conversation_id'])->toBe($conversationId);
    expect($data['conversation_id'])->toBe($conversationId);

    // Verify provider/model context
    expect($fragment->metadata['provider'])->toBe('test-provider');
    expect($fragment->metadata['model'])->toBe('test-model');
});

test('fragment uses RouteFragment with proper enrichment pipeline', function () {
    $response = $this->postJson('/api/messages', [
        'content' => 'This chat message should go through RouteFragment pipeline',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    $fragment = Fragment::find($data['user_fragment_id']);

    // Verify RouteFragment creates fragment with correct type and defaults
    expect($fragment->type)->toBe('log');
    expect($fragment->source)->toBe('chat-user');
    expect($fragment->vault)->toBe('Default Vault');

    // Verify RouteFragment sets proper hash fields for deduplication
    expect($fragment->input_hash)->not->toBeNull();
    expect($fragment->hash_bucket)->not->toBeNull();

    // Verify enrichment pipeline will be triggered asynchronously
    // (tested in other enrichment tests, just verify basic structure here)
    expect($fragment->message)->toBe('This chat message should go through RouteFragment pipeline');
});
