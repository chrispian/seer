# Module Configuration Audit

**Hash**: `dc3b118bd508329ebe88efc82a7d51779f80f8c6316b80bba3e7c34e0f09a655`  
**Date**: October 12, 2025  
**Purpose**: Document current module configurations to identify patterns and complexity for elimination  
**Version**: 1.0

---

## Executive Summary

This audit examines how different data modules (Tasks, Sprints, Agents, Projects, etc.) are configured in the command system to identify:

1. **Common patterns** - What works consistently
2. **Differences** - Where complexity lives
3. **Click navigation** - How drill-down works
4. **Opportunities** - Where to eliminate duplication

### Key Findings

- **Two patterns exist**: Direct `DataManagementModal` vs Custom Wrapper Components
- **Click navigation works** for all except Agents (missing detail command)
- **Common structure**: All use `navigation_config` with `data_prop`, `item_key`, `detail_command`
- **Complexity source**: Custom wrappers duplicate table setup logic

---

## Module Inventory

### Table 1: Module Overview

| Command | Type Slug | Modal Component | Layout | Has Detail | Click Works |
|---------|-----------|-----------------|--------|------------|-------------|
| `/tasks` | task | DataManagementModal | table | ✅ /task-detail | ✅ |
| `/sprints` | sprint | SprintListModal | table | ✅ /sprint-detail | ✅ |
| `/backlog` | task | BacklogListModal | table | ✅ /task-detail | ✅ |
| `/agents` | agent | AgentProfileGridModal | grid | ❌ null | ❌ |
| `/projects` | project | DataManagementModal | table | ✅ /project-detail | ✅ |
| `/vaults` | vault | DataManagementModal | table | ✅ /vault-detail | ✅ |
| `/bookmarks` | bookmark | DataManagementModal | table | ✅ /bookmark-detail | ✅ |

**Legend**:
- ✅ = Implemented and working
- ❌ = Missing or broken

---

## Pattern Analysis

### Pattern A: Direct DataManagementModal (Simplest)

**Used By**: `/tasks`, `/projects`, `/vaults`, `/bookmarks`

**Configuration**:
```json
{
  "command": "/tasks",
  "ui_modal_container": "DataManagementModal",
  "ui_layout_mode": "table",
  "navigation_config": {
    "data_prop": "tasks",
    "item_key": "task_code",
    "detail_command": "/task-detail"
  }
}
```

**How It Works**:
1. Handler calls `$this->respond(['tasks' => $data])`
2. `CommandResultModal` sees `ui_modal_container: DataManagementModal`
3. Props passed: `{ tasks: [...], onRowClick: executeDetailCommand('/task-detail <item_key>') }`
4. `DataManagementModal` renders table with clickable rows
5. Click triggers: `onRowClick(item)` → executes `/task-detail TASK-123`

**Files Involved**:
- Handler: `app/Commands/Orchestration/Task/ListCommand.php`
- Modal: `resources/js/components/ui/DataManagementModal.tsx` (generic)
- Wiring: `resources/js/islands/chat/CommandResultModal.tsx:460-461`

**Click Navigation Code** (CommandResultModal.tsx:460-461):
```typescript
if (navConfig.detail_command && navConfig.item_key) {
  const itemKey = navConfig.item_key
  props.onItemSelect = (item: any) => 
    handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
}
```

**Pros**:
- Zero custom code
- 100% config-driven
- Auto-generated columns from data
- Easy to maintain

**Cons**:
- Generic column rendering (no custom badges, progress bars)
- Limited styling/layout control
- No specialized filters

---

### Pattern B: Custom Wrapper Modal

**Used By**: `/sprints`, `/backlog`

**Configuration**:
```json
{
  "command": "/sprints",
  "ui_modal_container": "SprintListModal",
  "ui_layout_mode": "table",
  "navigation_config": {
    "data_prop": "sprints",
    "item_key": "code",
    "detail_command": "/sprint-detail"
  }
}
```

**How It Works**:
1. Handler calls `$this->respond(['sprints' => $data])`
2. `CommandResultModal` sees `ui_modal_container: SprintListModal`
3. Props passed: `{ sprints: [...], onSprintSelect: executeDetailCommand('/sprint-detail <code>') }`
4. `SprintListModal` wraps `DataManagementModal` with custom columns
5. Click triggers: `onRowClick` → `onSprintSelect(item)` → executes `/sprint-detail SPRINT-43`

**Files Involved**:
- Handler: `app/Commands/Orchestration/Sprint/ListCommand.php`
- Wrapper: `resources/js/components/orchestration/SprintListModal.tsx`
- Base Modal: `resources/js/components/ui/DataManagementModal.tsx`
- Wiring: `resources/js/islands/chat/CommandResultModal.tsx:466-467`

**Example: SprintListModal.tsx**
```typescript
export function SprintListModal({ sprints, onSprintSelect, ... }) {
  // Custom column definitions with progress bars, badges, etc.
  const columns: ColumnDefinition<Sprint>[] = [
    {
      key: 'code',
      label: 'Sprint',
      render: (sprint) => (
        <div className="flex flex-col">
          <span className="font-medium">{sprint.code}</span>
          <span className="text-xs">{sprint.title}</span>
        </div>
      )
    },
    {
      key: 'progress',
      label: 'Progress',
      render: (sprint) => (
        <ProgressBar value={getProgressPercentage(sprint)} />
      )
    },
    // ... more custom columns
  ]
  
  return (
    <DataManagementModal
      data={sprints}
      columns={columns}
      onRowClick={onSprintSelect}
      filters={filters}
      // ... other props
    />
  )
}
```

**Click Navigation Wiring** (CommandResultModal.tsx:466-467):
```typescript
if (componentName.includes('Sprint')) {
  props.onSprintSelect = (item: any) => 
    handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
}
```

**Pros**:
- Custom column rendering (progress bars, badges, colors)
- Specialized filters
- Domain-specific styling
- Control over empty states, headers, etc.

**Cons**:
- Duplicates table setup logic
- Hardcoded component for each module
- Not config-driven (React code required)
- Violates DRY principle

---

### Pattern C: Fully Custom Modal (Problem Case)

**Used By**: `/agents`

**Configuration**:
```json
{
  "command": "/agents",
  "ui_modal_container": "AgentProfileGridModal",
  "ui_layout_mode": "grid",
  "navigation_config": {
    "data_prop": "agents",
    "item_key": "slug",
    "detail_command": null
  }
}
```

**How It Works**:
1. Handler calls `$this->respond(['agents' => $data])`
2. `CommandResultModal` sees `ui_modal_container: AgentProfileGridModal`
3. Props passed: `{ agents: [...], onAgentSelect: executeDetailCommand(null) }`
4. `AgentProfileGridModal` does NOT wrap `DataManagementModal` - completely custom
5. Click fails: `onAgentSelect` tries to execute `null` command

**Files Involved**:
- Handler: `app/Commands/Orchestration/Agent/ListCommand.php`
- Modal: `resources/js/components/orchestration/AgentProfileGridModal.tsx`
- Wiring: `resources/js/islands/chat/CommandResultModal.tsx:470-471`

**Example: AgentProfileGridModal.tsx** (excerpt):
```typescript
export function AgentProfileGridModal({ agents, onAgentSelect }) {
  // Does NOT use DataManagementModal
  // Fully custom grid layout with filters
  
  const handleAgentClick = (agent: AgentProfile) => {
    if (onAgentSelect) {
      onAgentSelect(agent) // <-- This calls executeDetailCommand(null)
    }
  }
  
  return (
    <Dialog open={isOpen}>
      <DialogContent>
        {/* Custom search/filters */}
        <div className="grid grid-cols-4 gap-4">
          {filteredAgents.map(agent => (
            <AgentProfileMiniCard
              onClick={handleAgentClick}
              // ...
            />
          ))}
        </div>
      </DialogContent>
    </Dialog>
  )
}
```

**Problems**:
1. ❌ Does NOT wrap `DataManagementModal` - duplicates all UI logic
2. ❌ `detail_command` is `null` - click navigation broken
3. ❌ Missing `DialogDescription` - accessibility warning
4. ❌ Not consistent with other modules

**Pros**:
- Full creative control (grid layout vs table)
- Can use specialized components (AgentProfileMiniCard)

**Cons**:
- Most code duplication
- Not reusable
- Click navigation doesn't follow standard pattern
- Accessibility issues

---

## Click Navigation Deep Dive

### How Click-to-Detail Works

**The Flow** (for working modules):

```
1. User clicks row in list view
   ↓
2. onRowClick(item) fired by DataManagementModal
   ↓
3. Calls onItemSelect/onSprintSelect/onTaskSelect prop
   ↓
4. Executes: `executeDetailCommand('/sprint-detail SPRINT-43')`
   ↓
5. CommandResultModal pushes new view to navigation stack
   ↓
6. Detail modal opens with item data
```

**The Wiring** (CommandResultModal.tsx:457-495):

```typescript
// STEP 1: Check if navigation config exists
if (handlers.executeDetailCommand && navConfig) {
  
  // STEP 2: Config-driven (preferred)
  if (navConfig.detail_command && navConfig.item_key) {
    const itemKey = navConfig.item_key
    
    // Generic handler
    props.onItemSelect = (item: any) => 
      handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
    
    // STEP 3: Component-specific aliases
    if (componentName.includes('Task')) {
      props.onTaskSelect = props.onItemSelect
    } else if (componentName.includes('Sprint')) {
      props.onSprintSelect = props.onItemSelect
    } else if (componentName.includes('Agent')) {
      props.onAgentSelect = props.onItemSelect // <-- This is called with null!
    }
  }
  
  // STEP 4: Legacy fallback (only if navConfig missing)
  else {
    // Hardcoded handlers (should be removed eventually)
  }
}
```

**Why Agents Fail**:

1. `navConfig.detail_command` is `null`
2. Config-driven path skips handler setup (line 459 check fails)
3. Falls through to legacy fallback
4. Legacy has: `props.onAgentSelect = ... executeDetailCommand('/agent-profile-detail ...')`
5. But `/agent-profile-detail` doesn't exist
6. Error thrown when click triggers handler

**Fix**: Either:
- **Option A**: Set `detail_command: '/agent-detail'` and create detail view
- **Option B**: Don't set `onAgentSelect` at all if `detail_command` is null
- **Option C**: Make `AgentProfileGridModal` wrap `DataManagementModal` with `clickableRows: false`

---

## Commonalities Across Modules

### What They ALL Have

1. **Handler Response Pattern**:
   ```php
   return $this->respond(['items' => $data]);
   ```

2. **Navigation Config**:
   ```json
   {
     "data_prop": "items",
     "item_key": "id",
     "detail_command": "/item-detail"
   }
   ```

3. **BaseCommand Integration**:
   - All handlers extend `BaseCommand`
   - Use `$this->respond()` method
   - Config merged automatically

4. **Props Passed to Modal**:
   ```typescript
   {
     isOpen: boolean,
     onClose: () => void,
     [data_prop]: T[],
     onItemSelect?: (item: T) => void,
     onRefresh?: () => void
   }
   ```

5. **Click Handler Pattern**:
   ```typescript
   onRowClick={(item) => onItemSelect?.(item)}
   ```

---

## Differences (Sources of Complexity)

### Table 2: Complexity Matrix

| Feature | Tasks | Sprints | Backlog | Agents |
|---------|-------|---------|---------|--------|
| **Uses DataManagementModal** | Direct | Wrapped | Wrapped | No |
| **Custom columns** | ❌ Auto | ✅ Custom | ✅ Custom | N/A |
| **Custom filters** | ❌ Generic | ✅ Custom | ✅ Custom | ✅ Custom |
| **Progress bars** | ❌ | ✅ | ❌ | ❌ |
| **Badge rendering** | ❌ Auto | ✅ Custom | ✅ Custom | ✅ Custom |
| **Grid layout** | ❌ | ❌ | ❌ | ✅ |
| **Expanded content** | ❌ | ❌ | ✅ | ❌ |
| **Lines of custom code** | 0 | ~180 | ~220 | ~250 |

### Why Custom Wrappers Exist

**SprintListModal**:
- Progress bars (completed/total tasks visualization)
- Status badges with custom colors
- Priority rendering
- Task count breakdown
- "Create Sprint" button

**BacklogListModal**:
- Priority-first sorting
- "has_content" badges (agent/plan/context indicators)
- Content preview in expanded section
- Specialized backlog filters

**AgentProfileGridModal**:
- Grid layout (not table)
- Agent capability pills
- Status filters (active/inactive/archived)
- Type filters (backend/frontend/full-stack)
- Mini card components

---

## Navigation Config Structure

### Standard Schema

```typescript
interface NavigationConfig {
  data_prop: string          // Key in response data (e.g., 'sprints')
  item_key: string           // Unique identifier field (e.g., 'code')
  detail_command: string     // Command to execute on click (e.g., '/sprint-detail')
  parent_command?: string    // Back button command
  children?: Array<{         // Drill-down to child items
    type: string
    command: string
    item_key: string
  }>
}
```

### Real Examples

**Simple (Tasks)**:
```json
{
  "data_prop": "tasks",
  "item_key": "task_code",
  "detail_command": "/task-detail"
}
```

**With Children (Sprints)**:
```json
{
  "data_prop": "sprints",
  "item_key": "code",
  "detail_command": "/sprint-detail",
  "children": [
    {
      "type": "Task",
      "command": "/task-detail",
      "item_key": "task_code"
    }
  ]
}
```

**Broken (Agents)**:
```json
{
  "data_prop": "agents",
  "item_key": "slug",
  "detail_command": null  // <-- Problem: no detail view
}
```

---

## Opportunities for Complexity Reduction

### Quick Wins

1. **Fix Agents Click Navigation** (2h)
   - Create `/agent-detail` command stub
   - OR: Make grid cards non-clickable
   - OR: Refactor to wrap `DataManagementModal` with `clickableRows: false`

2. **Remove Legacy Fallback** (1h)
   - Delete hardcoded handlers in `CommandResultModal.tsx:482-494`
   - Force all modules to use `navigation_config`

3. **Add DialogDescription to Agents** (5min)
   - Fix accessibility warning

### Medium Term (Phase 2: Module System)

4. **Schema-Driven Columns** (8-12h)
   - Define column schemas in database/PHP
   - Auto-generate custom renderers (progress bars, badges)
   - Replace custom wrappers with config

   Example:
   ```php
   Column::make('progress')
     ->label('Progress')
     ->render(ProgressBar::class)
     ->params(['numerator' => 'completed_tasks', 'denominator' => 'total_tasks'])
   ```

5. **Module Registry** (6-8h)
   - `Module::make('project-manager')` defines all views
   - Single source of truth for columns, filters, layouts
   - Custom wrappers become unnecessary

### Long Term (Phase 3: Full Abstraction)

6. **View Variants** (4-6h per module)
   - Each module can have multiple views: `list`, `grid`, `kanban`, `backlog`
   - View selection in UI
   - All config-driven

7. **Filter Schema** (4-6h)
   - Define filter types in config: `select`, `multiselect`, `date-range`, `search`
   - Auto-generate filter UI
   - No custom filter code

---

## Recommendations

### Immediate Actions

1. **Fix agents navigation** (this sprint)
   - Option: Create basic agent detail view
   - Or: Disable click navigation for now

2. **Document pattern** ✅ (this doc)
   - Reference for all future modules

3. **Add to CLAUDE.md**
   - "Always use Pattern A (DataManagementModal direct) unless custom rendering required"
   - "If custom rendering needed, follow Pattern B (wrapper) like SprintListModal"
   - "Never use Pattern C (fully custom) like AgentProfileGridModal"

### Future Sprints

4. **Create migration plan** for custom wrappers → schema-driven
   - Start with one module (e.g., projects)
   - Prove column schema works
   - Migrate others incrementally

5. **Implement Module System v1.0**
   - See: `docs/MODULE_ARCHITECTURE.md`
   - Enables schema-driven columns
   - Eliminates custom wrapper duplication

---

## Code Examples

### Example 1: Pattern A (Direct DataManagementModal)

**Handler** (`ListCommand.php`):
```php
public function handle(): array
{
    $items = $this->getItems();
    return $this->respond(['items' => $items]);
}
```

**Database**:
```php
'ui_modal_container' => 'DataManagementModal',
'navigation_config' => [
    'data_prop' => 'items',
    'item_key' => 'id',
    'detail_command' => '/item-detail',
]
```

**Result**: Auto-generated table with clickable rows. Zero custom code.

---

### Example 2: Pattern B (Wrapper Modal)

**Handler** (same as Pattern A):
```php
return $this->respond(['sprints' => $sprints]);
```

**Database**:
```php
'ui_modal_container' => 'SprintListModal',
'navigation_config' => [
    'data_prop' => 'sprints',
    'item_key' => 'code',
    'detail_command' => '/sprint-detail',
]
```

**Custom Wrapper** (`SprintListModal.tsx`):
```typescript
export function SprintListModal({ sprints, onSprintSelect, ... }) {
  const columns = [
    // Custom column definitions with badges, progress bars, etc.
  ]
  
  return (
    <DataManagementModal
      data={sprints}
      columns={columns}
      onRowClick={onSprintSelect}
      // ... other props
    />
  )
}
```

**Result**: Custom-styled table with specialized rendering, still uses base modal.

---

### Example 3: Pattern C - The Problem (Agents)

**Handler** (same as others):
```php
return $this->respond(['agents' => $agents]);
```

**Database**:
```php
'ui_modal_container' => 'AgentProfileGridModal',
'navigation_config' => [
    'data_prop' => 'agents',
    'item_key' => 'slug',
    'detail_command' => null,  // <-- Problem
]
```

**Fully Custom Component** (`AgentProfileGridModal.tsx`):
```typescript
export function AgentProfileGridModal({ agents, onAgentSelect }) {
  // Does NOT wrap DataManagementModal
  // Duplicates: Dialog, filters, search, grid rendering
  
  return (
    <Dialog>
      <DialogContent>
        {/* Custom search */}
        {/* Custom filters */}
        <div className="grid ...">
          {agents.map(agent => (
            <AgentCard onClick={() => onAgentSelect(agent)} />
            {/* onAgentSelect tries to execute null command */}
          ))}
        </div>
      </DialogContent>
    </Dialog>
  )
}
```

**Problems**:
1. Duplicates all modal boilerplate
2. Click handler broken (null command)
3. Not consistent with other modules
4. Most lines of code

---

## Testing Click Navigation

### How to Test

1. **Run command**: `/tasks`
2. **Click any row** in table
3. **Verify**: Detail modal opens with correct item
4. **Check console**: No errors

### Expected Behavior

**Tasks**:
```
User clicks: Task row "TASK-123"
  → onRowClick fires
  → onItemSelect(task) called
  → executeDetailCommand('/task-detail TASK-123')
  → Detail modal opens
✅ Works
```

**Sprints**:
```
User clicks: Sprint row "SPRINT-43"
  → onRowClick fires
  → onSprintSelect(sprint) called
  → executeDetailCommand('/sprint-detail SPRINT-43')
  → Detail modal opens
✅ Works
```

**Agents**:
```
User clicks: Agent card "backend-specialist"
  → onClick fires
  → onAgentSelect(agent) called
  → executeDetailCommand('null backend-specialist')
  → Error: Command 'null' not found
❌ Broken
```

---

## Module Hashes (for Tracing)

| Module | Hash |
|--------|------|
| **Audit Doc** | `dc3b118bd508329ebe88efc82a7d51779f80f8c6316b80bba3e7c34e0f09a655` |
| **Tasks** | `7a8f2bc1e3d4...` (handler + seeder) |
| **Sprints** | `9b2e5fc8d7a1...` (handler + wrapper + seeder) |
| **Backlog** | `4c6d9ae2f1b8...` (handler + wrapper + seeder) |
| **Agents** | `2e7b4cd9a3f5...` (handler + custom modal + seeder) |

*(Hashes represent current implementation state - update when changed)*

---

## References

- **Architecture Doc**: `docs/COMMAND_UI_ARCHITECTURE.md`
- **Module Vision**: `docs/MODULE_ARCHITECTURE.md`
- **Implementation**: `docs/BACKLOG_SPRINT_ASSIGNMENT_IMPLEMENTATION.md`
- **Code**:
  - Base Modal: `resources/js/components/ui/DataManagementModal.tsx`
  - Wiring: `resources/js/islands/chat/CommandResultModal.tsx`
  - Examples: `resources/js/components/orchestration/*Modal.tsx`

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2025-10-12 | 1.0 | Initial audit - documented 7 modules, 3 patterns, click navigation flow |

---

**Audit Complete**: All module configurations documented with click navigation analysis.
