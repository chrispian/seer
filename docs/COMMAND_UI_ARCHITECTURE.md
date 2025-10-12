# Command UI Architecture - Layer Breakdown

**Date**: October 12, 2025  
**Status**: Reference Guide  
**Audience**: Developers working on command system

---

## Architecture Layers

The command system has a clear hierarchy of configuration that determines how commands are displayed:

```
┌────────────────────────────────────────────────────┐
│  1. types_registry (DATA LAYER)                    │
│     - Defines data model and DEFAULT display       │
│     - Sets default_card_component                  │
│     - Storage type (model/fragment)                │
└────────────────────────────────────────────────────┘
                        ↓
┌────────────────────────────────────────────────────┐
│  2. commands table (CONTROLLER/ROUTER)             │
│     - HIGHEST PRIORITY - overrides everything      │
│     - Links command to handler and UI config       │
│     - Defines modal container, layout, navigation  │
└────────────────────────────────────────────────────┘
                        ↓
┌────────────────────────────────────────────────────┐
│  3. CommandResultModal (RENDERER)                  │
│     - Resolves component from config               │
│     - Builds props and handlers                    │
│     - Renders chosen component                     │
└────────────────────────────────────────────────────┘
                        ↓
┌────────────────────────────────────────────────────┐
│  4. UI Component (PRESENTATION)                    │
│     - Displays data with configured layout         │
│     - Handles user interactions                    │
└────────────────────────────────────────────────────┘
```

---

## Commands Table Configuration

### Critical Columns

#### 1. `ui_modal_container` (WHICH component)
The React component that wraps and displays the data.

**Examples:**
- `AgentProfileGridModal` - Grid of agent cards
- `SprintListModal` - Table of sprints with filters
- `BacklogListModal` - Table of backlog tasks
- `DataManagementModal` - Generic table/grid (fallback)

**Pattern:**
- **List commands** → Use custom wrapper modals (`SprintListModal`, `BacklogListModal`)
- **Detail commands** → Use detail modals (`TaskDetailModal`, `SprintDetailModal`)
- **Simple data** → Use generic `DataManagementModal`

#### 2. `ui_layout_mode` (HOW items are displayed)
Controls the layout INSIDE the modal container.

**Options:**
- `grid` - Cards in a grid (e.g., agent profiles)
- `table` - Rows in a table (e.g., tasks, sprints)
- `list` - Vertical list (e.g., simple lists)
- `kanban` - Column-based board (future)
- `calendar` - Calendar view (future)

**Key Insight:** The same component can support multiple layouts!

#### 3. `ui_card_component` (WHAT to show for each item)
The component used to render individual items.

**Examples:**
- `AgentProfileCard` - For agent grid items
- `TaskCard` - For task list items
- `SprintCard` - For sprint list items

**Note:** Often implicit in the modal container implementation.

#### 4. `ui_base_renderer` (UNDERLYING engine)
The generic component that powers the modal.

**Examples:**
- `DataManagementModal` - Powers most list views
- `Dialog` - Powers simple modals

**Key Insight:** Custom modals WRAP the base renderer with specialized setup!

#### 5. `navigation_config` (HOW to navigate)
Defines data routing and detail navigation.

**Required Fields:**
```json
{
  "data_prop": "agents",         // Which property has the array data
  "item_key": "slug",            // Field to use as item identifier
  "detail_command": "/agent-detail"  // Command for detail view
}
```

---

## Example Configurations

### Grid Layout (Agents)

```sql
command: '/agents'
ui_modal_container: 'AgentProfileGridModal'  -- Custom grid wrapper
ui_layout_mode: 'grid'                       -- Show as card grid
ui_base_renderer: 'DataManagementModal'      -- Generic underneath
navigation_config: {
  "data_prop": "agents",
  "item_key": "slug",
  "detail_command": "/agent-detail"
}
```

**Result:** Grid of agent cards with search/filters

### Table Layout (Sprints)

```sql
command: '/sprints'
ui_modal_container: 'SprintListModal'        -- Custom table wrapper
ui_layout_mode: 'table'                      -- Show as table rows
ui_base_renderer: 'DataManagementModal'      -- Generic underneath
navigation_config: {
  "data_prop": "sprints",
  "item_key": "code",
  "detail_command": "/sprint-detail"
}
```

**Result:** Table of sprints with progress bars, filters, chips

### Table Layout (Backlog)

```sql
command: '/backlog'
ui_modal_container: 'BacklogListModal'       -- Custom table wrapper
ui_layout_mode: 'table'                      -- Show as table rows
ui_base_renderer: 'DataManagementModal'      -- Generic underneath
navigation_config: {
  "data_prop": "tasks",
  "item_key": "task_code",
  "detail_command": "/task-detail"
}
```

**Result:** Table of backlog tasks with priority sorting, content badges

---

## Config Priority

When determining which component to use:

1. **commands.ui_modal_container** (HIGHEST)
2. commands.ui_card_component (transform to modal)
3. types_registry.default_card_component (transform)
4. result.component (legacy override - AVOID)
5. 'UnifiedListModal' (fallback)

**Rule:** Database config ALWAYS wins. Never hardcode `component` in handlers!

---

## Component Patterns

### Pattern 1: Custom Wrapper Modal (Recommended)

**When to use:** Commands with specific UI needs (filters, custom columns, actions)

**Example:** `SprintListModal`, `BacklogListModal`, `AgentProfileGridModal`

**Structure:**
```tsx
export function SprintListModal({ sprints, onRefresh, onSprintSelect }) {
  // Sprint-specific setup
  const columns = [...] // Custom columns
  const filters = [...]  // Custom filters
  
  return (
    <DataManagementModal
      data={sprints}
      columns={columns}
      filters={filters}
      // ... sprint-specific props
    />
  )
}
```

**Advantages:**
- Full control over UI
- Custom filters, chips, actions
- Type-specific features

### Pattern 2: Generic DataManagementModal

**When to use:** Simple lists without custom requirements

**Example:** Simple bookmarks, basic entity lists

**Structure:**
```sql
ui_modal_container: 'DataManagementModal'
```

**Advantages:**
- Zero code needed
- Auto-generated columns
- Consistent UX

---

## Handler Response Pattern

### Standard Response (Correct ✅)

```php
public function handle(): array
{
    $data = $this->getData();
    
    return $this->respond([
        'agents' => $data,  // Match navigation_config.data_prop
    ]);
}
```

### Legacy Override (AVOID ❌)

```php
public function handle(): array
{
    return [
        'component' => 'AgentProfileGridModal',  // DON'T DO THIS
        'data' => $data,
    ];
}
```

**Why avoid:** Bypasses database config, makes system unpredictable.

---

## Adding a New Command

### Step 1: Create Handler

```php
class MyListCommand extends BaseCommand
{
    public function handle(): array
    {
        $items = $this->getItems();
        
        return $this->respond([
            'items' => $items,  // Match data_prop below
        ]);
    }
}
```

### Step 2: Add to CommandsSeeder

```php
[
    'command' => '/my-items',
    'handler_class' => 'App\\Commands\\MyListCommand',
    'ui_modal_container' => 'MyItemsListModal',  // or DataManagementModal
    'ui_layout_mode' => 'table',  // or 'grid', 'list'
    'navigation_config' => [
        'data_prop' => 'items',
        'item_key' => 'id',
        'detail_command' => '/my-item-detail',
    ],
]
```

### Step 3: Create Modal (if custom)

```tsx
export function MyItemsListModal({ items, onRefresh, onItemSelect }) {
  const columns = [...]
  const filters = [...]
  
  return (
    <DataManagementModal
      data={items}
      columns={columns}
      filters={filters}
      onRowClick={onItemSelect}
    />
  )
}
```

### Step 4: Register in COMPONENT_MAP

```tsx
const COMPONENT_MAP = {
  'MyItemsListModal': MyItemsListModal,
  // ... other components
}
```

---

## Common Mistakes

### ❌ Mistake 1: Hardcoding component in handler

```php
return ['component' => 'MyModal', 'data' => $data];  // WRONG
```

**Fix:** Use `respond()` and set `ui_modal_container` in database.

### ❌ Mistake 2: Wrong data_prop name

```php
// Handler returns: { 'agents': [...] }
// Config says: { "data_prop": "agent_profiles" }  // WRONG - mismatch
```

**Fix:** Match handler response key to `navigation_config.data_prop`.

### ❌ Mistake 3: Confusing modal_container with layout_mode

```sql
ui_modal_container: 'grid'  -- WRONG (not a component)
ui_layout_mode: 'AgentProfileGridModal'  -- WRONG (not a layout)
```

**Fix:**
- `ui_modal_container` = Component name
- `ui_layout_mode` = Layout type (grid/table/list)

### ❌ Mistake 4: Not clearing cache after changes

**Symptom:** Database changes don't take effect

**Fix:** Run `php artisan cache:clear` or `CommandRegistry::clearCache()`

---

## Migration Checklist

When standardizing an old command:

- [ ] Remove `component` key from handler
- [ ] Use `$this->respond(['key' => $data])`
- [ ] Set `ui_modal_container` in commands table
- [ ] Set `ui_layout_mode` (grid/table/list)
- [ ] Add `navigation_config` with `data_prop`
- [ ] Update CommandsSeeder
- [ ] Clear cache
- [ ] Test command in browser

---

## Related Documentation

- `docs/SPRINT_COMPONENT_GUIDE.md` - Sprint/Task component details
- `docs/MODULE_ARCHITECTURE.md` - Future module system design
- `docs/type-command-ui/` - Original refactor planning
- `docs/BACKLOG_SPRINT_ASSIGNMENT_IMPLEMENTATION.md` - Recent implementation

---

**Key Takeaway:** The command system is fully config-driven through the database. Custom modals wrap generic components with specialized features. Never hardcode component names in handlers!
