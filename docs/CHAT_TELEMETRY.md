# Chat Pipeline Telemetry Implementation

## Overview

This document describes the structured, privacy-respecting telemetry system implemented for the chat pipeline. The system builds on the existing correlation context foundation to provide comprehensive tracking and debugging capabilities without compromising user privacy.

## Architecture

### Core Components

1. **ChatTelemetry Service** (`app/Services/Telemetry/ChatTelemetry.php`)
   - Central service for all chat-related logging
   - Privacy-first design with no message content logging
   - Structured event-based logging with correlation context

2. **CorrelationContext Service** (`app/Services/Telemetry/CorrelationContext.php`)
   - Thread-local correlation ID management
   - Request-scoped context storage
   - Automatic propagation to queued jobs

3. **ChatTelemetryMiddleware** (`app/Http/Middleware/ChatTelemetryMiddleware.php`)
   - Adds chat-specific context to requests
   - Privacy-safe client identification (hashed IP, user agent)
   - Automatic context setup for chat endpoints

4. **Dedicated Log Channel** (`config/logging.php`)
   - Separate `chat-telemetry` log channel
   - Daily rotation with configurable retention
   - Structured JSON output for analysis

### Privacy Design Principles

- **No Content Logging**: User messages and AI responses are never logged in full
- **Content Length Only**: Track message sizes for performance analysis
- **Hash-Based Identification**: IP addresses and user agents are hashed
- **Correlation Over Identification**: Focus on request flow rather than user identity
- **Configurable Retention**: Logs rotate with configurable retention periods

## Instrumentation Points

### 1. Message Receipt and Validation
```php
ChatTelemetry::logMessageReceived([
    'content_length' => strlen($content),
    'has_conversation_id' => isset($conversationId),
    'attachment_count' => count($attachments),
    'provider_requested' => $provider,
]);
```

### 2. Provider Selection
```php
ChatTelemetry::logProviderSelection([
    'provider' => 'openai',
    'model' => 'gpt-4',
    'source' => 'request', // 'request', 'session', 'fallback'
]);
```

### 3. Streaming Events
```php
// Stream start
ChatTelemetry::logStreamingStarted($messageId, $sessionData);

// Progress tracking (every 10 deltas)
ChatTelemetry::logStreamingProgress($messageId, [
    'delta_count' => 50,
    'total_length' => 1200,
    'elapsed_ms' => 2500,
    'tokens_per_second' => 20.0,
]);

// Completion
ChatTelemetry::logStreamingCompleted($messageId, [
    'final_message_length' => 1500,
    'duration_ms' => 3000,
    'token_usage' => ['prompt_tokens' => 100, 'completion_tokens' => 75],
]);

// Errors
ChatTelemetry::logStreamingError($messageId, $exception, $context);
```

### 4. Fragment Processing
```php
// User fragment creation
ChatTelemetry::logUserFragmentCreated($fragmentId, $metadata);

// Assistant fragment processing
ChatTelemetry::logAssistantFragmentCreated($fragmentId, $metadata);

// Enrichment pipeline
ChatTelemetry::logEnrichmentPipelineStarted($fragmentId, $steps);
ChatTelemetry::logEnrichmentPipelineCompleted($fragmentId, $durationMs);
```

### 5. Transaction Summary
```php
ChatTelemetry::logChatTransactionSummary([
    'message_id' => $messageId,
    'conversation_id' => $conversationId,
    'total_duration_ms' => 2500.0,
    'user_fragment_id' => 'user-123',
    'assistant_fragment_id' => 'assistant-456',
    'provider' => 'openai',
    'model' => 'gpt-4',
    'input_length' => 100,
    'output_length' => 250,
    'token_usage' => [...],
    'success' => true,
]);
```

## Log Structure

All telemetry logs follow a consistent structure:

```json
{
  "event": "chat.streaming.completed",
  "data": {
    "message_id": "uuid-123",
    "final_message_length": 1500,
    "duration_ms": 3000.5,
    "token_usage": {
      "prompt_tokens": 100,
      "completion_tokens": 75
    }
  },
  "meta": {
    "timestamp": "2024-10-04T15:30:00.000Z",
    "event_id": "uuid-456",
    "service": "chat-pipeline",
    "version": "1.0"
  },
  "correlation": {
    "correlation_id": "uuid-789",
    "timestamp": "2024-10-04T15:29:45.000Z",
    "context": {
      "message_id": "uuid-123",
      "operation": "chat_stream",
      "conversation_id": "uuid-conv",
      "provider": "openai",
      "model": "gpt-4",
      "service": "chat",
      "endpoint": "chat/stream/uuid-123",
      "method": "GET",
      "user_id": 42,
      "ip_hash": "sha256-hash",
      "user_agent_hash": "sha256-hash"
    }
  }
}
```

## Performance Metrics Tracked

### Request-Level Metrics
- Total request duration
- Validation time
- Fragment creation time
- Provider selection time
- Cache operation time

### Streaming Metrics
- Time to first token
- Streaming duration
- Tokens per second
- Delta count and frequency
- Total response length

### Pipeline Metrics
- Enrichment pipeline duration
- Individual step performance
- Error rates by step
- Fragment processing time

## Usage for Debugging

### Common Queries

1. **Find slow chat requests**:
   ```bash
   grep "chat.transaction.summary" logs/chat-telemetry.log | \
   jq 'select(.data.total_duration_ms > 5000)'
   ```

2. **Track conversation flow**:
   ```bash
   grep "conversation_id.*uuid-conv" logs/chat-telemetry.log | \
   jq '.event, .data'
   ```

3. **Monitor streaming errors**:
   ```bash
   grep "chat.streaming.error" logs/chat-telemetry.log | \
   jq '.data.error_type, .data.provider'
   ```

4. **Analyze provider performance**:
   ```bash
   grep "chat.streaming.completed" logs/chat-telemetry.log | \
   jq 'select(.correlation.context.provider == "openai") | .data.duration_ms'
   ```

### Correlation Tracking

Every event includes a correlation ID that allows tracking:
- Complete request flow from receipt to completion
- Related fragment processing
- Async enrichment pipeline execution
- Error propagation across components

## Configuration

### Log Channel Configuration
```php
// config/logging.php
'chat-telemetry' => [
    'driver' => 'daily',
    'path' => storage_path('logs/chat-telemetry.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_DAILY_DAYS', 14),
    'replace_placeholders' => true,
],
```

### Environment Variables
- `LOG_LEVEL`: Control telemetry verbosity
- `LOG_DAILY_DAYS`: Log retention period
- Configure correlation middleware behavior

## Testing

The telemetry system includes comprehensive tests:

```bash
# Run all telemetry tests
./vendor/bin/pest tests/Feature/Telemetry/

# Run chat telemetry specific tests
./vendor/bin/pest tests/Feature/Telemetry/ChatTelemetryTest.php

# Run correlation context tests
./vendor/bin/pest tests/Feature/Telemetry/CorrelationMiddlewareTest.php
```

## Privacy Compliance

### Data Minimization
- Content length tracking instead of content logging
- Hashed identifiers for client information
- No PII in telemetry data

### Data Retention
- Configurable log retention periods
- Automatic cleanup of old telemetry data
- No long-term storage of sensitive context

### Access Control
- Separate log channel for access management
- Structured format for automated analysis
- No manual content inspection required

## Implementation Notes

### Key Changes Made

1. **Enhanced ChatApiController** with comprehensive telemetry
2. **Modified StreamChatProvider** to support telemetry callbacks
3. **Updated ProcessAssistantFragment** with enrichment pipeline tracking
4. **Added ChatTelemetryMiddleware** for automatic context setup
5. **Configured dedicated logging channel** for telemetry data

### Integration Points

- Middleware automatically adds context to chat requests
- Actions use `ChatTelemetry` service for structured logging
- Correlation context propagates across async operations
- All events include consistent metadata and correlation info

### Performance Considerations

- Minimal overhead for telemetry operations
- Structured logging optimized for analysis
- Async operations don't block user experience
- Configurable verbosity for production tuning