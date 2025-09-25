# ENG-01 Phase 1: Chaos Model Selection Plan

## Objective
Refactor `ParseChaosFragment` to leverage `ModelSelectionService` and provider abstraction instead of the hardcoded Ollama llama3 call, ensuring consistent configuration, determinism, and fallback handling.

## Deliverables
- `ParseChaosFragment` uses ModelSelectionService (text-operation context) to choose provider/model/parameters.
- Support multiple providers (OpenAI, Anthropic, Ollama) via AI provider abstraction created in AI-01.
- Respect deterministic parameter configuration (temperature/top_p) from config.
- Enhanced prompt handling with system prompt and JSON schema validation (reuse from Phase 1 validation work).
- Logging updated with correlation ID + provider/model metadata.
- Tests covering model selection behaviour and fallback path.

## Work Breakdown
1. **Analysis**
   - Review current hardcoded HTTP call to Ollama.
   - Identify proper context payload for ModelSelectionService.
2. **Integration**
   - Inject ModelSelectionService (and provider manager if needed) into ParseChaosFragment.
   - Request model selection with `operation_type=text`, `command=split_chaos`, plus vault/project context.
   - Use abstraction to invoke provider with deterministic params from config.
3. **Validation & Error Handling**
   - Reuse JSON schema validation from Phase 1 to ensure responses are arrays of fragments.
   - Implement retries/fallback if provider unavailable (e.g., call fallback provider).
4. **Logging & Metadata**
   - Add structured logs with correlation ID, provider, model, latency, retry count.
   - Store model metadata in parent fragment’s `metadata['chaos_lineage']` update.
5. **Testing**
   - Add unit tests mocking ModelSelectionService to ensure correct provider usage and fallback.
   - Add integration test for schema validation fallback.
6. **Documentation**
   - Update pipeline doc with new behaviour.

## Acceptance Criteria
- ParseChaosFragment no longer references `http://localhost:11434`; all calls go through provider abstraction.
- Deterministic parameters and fallback logic documented and tested.
- Logs include provider/model info; metadata records chosen model.
- Tests pass (`vendor/bin/pest`).

## Risks
- Ensure asynchronous dispatch still works after refactor.
- Confirm provider abstraction supports streaming=false expectations.
