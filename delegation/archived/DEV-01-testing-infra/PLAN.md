# DEV-01 Testing Infrastructure — Implementation Plan

## Objective
Improve the testing foundation for Seer: streamline Pest configuration, add representative factories/seeders, and set up targeted CI scripts.

## Deliverables
- Optimised Pest/PHPUnit configuration (e.g., parallelisation, reduced bootstrap overhead).
- Expanded model factories/seeders covering key pipeline scenarios (routing rules, embeddings on/off, AI providers).
- Command or script (e.g., Composer or `just` task) to run unit, feature, and integration suites selectively.
- Documentation explaining test categories and how to run them locally/CI.

## Work Breakdown
1. **Branch Prep**
   - `git fetch origin` (report if blocked) and `git pull --rebase origin main`.
   - `git checkout -b feature/dev-01-testing-infra`.
2. **Current State Audit**
   - Review `phpunit.xml`, `Pest.php`, existing factories in `database/factories`, and any CI configs.
   - Identify slow tests or missing coverage areas.
3. **Pest/PHPUnit Config Enhancements**
   - Enable parallel testing if feasible (`artisan test --parallel`).
   - Configure higher-order expectations/macros as needed.
   - Ensure database refresh strategies fit Postgres + SQLite.
4. **Factories & Seeders**
   - Add/update factories for fragments, routing rules, chat sessions with realistic attributes.
   - Create seeder(s) for common dev scenarios (may guard by env).
5. **Test Suites & Scripts**
   - Define categories (unit, feature, integration) and align Pest test folders/naming.
   - Add Composer scripts or `just` tasks to run targeted suites (e.g., `composer test:feature`).
6. **CI Recommendations**
   - Draft GitHub Actions (or noted pipeline) snippet illustrating how suites should run (include caching suggestions).
   - Document coverage allowances (optional).
7. **Documentation**
   - Update `docs/` with testing guide covering setup, commands, environment configuration.
   - Check relevant TODOs in `PROJECT_PLAN.md`.
8. **Validation & Handoff**
   - Run full test suites locally; capture timing improvements.
   - Summarise changes, push branch, open PR.

## Acceptance Criteria
- Running targeted suites is straightforward via documented commands.
- Factories/seeders support ENG-02/ENG-03 scenarios without manual setup.
- Pest config improvements shave measurable time off typical runs (document before/after).
- Documentation clearly explains testing strategy.

## Risks & Notes
- Parallel testing may expose race conditions; be prepared to adjust database setup.
- Seeder additions should avoid polluting production; gate via env or optional command.
- Coordinate with TPM before adding heavy dependencies or large fixture datasets.
