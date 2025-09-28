<?php

use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock successful Ollama response
    Http::fake([
        'localhost:11434/*' => Http::response(
            json_encode([
                'message' => ['content' => 'Assistant response'],
                'done' => false,
            ])."\n".
            json_encode([
                'message' => ['content' => ''],
                'done' => true,
                'prompt_eval_count' => 10,
                'eval_count' => 5,
            ])."\n",
            200,
            ['Content-Type' => 'application/x-ndjson']
        ),
    ]);
});

test('conversation_id is stored consistently in user fragment metadata', function () {
    $conversationId = 'test-conversation-consistency';

    // Create user message
    $response = $this->postJson('/api/messages', [
        'content' => 'User message for consistency test',
        'conversation_id' => $conversationId,
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Find user fragment
    $userFragment = Fragment::find($data['user_fragment_id']);
    expect($userFragment)->not->toBeNull();

    // Verify user fragment stores conversation_id in metadata
    expect($userFragment->metadata)->toHaveKey('conversation_id');
    expect($userFragment->metadata['conversation_id'])->toBe($conversationId);

    // Verify response includes conversation_id
    expect($data['conversation_id'])->toBe($conversationId);
});

test('sequential user messages in same conversation share identical conversation_id', function () {
    $conversationId = 'test-sequential-conversation';

    // First message
    $response1 = $this->postJson('/api/messages', [
        'content' => 'First message in conversation',
        'conversation_id' => $conversationId,
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response1->assertStatus(200);
    $data1 = $response1->json();

    // Second message with same conversation_id
    $response2 = $this->postJson('/api/messages', [
        'content' => 'Second message in conversation',
        'conversation_id' => $conversationId,
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response2->assertStatus(200);
    $data2 = $response2->json();

    // Find all user fragments for this conversation
    $userFragments = Fragment::where('source', 'chat-user')
        ->whereJsonContains('metadata->conversation_id', $conversationId)
        ->orderBy('created_at')
        ->get();

    expect($userFragments)->toHaveCount(2);

    // Verify all user fragments have the same conversation_id
    foreach ($userFragments as $fragment) {
        expect($fragment->metadata)->toHaveKey('conversation_id');
        expect($fragment->metadata['conversation_id'])->toBe($conversationId);
    }

    // Verify conversation IDs in responses
    expect($data1['conversation_id'])->toBe($conversationId);
    expect($data2['conversation_id'])->toBe($conversationId);
});

test('uuid generation for conversation_id when none provided', function () {
    // Create message without conversation_id
    $response = $this->postJson('/api/messages', [
        'content' => 'Message without conversation ID',
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Verify a UUID was generated
    expect($data['conversation_id'])->toBeString();
    expect($data['conversation_id'])->toHaveLength(36); // UUID v4 length

    // Find user fragment
    $userFragment = Fragment::find($data['user_fragment_id']);

    // Verify user fragment has the generated conversation_id
    expect($userFragment->metadata['conversation_id'])->toBe($data['conversation_id']);
});

test('conversation_id validation and persistence in cache', function () {
    $conversationId = 'test-cache-persistence';

    // Create user message
    $response = $this->postJson('/api/messages', [
        'content' => 'Test cache persistence',
        'conversation_id' => $conversationId,
        'provider' => 'ollama',
        'model' => 'llama3:latest',
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    // Verify cache contains conversation_id
    $cachedPayload = Cache::get("msg:{$data['message_id']}");
    expect($cachedPayload)->not->toBeNull();
    expect($cachedPayload)->toHaveKey('conversation_id');
    expect($cachedPayload['conversation_id'])->toBe($conversationId);

    // Verify other expected cache fields
    expect($cachedPayload)->toHaveKeys([
        'messages',
        'provider',
        'model',
        'user_fragment_id',
        'session_id',
    ]);
});

test('user conversation history reconstruction using conversation_id', function () {
    $conversationId = 'test-history-reconstruction';

    // Create multiple user messages in conversation
    $turns = [
        'What is the weather like?',
        'Tell me about machine learning',
        'How does neural network training work?',
    ];

    $fragmentIds = [];
    foreach ($turns as $content) {
        $response = $this->postJson('/api/messages', [
            'content' => $content,
            'conversation_id' => $conversationId,
            'provider' => 'ollama',
            'model' => 'llama3:latest',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $fragmentIds[] = $data['user_fragment_id'];
    }

    // Retrieve user conversation history using conversation_id
    $userFragments = Fragment::where('source', 'chat-user')
        ->whereJsonContains('metadata->conversation_id', $conversationId)
        ->orderBy('created_at')
        ->get();

    expect($userFragments)->toHaveCount(3);

    // Verify all have same conversation_id
    $conversationIds = $userFragments->pluck('metadata.conversation_id')->unique();
    expect($conversationIds)->toHaveCount(1);
    expect($conversationIds->first())->toBe($conversationId);

    // Verify content matches input
    $contents = $userFragments->pluck('message')->toArray();
    expect($contents)->toBe($turns);
});

test('graceful handling of missing conversation_id in legacy data', function () {
    // Create a fragment without conversation_id (simulating legacy data)
    $legacyFragment = Fragment::create([
        'message' => 'Legacy fragment without conversation_id',
        'source' => 'chat-user',
        'type' => 'log',
        'vault' => 'Default Vault',
        'metadata' => [
            'turn' => 'prompt',
            'provider' => 'ollama',
            'model' => 'llama3:latest',
            // No conversation_id
        ],
    ]);

    expect($legacyFragment)->not->toBeNull();
    expect($legacyFragment->metadata)->not->toHaveKey('conversation_id');

    // System should handle this gracefully
    expect($legacyFragment->metadata['turn'])->toBe('prompt');
    expect($legacyFragment->source)->toBe('chat-user');
});
