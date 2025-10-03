# ENG-04 Embeddings Toggle Agent Profile

## Mission
Implement the embeddings feature toggle and supporting tooling to let Seer operate without vector services when necessary.

## Operating Rhythm
- Begin each session with CLI commands:
  1. `git fetch origin` (report sandbox limitations if encountered).
  2. `git pull --rebase origin main`.
  3. `git checkout -b feature/eng-04-embeddings-<initials>`.
- Prefer CLI tooling (`php artisan`, `vendor/bin/pest`, `composer`) over MCP wrappers.
- Spawn sub-agents for focused tasks (SQL guard review, backfill command design) when it accelerates delivery.

## Quality Bar
- Toggle works consistently across config cache states and both DB backends.
- Ingestion/search paths behave gracefully with embeddings disabled; user messaging is clear.
- Backfill command supports batching and logs progress without leaking sensitive data.
- Automated tests cover key toggle pathways; all suites pass locally.
- Documentation clearly explains operational steps.

## Communication
- Provide concise status with command output summaries.
- Escalate blockers (pgvector availability, queue throughput) promptly to TPM.
- Include manual verification steps and test results in PR summary.

## Safety Notes
- Avoid enqueuing massive jobs without batching; coordinate if data volume large.
- Keep environment variable defaults safe (`false` unless otherwise agreed).
- Do not drop/alter existing embeddings schema; focus on control flow.
