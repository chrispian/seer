# ENG-03 Context

## Existing Assets
- `app/Services/AI/Embeddings.php`, `TypeInferenceService.php`, and command handlers under `app/Actions/Commands/` show how current AI calls are wired.
- ENV vars: `OPENAI_API_KEY`, `OPENAI_EMBEDDING_MODEL`, potential placeholders for Azure/Anthropic not yet formalised.
- Fragments and chat sessions currently lack columns to store provider/model metadata.

## Target Behaviour
- Before any AI call, consult a strategy service using the incoming context (command name, project, vault, user preferences, fallback queue).
- Persist the chosen provider/model back onto the resulting fragment or session.
- Expose provider/model to the UI (e.g., toast generated in `command-result` component or chat message metadata chips).

## Constraints
- Application runs on Laravel 12, PHP 8.3. Use migration patterns compatible with Postgres (primary) and SQLite (dev/tests).
- Livewire/Flux mix in UI; ensure new UI outputs work in both contexts.
- Keep strategy configurable via `config/fragments.php` (consider adding `models` section).

## Open Questions
- Are there per-user overrides? (Default to project/vault-level plus global default. Flag if user-level needed.)
- Should we log model decisions? (Yes—add debug/info logging with correlation IDs, but avoid secrets.)
- How to handle provider outage? (Integrate with ENG-04/AI-02 fallback later, but design strategy with graceful failure path.)

## Definition of Done (from PLAN)
- Strategy, persistence, UI transparency, and tests all implemented.
- Documentation updated with configuration instructions and selection flow.
