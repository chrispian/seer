# DEV-01 Context

## Current Testing Landscape
- Pest is configured via `tests/Pest.php`, but suite organisation is limited (mostly Feature tests).
- Factories exist for fragments, projects, vaults, routing rules; more complex pipeline scenarios still require manual setup.
- CI pipeline not fully defined; need scripts for reproducible runs.

## Key Touchpoints
- `phpunit.xml`, `tests/Pest.php` — adjust bootstrap, caching, parallel settings.
- `database/factories/` — extend to cover embeddings enabled/disabled, model selection metadata once ENG-03 lands.
- `database/seeders` — add opt-in dev seeder.
- `composer.json` — add Composer scripts for targeted suites.
- Potential `Justfile` if we adopt `just` (align with tooling decisions from DEV-03 later).

## Goals Alignment
- Support other tracks (ENG-02 routing, ENG-03 model selection, UX-01 toasts) with reliable fixtures and quick feedback cycles.
- Prepare for future CI integration (GitHub Actions, etc.).

## Considerations
- Ensure tests compatible with both Postgres (primary) and SQLite (local/CI) by guarding features like pgvector.
- Document how to toggle embeddings on/off in tests (config helper, environment variables).
- Might need to stub AI provider calls; evaluate existing fakes/mocks.

## Definition of Done
- Clear instructions and scripts exist for running each suite.
- Factories/seeders support major scenarios without manual fiddling.
- Pest config improvements validated by timing data.
