# ADR-v2: UI Builder v2 API Contracts and Architecture
Status: Accepted
Date: 2025-10-15
Owner: BE-Kernel Agent
Related: feature/ui-builder-v2-agents-modal

## Context
The UI Builder v2 system requires a robust backend API to support dynamic page configuration delivery, data source resolution, and action execution for frontend components. The system must support:

- Hash-versioned configuration persistence to enable cache invalidation and change tracking
- Flexible data source resolvers with capability-based filtering, searching, and sorting
- Router-only action execution pattern (all actions through CommandController/CommandRegistry)
- Backward compatibility with existing Agent model and command infrastructure
- Isolation under `/v2` namespace to avoid conflicts with existing APIs

Constraints:
- No schema changes to existing `agents` table
- All actions must route through CommandController/CommandRegistry
- Must compute and store hash + version for all configs
- SQLite compatibility for testing (RefreshDatabase pattern)

## Decision

### Database Schema
Created four new tables to support UI Builder v2:

1. **fe_ui_pages** - Stores complete page configurations
   - `key` (unique): Page identifier (e.g., "page.agent.table.modal")
   - `config` (JSON): Complete page configuration
   - `hash` (indexed): SHA-256 hash of config for change detection
   - `version` (integer): Auto-incrementing version number
   
2. **fe_ui_components** - Stores reusable component configurations
   - `key` (unique): Component identifier
   - `type`: Component type (table, search.bar, etc.)
   - `config` (JSON): Component configuration
   - `hash` + `version`: Same versioning pattern as pages

3. **fe_ui_datasources** - Registry of data source resolvers
   - `alias` (unique): Human-readable data source name (e.g., "Agent")
   - `model_class`: Eloquent model class reference
   - `resolver_class`: Resolver implementation class
   - `capabilities` (JSON): Searchable/filterable/sortable field definitions

4. **fe_ui_actions** - Action handler registry (future use)
   - `type`: Action type (command, navigate)
   - `handler_class`: Handler implementation class
   - `config` (JSON): Optional configuration

### API Endpoints

**GET /api/v2/ui/pages/{key}**
Retrieves page configuration by key.

Response payload:
```json
{
  "id": 1,
  "key": "page.agent.table.modal",
  "config": { /* full page config */ },
  "hash": "ce5b04cb...",
  "version": 1,
  "timestamp": "2025-10-15T05:00:00Z"
}
```

**POST /api/v2/ui/datasource/{alias}/query**
Executes data source query with filters, search, sort, and pagination.

Request payload:
```json
{
  "search": "string",
  "filters": { "field": "value" },
  "sort": { "field": "name", "direction": "asc" },
  "pagination": { "page": 1, "per_page": 15 }
}
```

Response payload:
```json
{
  "data": [ /* array of transformed records */ ],
  "meta": {
    "total": 25,
    "page": 1,
    "per_page": 15,
    "last_page": 2
  },
  "hash": "a3f2cd..."
}
```

**POST /api/v2/ui/action**
Executes actions (command or navigate).

Request payload (command):
```json
{
  "type": "command",
  "command": "/orch-agent",
  "params": { "id": "uuid" }
}
```

Request payload (navigate):
```json
{
  "type": "navigate",
  "url": "/agents"
}
```

Response payload:
```json
{
  "success": true,
  "result": { /* command result or navigation payload */ },
  "hash": "b4e1fa..."
}
```

### Implementation Architecture

**Models with Auto-Hashing**
All config-storing models (FeUiPage, FeUiComponent) automatically compute SHA-256 hash on save and increment version on config changes using Eloquent events.

**DataSource Resolver Pattern**
Each data source implements a resolver class with:
- `query(array $params): array` - Execute query with filters/search/sort/pagination
- `getCapabilities(): array` - Return searchable/filterable/sortable field lists

**Action Adapter Pattern**
ActionAdapter delegates to existing CommandRegistry for command execution, maintaining the router-only constraint. Navigate actions return navigation payloads without side effects.

## Consequences

### Positive
- Clean separation of v2 API from existing system
- Hash-based change detection enables efficient caching and cache invalidation
- Version tracking provides audit trail and rollback capability
- DataSource pattern enables frontend-driven querying without custom endpoints
- Action adapter maintains existing command execution patterns
- No schema changes to existing tables preserves backward compatibility

### Negative / Trade-offs
- Additional database tables increase schema complexity
- Hash computation on every save adds minor overhead
- DataSource resolvers must be registered manually in seeder/migration
- Test environment requires RefreshDatabase to run migrations (SQLite in-memory)

### Security/Privacy/Performance
- All data source queries validated through resolver capabilities
- Command execution still subject to existing CommandRegistry authorization
- Hash indexes enable fast cache validation queries
- JSON columns in PostgreSQL provide efficient querying and storage

## Alternatives Considered

**Option A: File-based Configuration**
Store page configs as JSON files in resources/ directory. Rejected because:
- No version tracking or audit trail
- Harder to update programmatically
- No database-backed search/filtering

**Option B: Embed Config in Command Models**
Store page configs directly in existing `commands` table. Rejected because:
- Tight coupling between commands and UI
- Limited flexibility for standalone pages
- Harder to manage reusable components

**Option C: GraphQL for Data Sources**
Implement GraphQL resolver instead of REST endpoints. Rejected because:
- Higher complexity for MVP
- Would require GraphQL infrastructure setup
- REST pattern aligns with existing API conventions

## Implementation Notes

### Migration/Rollback Plan
Migrations are isolated to new tables only. Rollback can be performed without affecting existing system. Seeder populates initial Agent data source registration and sample page config.

### Test Strategy
Feature tests cover:
- Page config retrieval with hash/version validation
- DataSource query with search, filter, sort, and pagination
- Action execution for both command and navigate types
- Version increment and hash change on config updates

Note: Tests use RefreshDatabase trait with SQLite in-memory database.

### Observability
- All DataSource queries logged with resolver class, alias, and error details
- Action execution failures logged with command name, type, and stack trace
- Command execution delegates to existing CommandController logging

## Links
- Commit: 49873479e9aa3740d9a715f5eac65886363dc236
- Config Hash: 33f41ea8d94da564ab3feae41bf4121bb607a596db5b7f9be7d80af89374a2b0
- Spec: delegation/tasks/ui-builder/backend/PACK_A_Backend_Kernel.md
- Page Config: delegation/tasks/ui-builder/frontend/page.agent.table.modal.json
