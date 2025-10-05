# Demo Seeder Pack Checklist

## Setup
- [ ] Confirm prerequisite seeders (DefaultVaultProjectSeeder, TypeSeeder, etc.)
- [ ] Document sample data requirements and volume targets
- [ ] Draft ER diagram notes for seeded relationships

## Seeder Design
- [ ] Define master `DemoDataSeeder` orchestration flow
- [ ] Plan per-entity generators (users, vaults, projects, contacts, chats/messages, todos)
- [ ] Decide on factories vs custom builders for state-heavy records
- [ ] Outline chronological batching logic for 3-month spread

## Implementation
- [ ] Implement user creation with default passwords/roles
- [ ] Seed vaults/projects (2 each) with consistent ownership
- [ ] Generate contacts (25) with diverse metadata
- [ ] Create chats (10) with ~5 messages each, distributed timestamps
- [ ] Seed todos (~100) linked to fragments/projects/tags
- [ ] Ensure messages and todos honor model casts and JSON schema
- [ ] Add safeguards for repeat execution (delete/truncate or uniqueness checks)

## Integration & Testing
- [ ] Register seeder within seeder pack/Artisan command
- [ ] Execute seeder and verify counts per entity
- [ ] Spot-check chronological distribution with queries
- [ ] Validate relationships (foreign keys, fragment links, metadata)
- [ ] Document run instructions and SQL export process
- [ ] Prepare follow-up task for SQL snapshot creation
