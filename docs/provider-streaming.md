# Provider Streaming System

## Overview

The Fragments Engine supports real-time streaming chat completions from multiple AI providers through a unified interface. This allows the UI to seamlessly switch between Ollama, OpenAI, Anthropic, and OpenRouter without code changes.

## Supported Providers

| Provider | Streaming Support | Configuration Required |
|----------|-------------------|------------------------|
| **Ollama** | ✅ | `OLLAMA_URL` (default: `http://localhost:11434`) |
| **OpenAI** | ✅ | `OPENAI_API_KEY`, `OPENAI_URL` (optional) |
| **Anthropic** | ✅ | `ANTHROPIC_API_KEY`, `ANTHROPIC_API_VERSION` (optional) |
| **OpenRouter** | ✅ | `OPENROUTER_API_KEY`, optional referer/title headers |

## Configuration

### Environment Variables

```bash
# Ollama (local)
OLLAMA_URL=http://localhost:11434

# OpenAI
OPENAI_API_KEY=sk-...
OPENAI_URL=https://api.openai.com/v1  # optional

# Anthropic  
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_API_VERSION=2023-06-01  # optional

# OpenRouter
OPENROUTER_API_KEY=sk-or-...
OPENROUTER_REFERER=https://yourdomain.com  # optional
OPENROUTER_TITLE=Your App Name  # optional
```

### Provider Configuration Files

Configuration is automatically loaded from:
- `config/prism.php` - Provider URLs and API keys
- `config/fragments.php` - Model selection and defaults

## API Usage

### Streaming Chat Request

1. **Create Message Session**:
   ```bash
   POST /api/messages
   {
     "content": "Hello, how are you?",
     "provider": "openai",  # or "ollama", "anthropic", "openrouter"
     "model": "gpt-4",
     "conversation_id": "optional-conversation-id"
   }
   ```

2. **Stream Response**:
   ```bash
   GET /api/chat/stream/{message_id}
   ```

   Returns Server-Sent Events (SSE):
   ```
   data: {"type":"assistant_delta","content":"Hello"}
   data: {"type":"assistant_delta","content":" there!"}
   data: {"type":"done"}
   ```

### Provider Selection

The system automatically validates:
- Provider exists and is configured
- Provider supports streaming
- Provider is available (API keys configured, etc.)

## Architecture

### Core Components

```
ChatApiController
    ↓
StreamChatProvider (Action)
    ↓
AIProviderManager
    ↓
[OllamaProvider|OpenAIProvider|AnthropicProvider|OpenRouterProvider]
```

### Provider Interface

All providers implement:
```php
interface AIProviderInterface {
    public function streamChat(array $messages, array $options = []): \Generator;
    public function supportsStreaming(): bool;
    // ... other methods
}
```

### Streaming Flow

1. **Session Retrieval**: Get cached message context
2. **Provider Validation**: Ensure provider supports streaming
3. **Stream Initiation**: Start generator-based streaming
4. **Delta Processing**: Emit SSE events for each content delta
5. **Completion**: Signal end of stream and process final response
6. **Fragment Processing**: Create assistant fragment with metadata

## Error Handling

The system provides consistent error handling across providers:

- **Provider not found**: HTTP 400 with error message
- **Provider doesn't support streaming**: HTTP 400 with error message  
- **Provider unavailable**: HTTP 400 with configuration error
- **Streaming errors**: Graceful error messages in SSE stream

## Testing

### Unit Tests
- `StreamChatProviderTest.php` - Action-level streaming logic
- `StreamingActionsTest.php` - Provider validation and utilities

### Integration Tests  
- `ProviderStreamingTest.php` - End-to-end streaming flows
- `ConversationTrackingTest.php` - Conversation ID consistency

### Running Tests
```bash
composer test -- --filter="Streaming"
```

## Models and Capabilities

### Provider-Specific Models

**Ollama** (local models):
- `llama3:latest`, `llama3:8b`, `llama3:70b`
- `mistral:latest`, `codellama:latest`
- Any locally installed Ollama model

**OpenAI**:
- `gpt-4o`, `gpt-4o-mini`
- `gpt-4-turbo`, `gpt-4`
- `gpt-3.5-turbo`

**Anthropic**:
- `claude-3-5-sonnet-latest`
- `claude-3-5-haiku-latest`
- `claude-3-opus-latest`

**OpenRouter** (proxy to multiple providers):
- `anthropic/claude-3.5-sonnet`
- `openai/gpt-4o`
- `meta-llama/llama-3.1-70b-instruct`
- 100+ available models

### Streaming Features

All providers support:
- ✅ Real-time token streaming
- ✅ Consistent SSE output format
- ✅ Error handling and recovery
- ✅ Token usage tracking
- ✅ Conversation context preservation

## Troubleshooting

### Common Issues

**Provider not available**:
- Check API keys in environment variables
- Verify provider configuration in `config/prism.php`
- Test provider health: Check logs for connectivity issues

**Streaming timeouts**:
- Verify network connectivity to provider APIs
- Check provider-specific rate limits
- Monitor provider service status

**Model not found**:
- Verify model name is correct for provider
- Check if model is available in your plan/region
- Use provider-specific model listing endpoints

### Debug Commands

```bash
# Check provider status
php artisan tinker
>>> app(\App\Services\AI\AIProviderManager::class)->healthCheckAll()

# Test provider availability
>>> app(\App\Services\AI\AIProviderManager::class)->getProvider('openai')->supportsStreaming()
```

## Migration Notes

### From Legacy System

The new provider system replaces these deprecated Actions:
- ~~`ValidateStreamingProvider`~~ → Now uses `AIProviderManager`
- ~~`ConfigureProviderClient`~~ → Handled by provider implementations
- ~~`StreamProviderResponse`~~ → Replaced by `StreamChatProvider`

### Backward Compatibility

- Existing SSE format unchanged
- Same API endpoints (`/api/messages`, `/api/chat/stream/{id}`)
- Configuration keys preserved
- Token extraction and fragment processing unchanged