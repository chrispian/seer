# ENG-02 Data-Driven Vault Routing — Implementation Plan

## Objective
Route newly created fragments into vaults/projects using persisted routing rules instead of the hard-coded `debug` vault. Ensure the pipeline, slash command UI, and automated tests reflect the new behaviour.

## Deliverables
- Updated routing pipeline that applies the highest-priority active rule matching a fragment.
- Graceful fallback when no rules match (default vault/project logic).
- Seeder + factories enabling demo data for routing rules.
- Feature/Unit tests covering routing decisions and command UI basics.
- Documentation updates (PROJECT_PLAN progress box + short note in docs/ or README changelog section).

## Work Breakdown
1. **Branch Prep**
   - `git pull --rebase origin main`
   - `git checkout -b feature/eng-02-routing`
2. **Service Integration**
   - Extend `VaultRoutingRuleService` with a method to resolve a target for a given fragment context (vault/project/type/metadata).
   - Inject the service into `RouteToVault` (and any other pipeline entrypoints) and remove the hard-coded `'debug'` assignment.
   - Decide tie-breakers (by priority, then created_at or ID) and ensure inactive rules are skipped.
3. **Fallback Logic**
   - Confirm default vault/project strategy (existing `Vault::getDefault()`, `Project::getDefaultForVault()`); cover scenario where fragment already has a vault override from `/vault:` directive.
   - Ensure routing rules respect explicit fragment metadata overrides before applying defaults.
4. **Testing**
   - Add unit tests for the new resolver method.
   - Expand existing feature tests (e.g., `RoutingCommandTest`) or add pipeline-level tests to prove fragments land in the configured vault/project.
   - Include failing scenario tests (inactive rule, missing target vault).
5. **Seeding + Demo Data**
   - Update factories/seeders so local dev has at least two vaults, projects, and example rules.
   - If a seeder already exists, add a toggled block (guard with env) rather than running automatically in production.
6. **Docs + Cleanup**
   - Update `PROJECT_PLAN.md` checkbox for “Replace hard-coded 'debug' vault...”.
   - Add a short entry to `docs/` or README noting ENG-02 completion and how to use `/routing`.
   - Run lint/tests; tidy diff.
7. **Handoff**
   - `git status` review.
   - Push branch and open PR (details and test results).

## Acceptance Criteria
- Fragments no longer default to the `debug` vault unless the rules explicitly point there.
- `/routing` command UI reflects changes without regressions.
- All new/updated tests pass locally (`php artisan test` or `vendor/bin/pest`).
- No failures introduced in existing suites.
- Documentation reflects new behaviour and rule evaluation order.

## Notes & Risks
- Watch for race conditions if routing occurs before fragment relationships are loaded.
- Validate performance; eager load what’s needed to avoid N+1 rule lookups.
- Ensure migrations remain idempotent; no schema changes expected here.

