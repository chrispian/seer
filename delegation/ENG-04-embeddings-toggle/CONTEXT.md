# ENG-04 Context

## Current State
- Embedding logic lives in `App\Actions\EmbedFragmentAction` and `App\Jobs\EmbedFragment`; both already reference `config('fragments.embeddings.enabled')` but the config lacks a robust flag and fallbacks are partial.
- Search command (`App\Actions\Commands\SearchCommand`) and `FragmentController@hybridSearch` attempt hybrid queries even when embeddings disabled, leading to errors if pgvector absent.
- Config file `config/fragments.php` currently stores provider/model/version without an explicit enabled flag.

## Target Behaviour
- Single source of truth for enabling/disabling embeddings via env/config.
- When disabled:
  - Embedding jobs/actions exit early with logging.
  - Search commands fall back to text-based queries and inform the user.
  - UI should not imply vector scoring; handle missing similarity metrics gracefully.
- When re-enabled, admins can rebuild embeddings via CLI command.

## Key Files
- `config/fragments.php`
- `app/Actions/EmbedFragmentAction.php`
- `app/Jobs/EmbedFragment.php`
- `App\Actions\Commands\SearchCommand`
- `app/Http/Controllers/FragmentController.php`
- Tests: create coverage under `tests/Feature` and `tests/Unit` as appropriate.

## Considerations
- SQLite environments lack pgvector; ensure SQL statements guarded with capability checks.
- Queueing: backfill command should allow chunking (e.g., `--batch=100`) to avoid overwhelming workers.
- Logging: maintain traceable logs (`Log::info`) noting toggle state.

## Dependencies & Follow-ups
- Ties into AI-02 fallback work; design toggle to integrate with provider detection later.
- Coordinate with DEV-01 outputs for testing commands/scripts.

## Definition of Done
Refer to PLAN acceptance criteria; verify with manual smoke (toggle env, run ingestion/search) before hand-off.
