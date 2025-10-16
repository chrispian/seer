# Making Navigation Stack Fully Config-Driven

## Goal
Create ONE perfect navigation implementation. All other command types work via config alone—no code changes needed.

## Current State ❌

**Hardcoded logic in CommandResultModal.tsx:**
```typescript
// Lines 164-180: Type-specific prop names
if (componentName.includes('Sprint')) {
  props.sprints = result.data
} else if (componentName.includes('Task')) {
  props.tasks = result.data
}

// Lines 188-197: Type-specific drill-down commands
if (componentName.includes('Sprint')) {
  props.onItemSelect = (item) => executeDetailCommand(`/sprint-detail ${item.code}`)
} else if (componentName.includes('Task')) {
  props.onItemSelect = (item) => executeDetailCommand(`/task-detail ${item.task_code}`)
}

// Line 209: Type-specific child relationships
if (componentName.includes('Sprint')) {
  props.onTaskSelect = (item) => executeDetailCommand(`/task-detail ${item.task_code}`)
}
```

Every new type requires code changes! ❌

## Target State ✅

**Config-driven via command metadata:**
```json
{
  "command": "/sprints",
  "ui_modal_container": "SprintListModal",
  "navigation": {
    "data_prop": "sprints",
    "item_key": "code",
    "detail_command": "/sprint-detail",
    "children": [
      {
        "type": "task",
        "command": "/task-detail",
        "item_key": "task_code",
        "prop_name": "tasks"
      }
    ]
  }
}
```

**Generic code:**
```typescript
// Get navigation config from result.config.navigation
const navConfig = result.config.navigation
if (navConfig) {
  props[navConfig.data_prop] = result.data
  props.onItemSelect = (item) => 
    executeDetailCommand(`${navConfig.detail_command} ${item[navConfig.item_key]}`)
  
  // Handle children
  navConfig.children?.forEach(child => {
    props[`on${child.type}Select`] = (item) =>
      executeDetailCommand(`${child.command} ${item[child.item_key]}`)
  })
}
```

## Implementation Steps

### Step 1: Add Navigation Metadata to Commands Table

**Migration:**
```php
$table->json('navigation_config')->nullable();
```

**Seeds for existing commands:**
```php
// Sprints
'navigation_config' => [
    'data_prop' => 'sprints',
    'item_key' => 'code',
    'detail_command' => '/sprint-detail',
    'children' => [
        ['type' => 'Task', 'command' => '/task-detail', 'item_key' => 'task_code']
    ]
]

// Sprint Detail
'navigation_config' => [
    'data_prop' => 'sprint',
    'item_key' => 'code',
    'parent_command' => '/sprints',
    'children' => [
        ['type' => 'Task', 'command' => '/task-detail', 'item_key' => 'task_code']
    ]
]

// Tasks
'navigation_config' => [
    'data_prop' => 'tasks',
    'item_key' => 'task_code',
    'detail_command' => '/task-detail'
]

// Agents
'navigation_config' => [
    'data_prop' => 'agents',
    'item_key' => 'slug',
    'detail_command' => '/agent-profile-detail'
]
```

### Step 2: Update BaseCommand to Include Navigation Config

**app/Commands/BaseCommand.php:**
```php
protected function getUIConfig(): array
{
    return [
        'modal_container' => $this->command->ui_modal_container,
        'base_renderer' => $this->command->ui_base_renderer,
        'navigation' => $this->command->navigation_config, // ← NEW
        // ... rest
    ];
}
```

### Step 3: Replace Hardcoded Logic with Config-Driven

**resources/js/islands/chat/CommandResultModal.tsx:**

Replace lines 162-181:
```typescript
function buildComponentProps(...) {
  const props = { isOpen, onClose, data, config }
  const navConfig = result.config?.ui?.navigation
  
  if (!navConfig) {
    // Fallback: use generic 'data' prop
    return props
  }
  
  // Data prop mapping from config
  if (navConfig.data_prop) {
    props[navConfig.data_prop] = result.data
  }
  
  // Drill-down to detail from config
  if (navConfig.detail_command && navConfig.item_key && handlers.executeDetailCommand) {
    props.onItemSelect = (item: any) => {
      const id = item[navConfig.item_key]
      handlers.executeDetailCommand!(`${navConfig.detail_command} ${id}`)
    }
    // Also set type-specific handler (onSprintSelect, onTaskSelect)
    const typeName = extractTypeName(navConfig.data_prop) // sprints → Sprint
    props[`on${typeName}Select`] = props.onItemSelect
  }
  
  // Child relationships from config
  navConfig.children?.forEach(child => {
    if (handlers.executeDetailCommand) {
      const handlerName = `on${child.type}Select`
      props[handlerName] = (item: any) => {
        const id = item[child.item_key]
        handlers.executeDetailCommand!(`${child.command} ${id}`)
      }
    }
  })
  
  // Detail-specific handling
  if (componentName.includes('Detail')) {
    if (result.data && typeof result.data === 'object') {
      Object.assign(props, result.data) // Spread data properties
    }
    if (handlers.onBackToList) {
      props.onBack = handlers.onBackToList
    }
  }
  
  return props
}
```

### Step 4: Test with Existing Commands

Verify all existing navigation works:
- [x] /sprints → /sprint-detail → /task-detail
- [ ] /tasks → /task-detail
- [ ] /agents → /agent-profile-detail
- [ ] /projects → /project-detail
- [ ] /vaults → /vault-detail
- [ ] /bookmarks → /bookmark-detail

### Step 5: Add New Command Type (Proof of Config-Only)

**Add Projects navigation WITHOUT touching CommandResultModal:**

1. Ensure commands table has:
   ```
   /projects: navigation_config = { data_prop: 'projects', item_key: 'id', detail_command: '/project-detail' }
   /project-detail: navigation_config = { data_prop: 'project', item_key: 'id' }
   ```

2. Components already exist (ProjectListModal, ProjectDetailModal)

3. Test: `/projects` → click project → detail modal ✅

4. If it works without code changes, **config-driven is proven** ✅

## Benefits

### Before (Hardcoded)
- Adding Agent navigation: Modify CommandResultModal.tsx (3 places)
- Adding Project navigation: Modify CommandResultModal.tsx (3 places)  
- Adding Vault navigation: Modify CommandResultModal.tsx (3 places)
- **N command types = 3N code changes**

### After (Config-Driven)
- Adding Agent navigation: Update database config
- Adding Project navigation: Update database config
- Adding Vault navigation: Update database config
- **N command types = N database updates, 0 code changes**

## Edge Cases to Handle

1. **Multi-level children**: Sprint → Task → Subtask
   - Config: `children: [{ children: [...] }]`
   - Code: Recursive child processing

2. **Composite keys**: Some items use multiple fields for ID
   - Config: `item_key: ['workspace_id', 'project_id']`
   - Code: Join with separator

3. **Custom handlers**: Some modals need special logic
   - Config: `custom_handlers: { onArchive: '/archive-sprint' }`
   - Code: Generic handler builder

4. **Different data structures**: List vs Detail vs Grid
   - Config: `view_mode: 'list' | 'detail' | 'grid'`
   - Code: Conditional prop building

## Success Criteria

✅ Sprint/Task navigation works (already working)
✅ Agent navigation works via config alone
✅ Project navigation works via config alone
✅ Adding new command type requires:
   - Database config update only
   - NO CommandResultModal.tsx changes
   - NO component changes (use existing DataManagementModal)

## Estimated Effort

- Step 1 (Migration): 15 minutes
- Step 2 (BaseCommand): 5 minutes
- Step 3 (Refactor logic): 45 minutes
- Step 4 (Testing): 30 minutes
- Step 5 (Proof): 15 minutes

**Total: ~2 hours**

## Files to Change

1. `database/migrations/YYYY_MM_DD_add_navigation_config_to_commands.php` (new)
2. `database/seeders/CommandSeeder.php` (update seeds)
3. `app/Commands/BaseCommand.php` (add navigation to getUIConfig)
4. `app/Models/Command.php` (add fillable/casts)
5. `resources/js/islands/chat/CommandResultModal.tsx` (replace hardcoded logic)

## Files NOT to Change

- Individual modal components (SprintListModal, TaskDetailModal, etc.)
- Command classes (SprintCommand, TaskCommand, etc.)
- Any type-specific code

This is the true test of a well-architected system! 🎯
