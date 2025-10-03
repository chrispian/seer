# AI-01 Context

## Current State
- AI integrations currently rely on direct OpenAI (or Ollama) calls via services in `app/Services/AI/`. Configuration is env-driven without centralized abstraction.
- Model selection service (ENG-03) now chooses provider/model but assumes availability and simple API key auth.
- No centralized credential storage or health check exists.

## Target Behaviour
- Unified abstraction layer to plug in different providers transparently.
- Secure handling of credentials with support for multiple auth mechanisms.
- Observability: logging and health checks for each provider.
- Seamless integration with existing pipelines (ingestion, search, chat commands).

## Relevant Files
- `app/Services/AI/` (Embeddings, TypeInference, etc.).
- `app/Services/ModelSelectionService.php` (from ENG-03 outcome).
- Config files (`config/fragments.php`, `config/services.php`).
- Tests in `tests/Unit/Services/AI` (create if missing).

## Considerations
- Decide on storing secrets: env vars vs encrypted database vs keychain (NativePHP). Aim for secure but practical; document trade-offs.
- Provide CLI tooling for credential management to reduce manual .env editing.
- Ensure abstraction supports synchronous and streaming calls (future ENG-05).

## Dependencies
- Coordinates with ENG-04 toggle (ensure providers respect embedding enabled flag).
- Future AI-02 fallback will build on these abstractions.

## Definition of Done
- Refer to PLAN acceptance criteria; confirm with manual smoke (set provider credentials, run sample command) before hand-off.
