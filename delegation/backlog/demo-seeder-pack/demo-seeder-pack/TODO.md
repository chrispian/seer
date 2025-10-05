# Demo Seeder Pack Checklist

## Setup
- [ ] Confirm prerequisite seeders (DefaultVaultProjectSeeder, TypeSeeder, DemoRoutingDataSeeder)
- [ ] Document sample data requirements and volume targets
- [ ] Map entity relationships (users, vaults/projects, fragments, todos, contacts, chats/messages)
- [ ] Identify factories/helpers to extend for chronological data

## Seeder Design
- [ ] Define master `DemoDataSeeder` orchestration flow with sub-seeders
- [ ] Plan per-entity generators (users, vaults/projects, fragments, contacts, chats/messages, todos)
- [ ] Design chronological batching utilities (daily spread over ~90 days)
- [ ] Decide cleanup/idempotency approach (scoped truncation or unique keys)
- [ ] Determine configuration/command integration strategy

## Implementation
- [ ] Implement user seeding with deterministic login credentials
- [ ] Seed vaults/projects (>=2 each) with appropriate metadata/default flags
- [ ] Generate fragments to back todos and contacts, ensuring schema compliance
- [ ] Create contacts (25) with diverse organizations/emails/phones
- [ ] Create chats (~10) each with ~5 messages, timestamps, provider metadata
- [ ] Seed todos (~100) across vaults/projects with varied statuses, priorities, due dates
- [ ] Ensure fragments/todos include tags and state arrays
- [ ] Add rerun safeguards (transaction, purge of existing demo dataset)

## Validation & Documentation
- [ ] Execute seeder and confirm counts per entity
- [ ] Spot-check chronological distribution and relationships via queries
- [ ] Record validation notes and sample queries
- [ ] Document run instructions (`php artisan db:seed --class=DemoDataSeeder`)
- [ ] Outline SQL export procedure for snapshot creation
- [ ] Capture follow-up tasks for additional data types if needed
