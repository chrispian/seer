# Demo Seeder Pack Context

## Goal
Create a reusable demo/testing data seeder that simulates ~3 months of realistic Fragments Engine usage, enabling fast environment setups without manual data entry.

## Data Scope
- **Users**: At least 1 primary demo user (consider multiple roles if needed)
- **Vaults & Projects**: Minimum 2 vaults with 2 projects each
- **Chats & Messages**: ~10 chat sessions, ~5 messages each, with historical timestamps
- **Todos**: ~100 todos linked to fragments, distributed across vaults/projects/tags
- **Contacts**: Exactly 25 contacts with varied metadata
- **Fragments**: Generate fragments to back todos/contacts where the schema requires
- **Types & Routing**: Ensure supporting seeders (TypeSeeder, DemoRoutingDataSeeder) run beforehand

## Dependencies & References
- Review existing seeders (`DatabaseSeeder`, `DefaultVaultProjectSeeder`, `TypeSeeder`, `DemoRoutingDataSeeder`, `TestDataSeeder`)
- Inspect related models and factories (e.g., `Fragment`, `Todo`, `ChatSession`, `Contact`, `Vault`, `Project`)
- Align with date/JSON field expectations (casts, default states)
- Ensure compatibility with existing routing rules, type schemas, and inbox behaviors
- Confirm any observers/events triggered on creation (Fragments, ChatSessions)

## Constraints
- Seeder must be idempotent or safely re-runnable (clear scoped data set if re-run)
- Generated data should mirror daily activity to facilitate analytics/testing
- Avoid clashing with production migrations or environment-specific seeders
- Maintain compatibility with PostgreSQL-specific features (JSONB, timestamps)
- Keep execution time reasonable (batch inserts, factory usage)

## Deliverables
- New seeder pack orchestrator(s) under `database/seeders`
- Supporting factory states/helpers for chronological data where necessary
- Documentation on execution (`php artisan db:seed --class=DemoDataSeeder`) and cleanup
- Validation notes confirming counts and date spreads
- Guidance for eventual SQL dump creation once dataset validated
