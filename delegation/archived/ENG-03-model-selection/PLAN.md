# ENG-03 Model Selection Step — Implementation Plan

## Objective
Introduce a configurable model-selection layer so fragments and chat sessions capture which AI provider/model handled each action, with strategy services and UI transparency.

## Deliverables
- Catalog of available AI providers/models (OpenAI, Azure OpenAI, Anthropic, local Ollama) with required config keys.
- Strategy service that chooses a model based on weighted rules (project/vault preferences, command overrides, fallbacks).
- Schema updates to store model metadata on fragments and chat sessions.
- UI surfacing of the chosen model (toast or panel notice) for user transparency.
- Tests validating strategy choices and data persistence.

## Work Breakdown
1. **Branch Prep**
   - `git fetch origin` (if sandbox blocks, notify TPM) then `git pull --rebase origin main`.
   - `git checkout -b feature/eng-03-model-selection`.
2. **Discovery & Design**
   - Review existing AI services (`app/Services/AI/*`).
   - Document provider configuration keys (env vars) and default models.
   - Confirm selection criteria with TPM (priority order: per-command override → project preference → global default → fallback).
3. **Schema & Models**
   - Add nullable columns to fragments/chat_sessions for `model_provider` and `model_name` (guard migrations for SQLite/Postgres compatibility).
   - Update Eloquent casts/fillables and factories.
4. **Strategy Service**
   - Create `ModelSelectionService` (or similar) encapsulating decision logic; accept context payload (command, project, vault, session metadata).
   - Implement weighting/override rules; include fallback to default provider.
5. **Integration**
   - Update command handlers/pipeline steps that invoke AI to call the strategy service before execution.
   - Persist chosen model info on fragment/session records.
6. **UI Transparency**
   - Update relevant UI components (chat message, toasts) to show “Powered by {provider/model}”.
   - Ensure verbosity settings respect new toasts/messages.
7. **Testing**
   - Add unit tests for strategy branching.
   - Feature tests verifying metadata persistence after running representative commands.
8. **Documentation & Cleanup**
   - Update `docs/` or README with configuration instructions and strategy overview.
   - Check off `PROJECT_PLAN.md` items.
   - Run `vendor/bin/pest` and linting before hand-off.
9. **Handoff**
   - Summarise changes, attach test output, push branch, open PR.

## Acceptance Criteria
- Strategy service selects models deterministically per rules with tests covering each branch.
- Fragments/chat sessions record provider/model metadata without breaking existing flows.
- Users see which model handled their request (respecting verbosity settings).
- No regressions in existing AI calls; default provider continues to function when optional configs missing.

## Risks & Notes
- Be mindful of secret handling; do not expose raw API keys in logs.
- Coordinate schema changes carefully; run migrations locally and ensure down() logic is sound.
- Keep strategy extensible for future providers.
