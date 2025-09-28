<?php

use App\Actions\CacheChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('CacheChatSession stores all required data', function () {
    $action = new CacheChatSession();
    
    $messageId = 'test-message-123';
    $messages = [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Hello world'],
    ];
    $provider = 'ollama';
    $model = 'llama3:latest';
    $userFragmentId = 'fragment-456';
    $conversationId = 'conversation-789';
    $sessionId = 'session-101';

    // Execute the action
    $action(
        $messageId,
        $messages,
        $provider,
        $model,
        $userFragmentId,
        $conversationId,
        $sessionId
    );

    // Verify cached data
    $cachedData = Cache::get("msg:{$messageId}");
    
    expect($cachedData)->not->toBeNull();
    expect($cachedData)->toHaveKeys([
        'messages',
        'provider',
        'model',
        'user_fragment_id',
        'conversation_id',
        'session_id',
    ]);

    expect($cachedData['messages'])->toBe($messages);
    expect($cachedData['provider'])->toBe($provider);
    expect($cachedData['model'])->toBe($model);
    expect($cachedData['user_fragment_id'])->toBe($userFragmentId);
    expect($cachedData['conversation_id'])->toBe($conversationId);
    expect($cachedData['session_id'])->toBe($sessionId);
});

test('CacheChatSession generates session_id when not provided', function () {
    $action = new CacheChatSession();
    
    $messageId = 'test-message-auto-session';
    $messages = [['role' => 'user', 'content' => 'Test message']];
    $provider = 'ollama';
    $model = 'llama3:latest';
    $userFragmentId = 'fragment-auto';
    $conversationId = 'conversation-auto';

    // Execute without session_id
    $action(
        $messageId,
        $messages,
        $provider,
        $model,
        $userFragmentId,
        $conversationId
        // No session_id parameter
    );

    // Verify session_id was generated
    $cachedData = Cache::get("msg:{$messageId}");
    
    expect($cachedData)->toHaveKey('session_id');
    expect($cachedData['session_id'])->toBeString();
    expect($cachedData['session_id'])->toHaveLength(36); // UUID v4 length
});

test('CacheChatSession cache expiration is set correctly', function () {
    $action = new CacheChatSession();
    
    $messageId = 'test-message-expiration';
    
    // Execute the action
    $action(
        $messageId,
        [['role' => 'user', 'content' => 'Test']],
        'ollama',
        'llama3:latest',
        'fragment-id',
        'conversation-id'
    );

    // Verify data is cached
    expect(Cache::has("msg:{$messageId}"))->toBeTrue();
    
    // Fast-forward time by 9 minutes (should still be cached)
    $this->travel(9)->minutes();
    expect(Cache::has("msg:{$messageId}"))->toBeTrue();
    
    // Fast-forward to 11 minutes (should be expired)
    $this->travel(11)->minutes();
    expect(Cache::has("msg:{$messageId}"))->toBeFalse();
});

test('CacheChatSession works with empty messages array', function () {
    $action = new CacheChatSession();
    
    $messageId = 'test-message-empty';
    $emptyMessages = [];
    
    // Should not throw an error with empty messages
    $action(
        $messageId,
        $emptyMessages,
        'ollama',
        'llama3:latest',
        'fragment-id',
        'conversation-id'
    );

    $cachedData = Cache::get("msg:{$messageId}");
    expect($cachedData['messages'])->toBe([]);
});

test('CacheChatSession handles special characters in IDs', function () {
    $action = new CacheChatSession();
    
    $messageId = 'test-message-special-chars';
    $conversationId = 'conversation-with-dashes-and_underscores';
    $userFragmentId = 'fragment-123-abc';
    
    $action(
        $messageId,
        [['role' => 'user', 'content' => 'Test special chars']],
        'ollama',
        'llama3:latest',
        $userFragmentId,
        $conversationId
    );

    $cachedData = Cache::get("msg:{$messageId}");
    expect($cachedData['conversation_id'])->toBe($conversationId);
    expect($cachedData['user_fragment_id'])->toBe($userFragmentId);
});