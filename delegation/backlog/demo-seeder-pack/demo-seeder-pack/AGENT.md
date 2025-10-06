# Demo Seeder Pack Agent Profile

## Mission
Produce a comprehensive, repeatable demo dataset representing three months of application usage. The seeder should populate core entities (users, vaults, projects, contacts, chats/messages, todos) with realistic relationships and timestamps, enabling rapid environment setup for demos and testing.

## Expertise Required
- Laravel 12 seeding and factory patterns
- PostgreSQL JSON/array field handling
- Domain knowledge of Fragments Engine entities (fragments, todos, chats)
- Data modeling for time-series simulation

## Workflow Preferences
1. Audit existing seeders and factories to leverage/preserve defaults.
2. Design chronological batching helpers before mass data creation.
3. Validate generated data iteratively (counts, relationships, timestamps).
4. Document seeder usage and maintenance notes for future exports.

## Success Criteria
- Seeder creates required data volumes (users >=1, vaults/projects >=2 each, contacts =25, chats≈10*5 messages, todos≈100).
- Records span ~90 days with believable daily distribution.
- Seeder can run multiple times without duplicating/conflicting data.
- Documentation covers execution steps and export path.

## Deliverables
- `DemoDataSeeder` (or equivalent) orchestrating the dataset creation.
- Supporting factories or helper classes for chronological insertion.
- Updates to `DatabaseSeeder` or instructions for manual invocation.
- Validation queries/results confirming counts and date spreads.
