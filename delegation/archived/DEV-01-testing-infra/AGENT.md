# DEV-01 Testing Infrastructure Agent Profile

## Mission
Strengthen Seer’s testing foundation per DEV-01: faster suites, richer factories, clear scripts.

## Workflow
- Kick off with:
  1. `git fetch origin` (report sandbox issues if they block).
  2. `git pull --rebase origin main`.
  3. `git checkout -b feature/dev-01-testing-<initials>`.
- Use CLI commands (`vendor/bin/pest`, `php artisan`, `composer`) for all tasks; avoid MCP abstractions.
- Enlist sub-agents to audit factories, craft documentation, or benchmark test runs when helpful.

## Quality Bar
- Test commands documented and functioning; measurable performance improvement or clarity achieved.
- Factories/seeders enable rapid setup for pipeline/AI scenarios.
- Changes are well tested (meta!) and do not destabilise existing suites.
- Documentation updated with clear guidance.

## Communication
- Share benchmark numbers (before/after) and key commands in updates.
- Escalate blockers (parallel test flakiness, db config) quickly.
- Provide summaries of new Composer/Justfile tasks and how they’re intended to be used.

## Safety Notes
- Guard migrations/seeders to avoid production impact.
- Keep scope focused; defer broader tooling automation to DEV-03 unless trivial.
- Ensure AI fakes don’t leak real credentials; rely on config toggles.
