# TELEMETRY-002: Structured Chat Pipeline Logging - Context

## Current State Analysis

### Chat Processing Pipeline
**Entry Point**: `app/Http/Controllers/ChatApiController.php:11-160`
- Validates chat message requests
- Creates user fragments via `CreateChatFragment`
- Handles conversation ID and session ID correlation
- Streams AI responses via `StreamChatProvider`
- Currently emits **no structured logs**

**Key Issues Identified**:
- No logging of message UUIDs, fragment IDs, or attachment stats
- Validation failures disappear silently
- No correlation between user input and AI response
- Missing token usage and latency metrics

### Streaming Pipeline
**Core Service**: `app/Actions/StreamChatProvider.php:10-76`
- Logs basic start/end messages with emoji strings
- Missing contextual keys: no `conversation_id`, `session_id`, or fragment relationship IDs
- No provider/model selection logging
- No token usage or cost tracking

**Current Logging Pattern**:
```php
Log::info('Starting chat stream', ['provider' => $provider]);
Log::info('Chat stream completed', ['duration' => $duration]);
Log::error('Chat streaming failed', ['error' => $e->getMessage()]);
```

### Chat Session Management
**Session Actions**:
- `app/Actions/CacheChatSession.php:10-34` - No logs
- `app/Actions/RetrieveChatSession.php:12-42` - No logs

**Fragment Creation**: `app/Actions/CreateChatFragment.php`
- Basic debug logging only
- Missing fragment lifecycle telemetry

## Target Log Schema Design

### Chat Message Events
```json
{
  "event": "chat.message.sent",
  "correlation_id": "uuid",
  "message_id": "uuid", 
  "conversation_id": "uuid",
  "session_id": 123,
  "fragment_id": 456,
  "provider": "openai",
  "model": "gpt-4",
  "attachments_count": 2,
  "user_id": "local-default",
  "timestamp": "2025-01-04T10:00:00Z"
}
```

### Streaming Events
```json
{
  "event": "chat.stream.completed",
  "correlation_id": "uuid",
  "message_id": "uuid",
  "request_fragment_id": 456,
  "response_fragment_id": 789,
  "provider": "openai",
  "model": "gpt-4",
  "token_usage": {
    "prompt_tokens": 150,
    "completion_tokens": 300,
    "total_tokens": 450
  },
  "duration_ms": 2150,
  "status": "success",
  "user_id": "local-default"
}
```

### Session Events
```json
{
  "event": "chat.session.created",
  "correlation_id": "uuid",
  "session_id": 123,
  "provider": "openai",
  "model": "gpt-4",
  "user_id": "local-default"
}
```

## Privacy Requirements

### What to Log (Metadata Keys Only)
- Message/fragment/conversation UUIDs
- Provider and model selection
- Token counts and timing metrics
- Session and user correlation
- Status and error codes

### What NOT to Log (Content Protection)
- Raw message content
- AI response text
- Attachment file contents
- User personal information
- System prompts or instructions

## Performance Constraints

### Logging Overhead Targets
- **Chat Message Send**: <1ms overhead
- **Streaming Events**: <0.5ms per event
- **Session Operations**: <0.2ms overhead
- **Total Pipeline**: <2ms additional latency

### Implementation Strategy
- Async logging where possible
- Minimal JSON serialization
- Pre-computed metadata extraction
- Batch session events when appropriate

## Integration Requirements

### Dependencies on TELEMETRY-001
- Correlation IDs from middleware
- Request context propagation
- Log context inheritance

### Downstream Impact
- Fragment processing jobs need message correlation
- Command execution needs conversation context
- Tool invocations need chat session linkage

### Existing Infrastructure
- Laravel Log facade and Monolog
- Current token usage tracking in AI providers
- Fragment metadata system
- Chat session caching system