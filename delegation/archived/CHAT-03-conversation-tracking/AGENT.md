# CHAT-03 Conversation Tracking Agent

## Mission
Establish a stable `conversation_id` propagated across chat turns, ensuring both user and assistant fragments are tagged consistently for analytics and history reconstruction.

## Getting Started
1. `git fetch origin`
2. `git checkout -b feature/chat-03-conversation-id`
3. No new dependencies expected; confirm migrations are up to date (`php artisan migrate`).

## Key Context
- `ChatApiController@send` currently caches conversation context but does not persist a dedicated identifier.
- `stream()` depends on the cached payload to link assistant responses; ensure the ID survives restarts/cache misses gracefully.
- Acceptable storage: fragment `metadata` or `relationships` JSON (no schema change yet). Future migration may add a column.
- Ensure backward compatibility if existing fragments lack conversation IDs.

## Deliverables
- Generate a UUID (or reuse existing session identifier) in `send()` when none is supplied.
- Persist `conversation_id` inside user fragment metadata and cached payload.
- On `stream()`, include the same ID in assistant fragment metadata/relationships.
- Update cache invalidation/lookup logic to rely on the new field when rehydrating history.
- Add feature tests showing that sequential messages share the ID and that pre-existing threads remain accessible.

## Definition of Done
- Both sides of each chat turn store an identical `conversation_id`.
- System gracefully handles missing/legacy IDs without errors.
- PR submission includes `composer test` output and manual verification steps.
