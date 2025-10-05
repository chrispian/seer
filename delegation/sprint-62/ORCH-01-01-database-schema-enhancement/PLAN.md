# Database Schema Enhancement Implementation Plan

## Phase 1: Analysis and Design (30 minutes)
- [ ] Review existing work_items and sprints table structures
- [ ] Analyze delegation folder to understand data patterns
- [ ] Design agent_profiles table schema
- [ ] Plan foreign key relationships and constraints
- [ ] Design indexes for optimal query performance

## Phase 2: Create agent_profiles Migration (45 minutes)
- [ ] Create migration file for agent_profiles table
- [ ] Define all required fields with proper types
- [ ] Add foreign key constraints where appropriate
- [ ] Include proper indexes for performance
- [ ] Add rollback functionality

### agent_profiles Table Schema
```sql
agent_profiles:
- id (uuid, primary)
- name (string, not null)
- slug (string, unique, not null) -- for CLI/API reference
- type (string, not null) -- backend-engineer, frontend-engineer, etc.
- mode (string, not null) -- implementation, planning, review, etc.
- description (text, nullable)
- capabilities (json, nullable) -- list of skills/tools
- constraints (json, nullable) -- limitations and rules
- tools (json, nullable) -- available tools/integrations
- metadata (json, nullable) -- flexible additional data
- status (string, default 'active') -- active, inactive, archived
- created_at, updated_at
```

## Phase 3: Enhance work_items Table (30 minutes)
- [ ] Create migration to add orchestration fields to work_items
- [ ] Add agent assignment tracking fields
- [ ] Add delegation status fields
- [ ] Ensure backward compatibility with existing records

### Additional work_items Fields
```sql
-- Enhanced assignment tracking (existing fields)
assignee_type -- already exists: 'agent'|'user'
assignee_id   -- already exists: references agent_profiles.id or users.id

-- New orchestration fields
delegation_status (string, default 'unassigned') -- unassigned, assigned, in_progress, blocked, completed
delegation_context (json, nullable) -- assignment context and notes
delegation_history (json, nullable) -- track assignment changes
estimated_hours (decimal, nullable) -- task estimation
actual_hours (decimal, nullable) -- time tracking
```

## Phase 4: Create Supporting Tables (45 minutes)
- [ ] Create task_assignments table for detailed assignment tracking
- [ ] Create agent_capabilities table for skill management
- [ ] Add proper relationships and constraints

### task_assignments Table
```sql
task_assignments:
- id (uuid, primary)
- work_item_id (uuid, not null, foreign key)
- agent_id (uuid, not null, foreign key)
- assigned_by (uuid, nullable, foreign key to users)
- assigned_at (timestamp, not null)
- started_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- status (string, not null) -- assigned, started, paused, completed, cancelled
- notes (text, nullable)
- context (json, nullable) -- assignment-specific context
- created_at, updated_at
```

## Phase 5: Add Indexes and Constraints (30 minutes)
- [ ] Add foreign key constraints with proper cascading
- [ ] Create indexes for common query patterns
- [ ] Add unique constraints where appropriate
- [ ] Verify constraint integrity

### Key Indexes
```sql
-- Agent profiles
INDEX agent_profiles_type_status ON agent_profiles(type, status)
INDEX agent_profiles_slug ON agent_profiles(slug) -- unique constraint

-- Work items (existing + new)
INDEX work_items_assignee ON work_items(assignee_type, assignee_id)
INDEX work_items_delegation_status ON work_items(delegation_status)
INDEX work_items_project_status ON work_items(project_id, status)

-- Task assignments
INDEX task_assignments_work_item ON task_assignments(work_item_id)
INDEX task_assignments_agent ON task_assignments(agent_id)
INDEX task_assignments_status ON task_assignments(status)
INDEX task_assignments_assigned_at ON task_assignments(assigned_at)
```

## Phase 6: Update Model Relationships (30 minutes)
- [ ] Update WorkItem model with agent relationships
- [ ] Update Sprint model with orchestration methods
- [ ] Add proper Eloquent relationships
- [ ] Update model factories for testing

### Model Relationships
```php
// WorkItem model
public function assignedAgent(): BelongsTo
public function assignments(): HasMany
public function currentAssignment(): HasOne

// AgentProfile model  
public function assignedTasks(): HasManyThrough
public function assignments(): HasMany
public function activeAssignments(): HasMany
```

## Phase 7: Testing and Validation (30 minutes)
- [ ] Run migrations on fresh database
- [ ] Test rollback functionality
- [ ] Verify all constraints work correctly
- [ ] Test model relationships
- [ ] Performance test key queries

## Acceptance Criteria
- [ ] All migrations run successfully without errors
- [ ] Rollback migrations work correctly
- [ ] Foreign key constraints properly enforced
- [ ] Indexes improve query performance measurably
- [ ] Model relationships function correctly
- [ ] No breaking changes to existing functionality
- [ ] Both PostgreSQL and SQLite compatibility verified

## Risk Mitigation
- **Data Loss**: Test on development database first
- **Performance Impact**: Analyze query execution plans
- **Backward Compatibility**: Ensure existing code continues to work
- **Migration Failures**: Implement proper error handling and rollback

## Dependencies
- Existing work_items and sprints tables
- Laravel migration system
- Database connection configuration
- Model factory system for testing

## Deliverables
1. Migration file for agent_profiles table
2. Migration file for work_items enhancements  
3. Migration file for task_assignments table
4. Updated model relationships
5. Performance analysis report
6. Migration testing documentation