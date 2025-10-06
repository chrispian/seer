# AI-01 Provider Abstraction — Implementation Plan

## Objective
Design and implement a pluggable AI provider abstraction supporting API key and OAuth flows (OpenAI, Azure OpenAI, Anthropic, local Ollama) with secure credential handling.

## Deliverables
- Provider interface contract and concrete adapters for targeted providers.
- Configuration schema (config + env) capturing credentials per provider.
- Secure credential storage (encrypted database or integration with OS keystore) with management commands/UI.
- Health check utilities to validate provider availability.
- Documentation describing setup, credential management, and fallback order.

## Work Breakdown
1. **Branch Prep**
   - `git fetch origin` / `git pull --rebase origin main` (report if sandbox disallows writes).
   - `git checkout -b feature/ai-01-provider-abstraction`.
2. **Requirements Gathering**
   - Confirm provider list and auth flows (API key, OAuth device code, local socket).
   - Align fallback priorities with ENG/AI leads.
3. **Interface Design**
   - Define `ProviderInterface` (methods: `supports`, `authenticate`, `invokeCompletion`, `invokeEmbedding`, etc.).
   - Draft normalization for request/response payloads.
4. **Adapter Implementation**
   - Build adapters for OpenAI/Azure/Anthropic/Ollama.
   - Implement shared HTTP client with retry/backoff.
   - Handle OAuth token refresh for providers that need it; store in secure cache.
5. **Credential Storage**
   - Decide storage mechanism: encrypted column (Laravel encrypt), or rely on environment-managed secrets with optional NativePHP keychain stub.
   - Provide artisan commands to add/list/remove credentials.
   - Ensure credentials masked in logs.
6. **Configuration & Health Checks**
   - Expand `config/fragments.php` or create `config/ai-providers.php` with provider metadata.
   - Build health check command (e.g., `ai:health`) to test connectivity; integrate with logging/telemetry.
7. **Integration Points**
   - Update model-selection service (ENG-03 output) to use provider abstraction.
   - Ensure slash commands/ingestion interact via abstraction, not provider-specific code.
8. **Testing**
   - Unit tests for adapters (mock HTTP where applicable).
   - Feature tests for credential commands and fallback behaviour.
9. **Documentation**
   - Update docs/README with configuration steps, CLI usage, and security considerations.
10. **Handoff**
   - Run full test suite; capture command output.
   - Push branch, open PR with summary and setup instructions.

## Acceptance Criteria
- Providers can be configured independently with secure credential management.
- Abstraction integrates with existing model selection and pipeline flows.
- Health checks and CLI commands function as documented.
- Tests cover provider selection, credential handling, and failure modes.

## Risks & Notes
- OAuth flows may require out-of-band user interaction; design CLI prompts carefully.
- Consider future expansion (Google, local LLMs); keep abstraction extensible.
- Coordinate with upcoming ENG-05 streaming work to ensure compatibility.
