# TELEMETRY-002: Structured Chat Pipeline Logging - Implementation Plan

## Estimated Time: 8 hours

## Phase 1: Chat Message Telemetry (3 hours)

### 1.1 Enhance ChatApiController Logging
**File**: `app/Http/Controllers/ChatApiController.php`

**Message Send Telemetry** (around line 22):
```php
// After message ID and conversation ID generation
Log::info('chat.message.sent', [
    'message_id' => $messageId,
    'conversation_id' => $conversationId,
    'session_id' => $sessionId,
    'fragment_id' => $fragment->id,
    'provider' => $useProvider,
    'model' => $useModel,
    'attachments_count' => count($data['attachments'] ?? []),
    'content_length' => strlen($data['content']), // size only, not content
    'user_id' => 'local-default'
]);
```

**Validation Error Logging** (around line 13):
```php
// In validate() error handling
try {
    $data = $req->validate([...]);
} catch (ValidationException $e) {
    Log::warning('chat.message.validation_failed', [
        'errors' => array_keys($e->errors()),
        'user_id' => 'local-default'
    ]);
    throw $e;
}
```

### 1.2 Create Chat Telemetry Helper Service
**File**: `app/Services/Telemetry/ChatTelemetry.php`

```php
<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;

class ChatTelemetry
{
    public static function messageSent(array $context): void
    {
        Log::info('chat.message.sent', array_filter([
            'message_id' => $context['message_id'] ?? null,
            'conversation_id' => $context['conversation_id'] ?? null,
            'session_id' => $context['session_id'] ?? null,
            'fragment_id' => $context['fragment_id'] ?? null,
            'provider' => $context['provider'] ?? null,
            'model' => $context['model'] ?? null,
            'attachments_count' => $context['attachments_count'] ?? 0,
            'content_length' => $context['content_length'] ?? null,
            'user_id' => 'local-default'
        ]));
    }
    
    public static function streamStarted(array $context): void
    {
        Log::info('chat.stream.started', array_filter([
            'message_id' => $context['message_id'] ?? null,
            'conversation_id' => $context['conversation_id'] ?? null,
            'request_fragment_id' => $context['request_fragment_id'] ?? null,
            'provider' => $context['provider'] ?? null,
            'model' => $context['model'] ?? null,
            'user_id' => 'local-default'
        ]));
    }
    
    public static function streamCompleted(array $context): void
    {
        Log::info('chat.stream.completed', array_filter([
            'message_id' => $context['message_id'] ?? null,
            'request_fragment_id' => $context['request_fragment_id'] ?? null,
            'response_fragment_id' => $context['response_fragment_id'] ?? null,
            'provider' => $context['provider'] ?? null,
            'model' => $context['model'] ?? null,
            'token_usage' => $context['token_usage'] ?? null,
            'duration_ms' => $context['duration_ms'] ?? null,
            'status' => $context['status'] ?? 'success',
            'user_id' => 'local-default'
        ]));
    }
    
    public static function streamFailed(array $context): void
    {
        Log::error('chat.stream.failed', array_filter([
            'message_id' => $context['message_id'] ?? null,
            'provider' => $context['provider'] ?? null,
            'model' => $context['model'] ?? null,
            'error' => $context['error'] ?? null,
            'duration_ms' => $context['duration_ms'] ?? null,
            'user_id' => 'local-default'
        ]));
    }
}
```

## Phase 2: Stream Provider Enhancement (2.5 hours)

### 2.1 Enhance StreamChatProvider Telemetry
**File**: `app/Actions/StreamChatProvider.php`

**Replace existing logging** (around lines 37, 56, 68):
```php
use App\Services\Telemetry\ChatTelemetry;

public function __invoke(string $content, array $context = [])
{
    $startTime = microtime(true);
    $messageId = $context['message_id'] ?? null;
    $requestFragmentId = $context['request_fragment_id'] ?? null;
    
    // Enhanced start logging
    ChatTelemetry::streamStarted([
        'message_id' => $messageId,
        'conversation_id' => $context['conversation_id'] ?? null,
        'request_fragment_id' => $requestFragmentId,
        'provider' => $provider,
        'model' => $model
    ]);
    
    try {
        // ... existing streaming logic ...
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        // Enhanced completion logging
        ChatTelemetry::streamCompleted([
            'message_id' => $messageId,
            'request_fragment_id' => $requestFragmentId,
            'response_fragment_id' => $assistantFragment->id ?? null,
            'provider' => $provider,
            'model' => $model,
            'token_usage' => $this->extractTokenUsage($response),
            'duration_ms' => round($duration, 2),
            'status' => 'success'
        ]);
        
    } catch (\Exception $e) {
        $duration = (microtime(true) - $startTime) * 1000;
        
        ChatTelemetry::streamFailed([
            'message_id' => $messageId,
            'provider' => $provider,
            'model' => $model,
            'error' => $e->getMessage(),
            'duration_ms' => round($duration, 2)
        ]);
        
        throw $e;
    }
}

private function extractTokenUsage($response): ?array
{
    // Extract token usage from AI provider response
    if (isset($response['usage'])) {
        return [
            'prompt_tokens' => $response['usage']['prompt_tokens'] ?? null,
            'completion_tokens' => $response['usage']['completion_tokens'] ?? null,
            'total_tokens' => $response['usage']['total_tokens'] ?? null
        ];
    }
    return null;
}
```

### 2.2 Update ChatApiController Stream Context
**File**: `app/Http/Controllers/ChatApiController.php`

Pass context to StreamChatProvider (around line 65):
```php
// Enhanced context passing
$streamContext = [
    'message_id' => $messageId,
    'conversation_id' => $conversationId,
    'session_id' => $sessionId,
    'request_fragment_id' => $userFragmentId,
    'provider' => $useProvider,
    'model' => $useModel
];

return response()->stream(function () use ($streamChatProvider, $data, $streamContext) {
    $streamChatProvider($data['content'], $streamContext);
});
```

## Phase 3: Session & Fragment Telemetry (1.5 hours)

### 3.1 Enhance Chat Session Actions
**File**: `app/Actions/CacheChatSession.php`

```php
use App\Services\Telemetry\ChatTelemetry;

public function __invoke(ChatSession $session): void
{
    // existing caching logic...
    
    Log::info('chat.session.cached', [
        'session_id' => $session->id,
        'provider' => $session->model_provider,
        'model' => $session->model_name,
        'user_id' => 'local-default'
    ]);
}
```

**File**: `app/Actions/RetrieveChatSession.php`

```php
public function __invoke(int $sessionId): ?ChatSession
{
    $session = // ... existing retrieval logic
    
    if ($session) {
        Log::info('chat.session.retrieved', [
            'session_id' => $sessionId,
            'provider' => $session->model_provider,
            'model' => $session->model_name,
            'user_id' => 'local-default'
        ]);
    } else {
        Log::warning('chat.session.not_found', [
            'session_id' => $sessionId,
            'user_id' => 'local-default'
        ]);
    }
    
    return $session;
}
```

### 3.2 Enhance CreateChatFragment
**File**: `app/Actions/CreateChatFragment.php`

```php
public function __invoke(string $content): Fragment
{
    Log::info('chat.fragment.creation_started', [
        'content_length' => strlen($content),
        'user_id' => 'local-default'
    ]);
    
    $fragment = // ... existing creation logic
    
    Log::info('chat.fragment.created', [
        'fragment_id' => $fragment->id,
        'type' => $fragment->type,
        'content_length' => strlen($content),
        'bypassed_deduplication' => true,
        'user_id' => 'local-default'
    ]);
    
    return $fragment;
}
```

## Phase 4: Testing & Validation (1 hour)

### 4.1 Unit Tests for ChatTelemetry
**File**: `tests/Unit/Services/Telemetry/ChatTelemetryTest.php`

```php
<?php

namespace Tests\Unit\Services\Telemetry;

use App\Services\Telemetry\ChatTelemetry;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ChatTelemetryTest extends TestCase
{
    public function test_message_sent_logging()
    {
        Log::spy();
        
        ChatTelemetry::messageSent([
            'message_id' => 'test-uuid',
            'conversation_id' => 'conv-uuid',
            'fragment_id' => 123,
            'provider' => 'openai',
            'model' => 'gpt-4'
        ]);
        
        Log::shouldHaveReceived('info')
            ->with('chat.message.sent', \Mockery::on(function ($context) {
                return $context['message_id'] === 'test-uuid' &&
                       $context['user_id'] === 'local-default' &&
                       $context['provider'] === 'openai';
            }));
    }
    
    public function test_stream_completed_with_token_usage()
    {
        Log::spy();
        
        ChatTelemetry::streamCompleted([
            'message_id' => 'test-uuid',
            'provider' => 'openai',
            'token_usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 200,
                'total_tokens' => 300
            ],
            'duration_ms' => 1500.5
        ]);
        
        Log::shouldHaveReceived('info')
            ->with('chat.stream.completed', \Mockery::on(function ($context) {
                return isset($context['token_usage']) &&
                       $context['token_usage']['total_tokens'] === 300 &&
                       $context['duration_ms'] === 1500.5;
            }));
    }
}
```

### 4.2 Integration Tests for Chat Pipeline
**File**: `tests/Feature/Telemetry/ChatPipelineTelemetryTest.php`

```php
<?php

namespace Tests\Feature\Telemetry;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ChatPipelineTelemetryTest extends TestCase
{
    public function test_chat_send_includes_structured_telemetry()
    {
        Log::spy();
        
        $response = $this->postJson('/api/chat/send', [
            'content' => 'test message',
            'conversation_id' => 'test-conv-id'
        ]);
        
        $response->assertOk();
        
        // Verify message sent event
        Log::shouldHaveReceived('info')
            ->with('chat.message.sent', \Mockery::on(function ($context) {
                return isset($context['message_id']) &&
                       isset($context['conversation_id']) &&
                       isset($context['fragment_id']) &&
                       $context['user_id'] === 'local-default';
            }));
    }
    
    public function test_validation_errors_are_logged()
    {
        Log::spy();
        
        $response = $this->postJson('/api/chat/send', [
            'content' => '', // Invalid empty content
        ]);
        
        $response->assertStatus(422);
        
        Log::shouldHaveReceived('warning')
            ->with('chat.message.validation_failed', \Mockery::on(function ($context) {
                return in_array('content', $context['errors']);
            }));
    }
}
```

## Implementation Checklist

### Core Implementation
- [ ] Create `ChatTelemetry` helper service
- [ ] Enhance `ChatApiController` with structured logging
- [ ] Update `StreamChatProvider` with comprehensive telemetry
- [ ] Add session action logging in `CacheChatSession`/`RetrieveChatSession`
- [ ] Enhance `CreateChatFragment` with lifecycle telemetry

### Context & Correlation
- [ ] Pass message context to stream provider
- [ ] Ensure correlation IDs propagate through chat pipeline
- [ ] Link fragment IDs to message IDs in logs

### Testing
- [ ] Unit tests for `ChatTelemetry` service
- [ ] Integration tests for chat pipeline telemetry
- [ ] Performance tests for logging overhead
- [ ] Validation error logging tests

### Performance Optimization
- [ ] Measure logging overhead (<2ms requirement)
- [ ] Optimize JSON serialization for telemetry
- [ ] Consider async logging for non-critical events

## Success Metrics

- Chat message events logged with complete metadata
- Token usage and timing captured for all AI interactions
- Conversation and session correlation working
- Validation errors properly logged and debuggable
- <2ms total logging overhead per chat interaction
- Zero raw content stored in logs (privacy compliance)
- Integration with correlation middleware from TELEMETRY-001