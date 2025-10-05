# Database Schema Enhancement Context

## Current State Analysis

### Existing Database Foundation
The project already has a solid foundation with work items and sprints:

**work_items table** (from migration 20251004151940):
```sql
- id (uuid, primary)
- type (string) -- epic|story|task|bug|spike|decision
- parent_id (uuid, nullable, indexed)
- assignee_type (string, nullable) -- agent|user  
- assignee_id (uuid, nullable, indexed)
- status (string, default 'backlog')
- priority (string, nullable)
- project_id (uuid, nullable, indexed)
- tags (json, nullable)
- state (json, nullable)
- metadata (json, nullable)
- timestamps
```

**sprints table** (from migration 20251004151941):
```sql
- id (uuid, primary)
- code (string, unique)
- starts_on (date, nullable)
- ends_on (date, nullable)
- meta (json, nullable)
- timestamps
```

### Delegation Folder Structure
Current file-based organization:
```
delegation/
├── sprint-43/ (and other sprint folders)
│   ├── UX-04-01-todo-management-modal/
│   │   ├── AGENT.md
│   │   ├── CONTEXT.md
│   │   ├── PLAN.md
│   │   └── TODO.md
│   └── SPRINT_SUMMARY.md
├── agents/
│   ├── active/
│   └── templates/
└── backlog/
```

## Requirements Analysis

### Agent Orchestration Needs
1. **Agent Profiles**: Store agent configurations, capabilities, assignments
2. **Task Assignment**: Bridge between work items and agents
3. **Status Tracking**: Enhanced status management with agent context
4. **Delegation History**: Track assignment changes and progress

### Schema Enhancement Goals
1. **Minimal Changes**: Leverage existing work_items table where possible
2. **Future-Proof**: Design for upcoming MCP and UI requirements
3. **Performance**: Proper indexing for queries and relationships
4. **Data Integrity**: Foreign keys and validation constraints

## Technical Context

### Existing Models
- `WorkItem` model exists with basic structure
- `Sprint` model exists with meta field for flexibility
- UUID-based primary keys throughout system
- JSON fields used for flexible metadata storage

### Laravel Migration Patterns
- Use proper Schema facade methods
- Include proper rollback functionality
- Add indexes for foreign keys and query performance
- Follow naming conventions for consistency

### Database Compatibility
- Primary: PostgreSQL with JSON support
- Secondary: SQLite for development/testing
- Must work with both database engines

## Integration Points

### With Existing Systems
- Work items already support assignee_type/assignee_id pattern
- Sprints have flexible meta field for orchestration data
- Projects table exists for organization
- Users table for human assignees

### With Future Sprints
- Sprint 63: CLI commands will query these tables
- Sprint 64: MCP server will expose CRUD operations
- Sprint 66: UI dashboard will display relationships

## Data Migration Strategy

### From File to Database
1. Parse existing delegation folder structure
2. Create agent profiles from templates and active agents
3. Map sprint folders to sprint records
4. Create work items from task pack folders
5. Establish relationships and assignments

### Validation Requirements
- Ensure all foreign keys resolve correctly
- Validate JSON field structures
- Check for orphaned records
- Verify constraint compliance