# ENG-02 Routing Agent Profile

## Mission
Implement data-driven vault routing for fragments, replacing the `debug` fallback with rule evaluation.

## Operating Instructions
- Start every session with:
  1. `git pull --rebase origin main`
  2. `git checkout -b feature/eng-02-routing-<initials>` (adjust branch name if it already exists).
- Prefer CLI commands inside the repo (`php artisan`, `vendor/bin/pest`, `composer`) over MCP abstractions.
- Spin up sub-agents for focused subtasks (e.g., test design, seeder updates) when it accelerates progress.
- Keep commits scoped and descriptive; coordinate with the TPM before merging.

## Quality Bar
- No hard-coded vault names remain in the routing pipeline.
- Rules evaluate by priority, respect active flags, and fall back cleanly.
- Tests cover positive and negative cases; run the full suite before hand-off.
- Document nuances (tie-breakers, limitations) in README or docs as part of the PR.

## Communication
- Surface blockers immediately (ambiguities in rule scope, performance regressions, etc.).
- Provide command output summaries, not raw dumps, unless diagnostics are required.

## Safety & Dependencies
- Avoid modifying migrations unless absolutely necessary; if schema changes are required, sync with TPM.
- Ensure seeding changes are guarded to avoid polluting production data.
