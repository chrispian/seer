# Demo Seeder Pack Plan

## Phase 1: Discovery & Requirements (1-2 hours)
- Review existing seeders, factories, and model requirements
- Inventory required relationships (users, vaults, projects, fragments, chats, messages, todos, contacts)
- Capture data-volume targets and time-series distribution (3-month window)
- Identify dependent seeders (TypeSeeder, DemoRoutingDataSeeder, DefaultVaultProjectSeeder)

## Phase 2: Seeder Architecture Design (1-2 hours)
- Draft data generation strategy per entity with repeatability in mind
- Define reusable helper services/factories for chronological batching and message/todo generation
- Plan idempotency safeguards (scoped cleanup, firstOrCreate patterns, deterministic identifiers)
- Outline configuration entry (e.g., `DatabaseSeeder` integration, artisan command wrapper)

## Phase 3: Implementation (3-4 hours)
- Scaffold primary `DemoDataSeeder` orchestrator with sub-seeders per entity
- Implement user seeding and ensure password consistency for logins
- Seed vaults/projects (minimum two each) with ownership and default flags
- Generate fragments supporting contacts and todos with proper state/tags
- Create contacts (25) linked to fragments, with varied metadata
- Generate chats (~10) with ~5 messages each, including timestamps and providers
- Seed todos (~100) across projects/vaults with status/priority mixes and due dates
- Ensure chronological distribution across ~90 days for chats, todos, and fragments

## Phase 4: Validation & Tooling (2 hours)
- Run seeder locally and verify counts/relationships via queries or artisan output
- Add command output or logging summarizing entity counts
- Document execution, rerun strategy, and cleanup guidance
- Outline SQL export process to capture seeded dataset once approved

## Risks & Mitigations
- **Model requirements**: Cross-check fillable fields and casts; leverage factories
- **Idempotency**: Use transactions, scoped deletes, or deterministic lookup keys before seeding
- **Performance**: Batch operations where practical, avoid excessive per-record queries
- **Chronology accuracy**: Centralize date generation utilities to maintain consistent spread
