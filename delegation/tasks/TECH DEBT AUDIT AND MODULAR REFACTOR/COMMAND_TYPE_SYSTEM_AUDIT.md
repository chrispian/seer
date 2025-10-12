# Command & Type System Architecture Audit
## Date: October 11, 2025
## Status: Initial Audit Complete

---

## Executive Summary

The Fragments Engine uses a database-driven configuration system for commands and types. The system is functional but has critical issues that need immediate attention:

1. **32 YAML commands remain** in the legacy system causing confusion
2. **Configuration errors** prevent some commands from working (e.g., `/tasks`)
3. **Missing commands** that worked in hardcoded system (`/help`, `/search`)
4. **No Actions system** for CRUD operations beyond basic list/detail views
5. **No Dashboard infrastructure** for monitoring system health

---

## Core Architecture

### Three Database Tables Drive Everything

1. **`commands` table** - Registry for all slash commands
   - Maps command strings to handler classes
   - Defines UI components and navigation config
   - Controls availability across interfaces (slash/mcp/cli)

2. **`types_registry` table** - Model-backed types (dedicated DB tables)
   - Sprint, Task, Agent, Project, Vault
   - Have Eloquent models and relationships
   - Full ORM capabilities

3. **`fragment_type_registry` table** - Fragment-backed types (JSON storage)
   - Note, Bookmark, Todo, User, Assistant, System
   - Schema-based validation
   - Flexible, schemaless storage

### Command Resolution Flow

```
User Input: /sprints
    ↓
CommandController::handleWebCommand()
    ↓
CommandRegistry::getPhpCommand('sprints')
    ↓
Load from Cache/DB: commands table
    ↓
Instantiate: App\Commands\Orchestration\Sprint\ListCommand
    ↓
Execute: ListCommand->handle()
    ↓
Return: BaseCommand->respond() with config
    ↓
Frontend: CommandResultModal renders SprintListModal
```

---

## Analysis: Working Example - `/sprints`

### Database Configuration
```json
{
  "command": "/sprints",
  "handler_class": "App\\Commands\\Orchestration\\Sprint\\ListCommand",
  "ui_modal_container": "SprintListModal",  // ✅ No .tsx extension
  "navigation_config": {
    "data_prop": "sprints",
    "item_key": "code",
    "detail_command": "/sprint-detail",
    "children": [{
      "type": "Task",
      "command": "/task-detail",
      "item_key": "task_code"
    }]
  }
}
```

### Why It Works
1. ✅ Component name matches COMPONENT_MAP exactly
2. ✅ Navigation config properly structured
3. ✅ Handler class exists and extends BaseCommand
4. ✅ Type configuration in types_registry is complete

---

## Analysis: Broken Example - `/tasks`

### Issue Found
```json
{
  "ui_modal_container": "TaskListModal.tsx",  // ❌ Has .tsx extension
  "ui_detail_component": "TaskDetailModal.tsx"  // ❌ Has .tsx extension
}
```

### Quick Fix
```sql
UPDATE commands 
SET ui_modal_container = 'TaskListModal',
    ui_detail_component = NULL
WHERE command = '/tasks';
```

---

## Analysis: Missing Commands - `/help` & `/search`

### Current State
- ✅ PHP handler classes exist (`HelpCommand.php`, `SearchCommand.php`)
- ❌ Not in `commands` table
- ❌ Not registered with CommandRegistry
- ✅ Return proper component names (`HelpModal`, `FragmentListModal`)

### Required Actions
1. Add entries to `commands` table
2. Register components in COMPONENT_MAP if missing
3. Create/verify frontend modal components exist

---

## Legacy YAML Commands (32 Remaining)

### Categories Still Using YAML
- **Orchestration (4)**: backlog-list, delegation-list, work-list, session-list
- **Content (8)**: bookmark, note, recall, remind, link, news-digest, summary, transcribe
- **Communication (6)**: accept, channels, inbox, join, reject, send
- **System (9)**: clear, frag, help, name, ping, search, setup, todo, restart
- **Development (5)**: schema, set-model, templates, test, test2

### Migration Priority
1. **P0 - Blocking**: help, search, todo (used frequently)
2. **P1 - Important**: accept, reject, channels, inbox (workflow critical)
3. **P2 - Nice to have**: Rest can be batch migrated

---

## Sprint Module Requirements

### Current State
✅ List view (`/sprints`)
✅ Detail view (`/sprint-detail`)
✅ Basic CRUD backend
❌ Create/Edit UI
❌ Actions system
❌ State transitions
❌ Bulk operations

### Complete CRUD Needs
1. **Create**: Modal form with validation
2. **Read**: ✅ Already working
3. **Update**: Edit form with field-level updates
4. **Delete**: Confirmation dialog with cascade handling

### Actions System Design
```typescript
interface Action {
  id: string
  label: string
  icon?: string
  type: 'single' | 'bulk' | 'global'
  handler: string  // Backend command
  confirmation?: boolean
  validator?: (item: any) => boolean
}

// Example Sprint Actions
const sprintActions: Action[] = [
  {
    id: 'activate',
    label: 'Activate Sprint',
    icon: 'play',
    type: 'single',
    handler: '/sprint-activate',
    validator: (sprint) => sprint.status === 'planned'
  },
  {
    id: 'delegate',
    label: 'Delegate Tasks',
    icon: 'users',
    type: 'single',
    handler: '/sprint-delegate'
  }
]
```

---

## Dashboard System Requirements

### Monitoring Needs
1. **Activity Dashboard**
   - Active sprints/tasks
   - Recent commands executed
   - User activity timeline

2. **Telemetry Dashboard**
   - Command usage statistics
   - Performance metrics
   - Error rates

3. **System Health**
   - Queue status (Horizon integration)
   - Cron job monitoring
   - Database stats

### Implementation Approach
```php
// Dashboard Command Pattern
class DashboardCommand extends BaseCommand {
    public function handle(): array {
        return [
            'component' => 'DashboardContainer',
            'data' => [
                'widgets' => $this->loadWidgets(),
                'layout' => $this->getLayout(),
                'refresh_interval' => 30000
            ]
        ];
    }
}
```

---

## Proposed System Names

Instead of "Command System" or "Type System", consider:

1. **Orchestration Control Panel (OCP)**
   - Emphasizes business process orchestration
   - Clear operational focus

2. **Business Process Framework (BPF)**
   - Highlights CRUD and workflow capabilities
   - Enterprise-friendly terminology

3. **Fragments Operations Center (FOC)**
   - Ties to core "Everything is a Fragment" philosophy
   - Operational monitoring focus

4. **Command & Control Interface (CCI)**
   - Military/operational precision
   - Clear authority structure

---

## Priority Action Plan

### Phase 1: Fix Immediate Issues (1-2 days)
1. ✅ Fix `/tasks` command (remove .tsx extensions)
2. Add `/help` and `/search` to commands table
3. Create missing frontend components
4. Document component registration process

### Phase 2: Remove YAML System (3-5 days)
1. Migrate P0 commands (help, search, todo)
2. Migrate P1 commands (accept, reject, channels, inbox)
3. Create batch migration for P2 commands
4. Remove YAML loader code
5. Clean up fragments/commands directory

### Phase 3: Complete Sprint Module (1 week)
1. Build Create/Edit forms
2. Implement Actions system
3. Add state transition commands
4. Create bulk operations
5. Add validation and error handling

### Phase 4: Dashboard Infrastructure (1 week)
1. Create dashboard framework
2. Build widget system
3. Implement activity monitoring
4. Add telemetry views
5. Integrate system health checks

---

## Technical Recommendations

### 1. Immediate Fixes
```sql
-- Fix /tasks command
UPDATE commands 
SET ui_modal_container = 'TaskListModal',
    ui_detail_component = NULL
WHERE command = '/tasks';

-- Add /help command
INSERT INTO commands (
    command, name, description, category, 
    handler_class, ui_modal_container,
    available_in_slash, available_in_mcp
) VALUES (
    '/help', 'Help System', 'Show available commands',
    'System', 'App\\Commands\\HelpCommand', 'HelpModal',
    true, true
);

-- Add /search command
INSERT INTO commands (
    command, name, description, category,
    handler_class, ui_modal_container,
    available_in_slash, available_in_mcp
) VALUES (
    '/search', 'Search', 'Search fragments and content',
    'Navigation', 'App\\Commands\\SearchCommand', 'FragmentListModal',
    true, true
);
```

### 2. Component Registration Pattern
```typescript
// Always register new components in CommandResultModal.tsx
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  'DataManagementModal': DataManagementModal,
  'SprintListModal': SprintListModal,
  'TaskListModal': TaskListModal,  // No .tsx extension!
  // ... add new components here
}
```

### 3. Migration Template
```php
// Template for migrating YAML to PHP command
class MigratedCommand extends BaseCommand {
    public function handle(): array {
        // 1. Port YAML logic
        // 2. Use BaseCommand->respond()
        // 3. Return proper component name
        return $this->respond([
            'data' => $this->getData(),
        ]);
    }
}
```

---

## Success Metrics

1. **Phase 1**: `/tasks` works, `/help` shows commands, `/search` returns results
2. **Phase 2**: Zero YAML commands remain, no regression errors
3. **Phase 3**: Full CRUD on sprints with 5+ working actions
4. **Phase 4**: 3+ working dashboards with real-time updates

---

## Risk Mitigation

1. **Backup before changes**: Database snapshots before migrations
2. **Feature flags**: Gradual rollout of new commands
3. **Parallel running**: Keep YAML system until PHP proven
4. **Comprehensive testing**: Each migration includes tests
5. **Documentation**: Update as changes are made

---

## Next Steps

1. Review and approve this audit
2. Execute Phase 1 fixes immediately
3. Begin YAML migration with P0 commands
4. Design detailed Actions system specification
5. Create dashboard wireframes

---

## Appendix: Component Checklist

### Working Components ✅
- SprintListModal
- TaskListModal (after fix)
- DataManagementModal
- UnifiedListModal
- FragmentListModal

### Missing Components ❌
- HelpModal (for /help command)
- SearchResultsModal (if different from FragmentListModal)
- CreateSprintModal
- EditSprintModal
- ActionConfirmationModal

### Deprecated Components ⚠️
- TodoManagementModal (being replaced)
- Legacy YAML renderers