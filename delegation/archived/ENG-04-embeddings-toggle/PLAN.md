# ENG-04 Embeddings Toggle — Implementation Plan

## Objective
Introduce a configurable kill-switch for vector embeddings so the pipeline degrades gracefully when embeddings are disabled, and provide tooling to backfill embeddings after re-enabling.

## Deliverables
- Config-driven toggle for embeddings (env + config cache aware).
- Guardrails in ingestion/search pipelines to bypass vector work when disabled.
- Admin command (artisan) to backfill missing embeddings for fragments.
- Documentation describing how to toggle embeddings and run the backfill.
- Automated tests covering enabled/disabled flows.

## Work Breakdown
1. **Branch Prep**
   - `git fetch origin` (notify TPM if sandbox blocks writes) and `git pull --rebase origin main`.
   - `git checkout -b feature/eng-04-embeddings-toggle`.
2. **Config Updates**
   - Expand `config/fragments.php` to support an `enabled` flag sourced from `EMBEDDINGS_ENABLED` env.
   - Ensure caching (`php artisan config:cache`) respects the toggle; document default.
3. **Pipeline Guards**
   - Update actions/jobs (`EmbedFragmentAction`, `EmbedFragment` job, search commands/controllers) to short-circuit cleanly when disabled.
   - Avoid throwing when pgvector columns missing; return fallback data.
4. **Search Path Adjustments**
   - Ensure hybrid search falls back to text-only search when embeddings disabled (reuse existing fallback logic; add coverage).
5. **Backfill Command**
   - Create `artisan embeddings:backfill` (name tbd) that queues embedding jobs for fragments missing embeddings when the toggle is on.
   - Support provider/model options and dry-run summary.
6. **Testing**
   - Add unit tests for toggle logic and command.
   - Feature tests for search command in disabled mode.
   - Ensure Pest suites pass with config toggled off (set env within tests).
7. **Docs & Comms**
   - Document toggle/backfill usage in `docs/` and update `PROJECT_PLAN.md` checkboxes.
   - Update README or `.env.example` with `EMBEDDINGS_ENABLED` guidance.
8. **Handoff**
   - Run full test suite; gather CLI output.
   - Push branch, open PR summarising behaviour and including command examples.

## Acceptance Criteria
- Setting `EMBEDDINGS_ENABLED=false` prevents new embedding jobs and uses text-only search with user-friendly messaging.
- Backfill command repopulates embeddings queue when re-enabled.
- Tests verify both enabled/disabled states.
- Documentation provides clear operational steps.

## Risks & Notes
- Ensure backfill command doesn’t overwhelm queue; consider batching.
- Guard for databases without pgvector (SQLite) to avoid SQL errors.
- Communicate to TPM if additional configuration (queue connection) is required.
