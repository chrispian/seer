# CHAT-04 Provider Streaming Agent

## Mission
Abstract streaming chat providers so the UI can switch between Ollama, OpenAI, Anthropic, and OpenRouter without code churn, while keeping the SSE contract unchanged.

## Getting Started
1. `git fetch origin`
2. `git checkout -b feature/chat-04-provider-abstraction`
3. Confirm credentials/CLI access for each provider (env vars, config files). Ollama already runs locally.

## Key Context
- `ChatApiController@stream` currently hardcodes an Ollama streaming loop.
- Desired interface: `App\Services\Providers\*Provider::streamChat($messages, array $options)` yielding text deltas.
- Consider a manager/orchestrator (`App\Services\AIProviderManager`) that selects providers based on payload (`provider`, `model`).
- SSE payload expectations: `{ type: 'assistant_delta', data: '...' }` during stream, followed by `{ type: 'done' }`.
- Provider-specific needs:
  - Ollama: local HTTP stream (already working).
  - OpenAI: use official REST streaming (Chat Completions or Responses API).
  - Anthropic: use streaming Messages API.
  - OpenRouter: proxy to upstream; respect rate limits.
- Handle timeout/retry logic and translate provider errors into consistent SSE error events.

## Deliverables
- Provider manager class to choose and invoke the correct provider implementation.
- Dedicated provider classes/modules encapsulating HTTP clients, headers, streaming loops.
- Updated `ChatApiController@stream` to delegate streaming to the manager and relay deltas to the UI.
- Configuration documentation (env vars, credentials) added under `docs/` or `README.md` if missing.
- Automated tests covering provider selection logic and a simulated streaming loop (consider contract tests or fakes).

## Definition of Done
- Switching `provider` in the cached payload immediately uses the corresponding backend with accurate SSE output.
- Error handling/reporting consistent across providers with helpful logs.
- PR submitted with test results (`composer test`) and manual verification notes.
