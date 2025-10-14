# Task: Migrate AI Provider System to Use Prism

**Status**: Not Started  
**Priority**: High  
**Estimated Effort**: 3-5 days  
**Created**: 2025-10-14  
**Type**: Technical Debt / Architecture Migration

## Problem Statement

The application currently uses custom AI provider implementations (`OpenAIProvider`, `AnthropicProvider`, etc.) that directly call provider APIs. This approach has several issues:

1. **Model-specific parameter handling**: Different models require different parameters:
   - GPT-5 and o1 models use `max_completion_tokens` instead of `max_tokens`
   - GPT-5 doesn't support custom temperature values (only default)
   - Different models have different parameter constraints
   
2. **Maintenance burden**: Every new model or parameter change requires manual code updates across multiple provider classes

3. **Prism already installed but unused**: The `prism-php/prism` package (v0.86.0) is installed and configured (`config/prism.php`) but not integrated

4. **Inconsistent behavior**: Custom providers don't handle all edge cases and model-specific quirks

## Current Architecture

```
ChatApiController
    └─> ToolAwarePipeline
        └─> AIProviderManager
            └─> Custom Providers (OpenAIProvider, AnthropicProvider, etc.)
                └─> Direct HTTP API calls
```

### Current Provider Files
- `app/Services/AI/Providers/OpenAIProvider.php`
- `app/Services/AI/Providers/AnthropicProvider.php`
- `app/Services/AI/Providers/OllamaProvider.php`
- `app/Services/AI/Providers/OpenRouterProvider.php`
- `app/Services/AI/Providers/AbstractAIProvider.php`
- `app/Services/AI/AIProviderManager.php`

## Target Architecture

```
ChatApiController
    └─> ToolAwarePipeline
        └─> PrismProviderAdapter (new)
            └─> Prism Library
                └─> Handles all model-specific parameters automatically
```

## Benefits of Migration

1. **Automatic model handling**: Prism handles all model-specific parameters, constraints, and API differences
2. **Reduced code**: Remove ~500+ lines of custom provider code
3. **Better error handling**: Prism provides consistent error handling across providers
4. **Streaming support**: Built-in streaming for all providers
5. **Future-proof**: New models automatically supported via Prism updates
6. **Testing**: Prism is well-tested and maintained by EchoLabs

## Implementation Plan

### Phase 1: Investigation & Planning (1 day)
- [ ] Review Prism documentation for chat completion API
- [ ] Review Prism documentation for streaming API
- [ ] Identify all places where AI providers are called
- [ ] Map current provider methods to Prism equivalents
- [ ] Create compatibility matrix for current features vs Prism features
- [ ] Design adapter layer to maintain current interface

### Phase 2: Create Prism Adapter (1 day)
- [ ] Create `PrismProviderAdapter` that implements `AIProviderInterface`
- [ ] Implement `generateText()` method using Prism
- [ ] Implement `streamChat()` method using Prism
- [ ] Implement `generateEmbedding()` method using Prism
- [ ] Add credential management integration
- [ ] Add telemetry/logging integration (maintain current LLM telemetry)

### Phase 3: Update Configuration (0.5 day)
- [ ] Update `config/prism.php` to include all providers
- [ ] Map `ai_credentials` table to Prism configuration
- [ ] Update `AIProviderManager` to use Prism adapter
- [ ] Ensure backward compatibility with existing code

### Phase 4: Testing & Error Handling (1.5 days)
- [ ] Test OpenAI provider (GPT-4, GPT-4o, GPT-5)
- [ ] Test Anthropic provider (Claude 3.5, Claude 4, Claude Opus)
- [ ] Test Ollama provider
- [ ] Test OpenRouter provider
- [ ] Test tool-aware pipeline with Prism
- [ ] Test chat interface with all providers
- [ ] Test streaming responses
- [ ] Test error handling and retries
- [ ] Verify telemetry still works
- [ ] **Add user-friendly error display in chat UI**:
  - [ ] When API errors occur, display error message in chat (NOT saved to fragments/history)
  - [ ] Format: "Error returned from API:" header with scrollable error details below
  - [ ] Add copy button to easily copy error message for debugging
  - [ ] Error message should be ephemeral (disappears on page refresh, not in history)
  - [ ] Apply to all error types: API errors, parameter errors, credit errors, etc.
  - [ ] **For large errors (> limit)**: Use summarize agent to generate concise error summary
    - Return meaningful short message with critical details (model, error type, key parameters)
    - Make it easy for user/agent to start investigating without reading full stacktrace
    - Still provide full error in scrollable section with copy button
    - Example: "OpenAI API Error: GPT-5 model doesn't support temperature=0.1. Use default temperature or switch to GPT-4." instead of full 500-line error dump

### Phase 5: Migration & Cleanup (0.5 day)
- [ ] Deploy to production with feature flag
- [ ] Monitor for issues
- [ ] Remove old provider classes once stable
- [ ] Update documentation

## Technical Details

### Prism Configuration Location
- Package: `prism-php/prism` (v0.86.0)
- Config: `config/prism.php`
- Providers configured: OpenAI, Anthropic, Ollama, OpenRouter

### Key Integration Points

**1. AIProviderManager** (`app/Services/AI/AIProviderManager.php`)
```php
// Current
$this->providers = [
    'openai' => new OpenAIProvider($config),
    'anthropic' => new AnthropicProvider($config),
    // ...
];

// Target
$this->providers = [
    'openai' => new PrismProviderAdapter('openai', $config),
    'anthropic' => new PrismProviderAdapter('anthropic', $config),
    // ...
];
```

**2. Tool-Aware Pipeline Components**
- `Router` (calls LLM with specific temperature/max_tokens)
- `ToolSelector` (calls LLM with specific parameters)
- `OutcomeSummarizer` (calls LLM)
- `FinalComposer` (calls LLM)

All of these should work transparently with Prism adapter.

### Credential Management

Current credential management (`AICredential` model) should be integrated:

```php
class PrismProviderAdapter {
    protected function getApiKey(string $provider): string {
        $credential = AICredential::getActiveCredential($provider);
        return $credential->getCredentials()['key'];
    }
}
```

### Telemetry Integration

Maintain current LLM telemetry system:
- Log every API call to `storage/logs/laravel.log`
- Include: provider, model, tokens, cost, response time
- Use existing `LLMTelemetry` service

## Risks & Mitigation

**Risk 1**: Prism API different from current interface
- **Mitigation**: Create adapter layer to maintain existing interface

**Risk 2**: Feature parity issues
- **Mitigation**: Phase 1 includes compatibility matrix review

**Risk 3**: Production issues during migration
- **Mitigation**: Use feature flag for gradual rollout

**Risk 4**: Performance differences
- **Mitigation**: Load test before full migration

## Success Criteria

- [ ] All providers work through Prism
- [ ] GPT-5 models work without custom parameter handling
- [ ] All tool-aware pipeline tests pass
- [ ] Chat interface works with all providers
- [ ] Telemetry continues to work
- [ ] No regressions in existing functionality
- [ ] Code reduced by at least 400 lines
- [ ] API errors display user-friendly messages in chat UI (ephemeral, with copy button)

## Related Issues

- **GPT-5 Parameter Issues**: Models fail with custom temperature and max_tokens
- **Anthropic Credit Errors**: Better error handling needed
- **Model-Specific Quirks**: O1 models, reasoning models, etc.

## References

- Prism Documentation: https://github.com/echolabsdev/prism
- Current Provider Implementation: `app/Services/AI/Providers/`
- Issue Discussion: Session summary 2025-10-14

## Notes

- Prism is maintained by EchoLabs (same team as Prism)
- Package is already installed and composer.json lists `"prism-php/prism": "^0.86.0"`
- Config file exists at `config/prism.php` with provider credentials
- This is a **refactor**, not a new feature - existing functionality should remain identical
