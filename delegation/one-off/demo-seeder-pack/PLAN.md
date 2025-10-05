# Demo Seeder Pack Plan

## Phase 1: Discovery & Requirements (1-2 hours)
- Review existing seeders, factories, and model requirements
- Inventory required relationships (users, vaults, projects, chats, messages, todos, contacts)
- Capture data-volume targets and time-series distribution (3-month window)
- Identify dependent seeders (types, routing, defaults)

## Phase 2: Seeder Architecture Design (1-2 hours)
- Draft data generation strategy per entity with repeatability in mind
- Define reusable helper services/factories for chronological batching
- Plan idempotency safeguards (firstOrCreate patterns, truncation toggles)
- Outline configuration entry (e.g., `DatabaseSeeder` integration) and artisan hooks

## Phase 3: Implementation (3-4 hours)
- Scaffold primary DemoDataSeeder orchestrator
- Implement sub-seeders/factories for users, vaults, projects, contacts, chats, todos
- Generate chronological data (daily batches) with timestamps spread across 3 months
- Ensure relationships/events cascades (messages -> chats, todos -> fragments)

## Phase 4: Validation & Tooling (2 hours)
- Run seeder locally and verify counts/relationships
- Add assertions or logging for data volume targets
- Provide artisan command or README usage instructions
- Prepare SQL export instructions (pending final dataset approval)

## Risks & Mitigations
- **Model requirements**: cross-check fillable fields and casts; leverage factories
- **Idempotency**: wrap in transaction and clear scoped data set if re-run
- **Performance**: batch inserts where possible, avoid N+1 factory loops
- **Time investment**: incremental verification after each entity group
