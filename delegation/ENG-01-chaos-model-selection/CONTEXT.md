# Context: Chaos Model Selection

## Current Implementation
- `ParseChaosFragment` calls Ollama via `Http::post('http://localhost:11434/api/generate', ['model' => 'llama3', ...])`.
- No use of `ModelSelectionService`; temperature defaults from provider (likely non-deterministic).
- Prompt lacks system role; validation uses regex to strip markdown.

## Dependencies
- ModelSelectionService (ENG-03) now chooses models with context.
- AI provider abstraction (AI-01) provides provider-specific request helpers.
- Phase 1 JSON schema validation logic available from previous work.

## Goals
- Utilize selection context (`operation_type=text`, `command=split_chaos`).
- Respect configured deterministic params (`temperature`, `top_p`, `max_tokens`).
- Support fallback provider if primary fails; log attempts.
- Update fragment metadata with provider/model lineage.

## Testing Targets
- Unit test: ensures ParseChaosFragment requests model selection and uses chosen provider.
- Integration test: ensures fallback invoked when first provider fails.
- Schema validation ensures response array of fragments; invalid JSON triggers retry/fallback.
