# CHAT-01 User Prompt Ingestion Agent

## Mission
Replace the raw fragment insert in `app/Http/Controllers/ChatApiController.php:send` with the standard ingestion pipeline so chat prompts receive full enrichment (type resolution, tags, dedupe, metadata merge, link creation).

## Getting Started
1. `git fetch origin`
2. `git checkout -b feature/chat-01-prompt-pipeline`
3. Ensure `composer install` and `npm install` have been run; no new dependencies expected.

## Key Context
- Current chat endpoints: `POST /api/messages` calls `ChatApiController@send`; `GET /api/chat/stream/{messageId}` streams assistant output.
- Chat cache payload (see `ChatApiController@send`) holds provider/model/session metadata consumed by `stream()`.
- Legacy ingestion lives in `App\Services\FragmentIngestService` (preferred) and/or `App\Http\Controllers\FragmentController::store`. Reuse whichever ensures enrichment hooks fire.
- Fragments schema: `app/Models/Fragment.php`, `fragments-schema.sql` (relationships, metadata JSON columns) for field expectations.
- Tests live under `tests/Feature` and `tests/Unit`; leverage Pest for coverage.

## Deliverables
- `ChatApiController@send` delegates to the ingestion service/controller instead of `DB::table('fragments')->insert`.
- The created fragment retains existing chat metadata (session, vault, turn, etc.) and still feeds the cache used by `stream()`.
- Feature/coverage tests verifying that the ingestion path triggers enrichment (e.g., tags or metadata merging). Add to `tests/Feature/Chat/` if needed.
- Update any related docs within `docs/` or `delegation/` if the workflow changes.
- Conclude with passing `composer test` and include results in the PR description.

## Definition of Done
- User prompts create fragments through the canonical pipeline with no regression to streaming behavior.
- Cached payload structure remains stable for downstream tasks.
- PR raised against `main` after user validation; include test output and manual verification notes.
