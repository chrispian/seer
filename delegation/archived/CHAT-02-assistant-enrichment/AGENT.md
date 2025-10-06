# CHAT-02 Assistant Enrichment Agent

## Mission
Enhance assistant fragment writes in `ChatApiController@stream` so streaming responses capture rich metadata (provider/model/session, conversation linkage, latency, token/cost metrics) and optionally merge model-suggested JSON annotations.

## Getting Started
1. `git fetch origin`
2. `git checkout -b feature/chat-02-assistant-enrichment`
3. Verify local Ollama is running; prepare API keys for OpenAI/Anthropic/OpenRouter as stubs if needed.

## Key Context
- Assistant fragments are currently inserted via `DB::table('fragments')` in `ChatApiController@stream` once streaming completes.
- Chat cache payload (from `send()`) includes session/vault context that must propagate into assistant metadata.
- Standard fragment enrichment is intentionally skipped here; we only append structured metadata.
- Latency measurement should wrap the provider streaming call to capture total round-trip.
- Optional structured metadata block:
  ```
  <<<JSON_METADATA>>>
  { "tags": [...], "facets": {...}, "links": [...] }
  <<<END_JSON_METADATA>>>
  ```
  Parse after stream completion; ignore failures silently.

## Deliverables
- Assistant fragment insert populates `metadata` with `turn`, `conversation_id`, session/vault/project, latency, token usage, cost, router name.
- `relationships` captures `in_reply_to_id` (existing) and conversation linkage as appropriate.
- Structured metadata block (if present) merges into fragment metadata/tags/links without breaking plaintext response.
- Automated coverage under `tests/Feature/Chat` verifying metadata persistence and JSON parsing behaviors.
- Updated documentation (if any) describing new analytics fields.

## Definition of Done
- Assistant fragments contain analytics-ready metadata while preserving streaming UX.
- Robust against providers that do not supply token/cost data (fields optional or null-safe).
- PR raised on completion with test output (`composer test`) and manual verification notes.
