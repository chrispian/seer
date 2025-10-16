# Navigation System Complete Guide
# THE DEFINITIVE DOCUMENTATION

## Core Philosophy
**Build primitives and generics, then assemble them via configuration.**
No more `SprintListModal`, `TaskListModal`, etc. Just `DataManagementModal` + config.

---

## How It Actually Fucking Works (Step by Step)

### 1. User Types Command
```
User: /tasks
```

### 2. Backend Command Execution
**File**: `app/Commands/Orchestration/Task/ListCommand.php`

```php
class ListCommand extends BaseCommand 
{
    public function __invoke(array $params): array
    {
        $tasks = Task::query()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($task) => [
                'id' => $task->id,
                'task_code' => $task->code,
                'task_name' => $task->name,
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'assigned_to' => $task->assigned_to,
                'priority' => $task->priority,
                // etc...
            ]);

        return $this->respond([
            'tasks' => $tasks->toArray()
        ]);
    }
}
```

### 3. BaseCommand Adds Configuration
**File**: `app/Commands/BaseCommand.php`

The `respond()` method wraps the data with configuration:

```php
protected function webResponse(array $data, ?string $component): array
{
    return [
        'type' => $this->getResponseType(),        // 'task'
        'data' => $data,                           // ['tasks' => [...]]
        'config' => $this->getFullConfig(),        // SEE BELOW
    ];
}

public function getFullConfig(): array
{
    return [
        'type' => $this->getTypeConfig(),          // From types_registry table
        'type_slug' => $this->type?->slug,         // 'task'
        'ui' => $this->getUIConfig(),              // From commands table
        'command' => $this->commandConfig(),
    ];
}

protected function getUIConfig(): array 
{
    return [
        'modal_container' => $this->command->ui_modal_container,  // 'DataManagementModal'
        'navigation' => $this->command->navigation_config,        // THE MAGIC!
        // ...
    ];
}
```

### 4. Frontend Receives Response
**File**: `resources/js/islands/chat/ChatInterface.tsx`

```typescript
const response = {
    type: 'task',
    data: {
        tasks: [
            { task_code: 'T-001', task_name: 'Fix navigation', ... },
            { task_code: 'T-002', task_name: 'Add tests', ... }
        ]
    },
    config: {
        type_slug: 'task',
        ui: {
            modal_container: 'DataManagementModal',
            navigation: {
                data_prop: 'tasks',           // Which property has the array
                item_key: 'task_code',        // Primary key for items
                detail_command: '/task-detail', // Command to run on click
                children: []                  // Sub-navigation (if any)
            }
        }
    }
}
```

### 5. CommandResultModal Determines Component
**File**: `resources/js/islands/chat/CommandResultModal.tsx`

```typescript
function getComponentName(result: CommandResult): string {
    // Priority 1: Explicit modal container from commands table
    if (result.config?.ui?.modal_container) {
        return result.config.ui.modal_container  // 'DataManagementModal'
    }
    
    // Priority 2: Transform card component to modal
    if (result.config?.ui?.card_component) {
        return transformCardToModal(result.config.ui.card_component)
    }
    
    // Fallback
    return 'UnifiedListModal'
}
```

### 6. Build Props for Component
**File**: `resources/js/islands/chat/CommandResultModal.tsx` (lines 195-350)

```typescript
function buildComponentProps(result: CommandResult, componentName: string, handlers: ComponentHandlers): Record<string, any> {
    const props: Record<string, any> = {
        isOpen: true,
        onClose: handlers.onClose,
        data: result.data,
        config: result.config,
    }
    
    const navConfig = result.config?.ui?.navigation
    
    // CRITICAL: Extract the array from the data object
    if (navConfig?.data_prop) {
        const dataProp = navConfig.data_prop  // 'tasks'
        
        if (componentName === 'DataManagementModal') {
            // DataManagementModal expects 'data' to be an array
            props.data = result.data[dataProp]  // Extract tasks array
        } else {
            // Other components might expect the prop by name
            props[dataProp] = result.data[dataProp]
        }
    }
    
    // BUILD NAVIGATION HANDLER
    if (navConfig?.detail_command && navConfig?.item_key) {
        props.onItemSelect = (item: any) => {
            const itemKey = navConfig.item_key  // 'task_code'
            const itemId = item[itemKey]        // 'T-001'
            const command = `${navConfig.detail_command} ${itemId}`  // '/task-detail T-001'
            handlers.executeDetailCommand(command)
        }
    }
    
    // BUILD COLUMNS (if not provided)
    if (componentName === 'DataManagementModal') {
        // TODO: Move to config
        props.columns = props.columns || generateColumnsFromData(props.data)
        props.searchFields = props.searchFields || ['name', 'title', 'code']
        props.clickableRows = true
        props.onRowClick = props.onItemSelect  // Connect row clicks to navigation
    }
    
    return props
}
```

### 7. Render the Component
```typescript
const Component = COMPONENT_MAP[componentName]  // DataManagementModal
return <Component {...props} />
```

### 8. DataManagementModal Renders
**File**: `resources/js/components/ui/DataManagementModal.tsx`

```typescript
export function DataManagementModal<T>({
    data,              // Array of tasks
    columns,           // Column definitions
    onRowClick,        // Navigation handler
    clickableRows,     // true
    ...
}) {
    // Renders table with clickable rows
    // When row clicked, calls onRowClick(item)
    // Which triggers the navigation command
}
```

### 9. User Clicks a Row
```
Click on task T-001
→ onRowClick({ task_code: 'T-001', task_name: 'Fix navigation', ... })
→ onItemSelect(item) 
→ executeDetailCommand('/task-detail T-001')
→ Backend receives new command
→ Process repeats for detail view
```

---

## The Database Configuration

### commands table
```sql
SELECT * FROM commands WHERE command = '/tasks';
```

| Field | Value |
|-------|-------|
| command | /tasks |
| handler_class | App\Commands\Orchestration\Task\ListCommand |
| ui_modal_container | DataManagementModal |
| navigation_config | {"data_prop": "tasks", "item_key": "task_code", "detail_command": "/task-detail"} |

### types_registry table
```sql
SELECT * FROM types_registry WHERE slug = 'task';
```

| Field | Value |
|-------|-------|
| slug | task |
| display_name | Task |
| model_class | App\Models\Task |
| storage_type | model |

---

## Why SprintListModal Still Exists (TEMPORARY)

1. **Unassigned Tasks Virtual Sprint** - Should be removed per your direction
2. **Custom Column Rendering** - Should move to config
3. **Dual Prop Support** - Bridging legacy/new during migration

**Migration Path**:
```typescript
// Current (BAD - hardcoded wrapper)
SprintListModal → DataManagementModal

// Future (GOOD - direct with config)
DataManagementModal (with column/filter config)
```

---

## Configuration Schema

### Full navigation_config Structure
```json
{
    "data_prop": "string",        // Which property contains the array
    "item_key": "string",         // Primary key field name
    "detail_command": "string",   // Command to execute on item click
    "parent_command": "string",   // For back navigation (optional)
    "children": [                 // Nested navigation (optional)
        {
            "type": "string",     // Child type name
            "command": "string",  // Command to execute
            "item_key": "string"  // Field to pass as parameter
        }
    ],
    "columns": [                  // FUTURE: Column definitions
        {
            "key": "string",
            "label": "string",
            "render": "component|function"
        }
    ],
    "filters": [                  // FUTURE: Filter definitions
        {
            "key": "string",
            "type": "select|range|search",
            "options": []
        }
    ],
    "actions": [                  // FUTURE: Action buttons
        {
            "key": "string",
            "label": "string",
            "command": "string",
            "confirm": true
        }
    ]
}
```

---

## Common Fuck-Ups and How to Avoid Them

### 1. Component Not Found
**Error**: `Component 'DataManagementModal' not found in COMPONENT_MAP`
**Fix**: Register in `CommandResultModal.tsx`:
```typescript
const COMPONENT_MAP = {
    'DataManagementModal': DataManagementModal,
    // ...
}
```

### 2. Data is Not an Array
**Error**: `TypeError: data.filter is not a function`
**Cause**: DataManagementModal received object instead of array
**Fix**: Ensure `navigation_config.data_prop` is set correctly

### 3. Navigation Doesn't Work
**Cause**: Missing `onItemSelect` handler
**Fix**: Ensure `navigation_config` has both `detail_command` and `item_key`

### 4. Cache Issues
**Symptom**: Changes don't appear
**Fix**: 
```bash
php artisan cache:clear
```

---

## Adding a New List Command (Step by Step)

### Example: Adding `/projects` Command

#### 1. Create the Command Class
```php
// app/Commands/Project/ListCommand.php
class ListCommand extends BaseCommand {
    public function __invoke(array $params): array {
        $projects = Project::all()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'status' => $p->status,
        ]);
        
        return $this->respond(['projects' => $projects->toArray()]);
    }
}
```

#### 2. Add to CommandsSeeder
```php
[
    'command' => '/projects',
    'handler_class' => 'App\Commands\Project\ListCommand',
    'ui_modal_container' => 'DataManagementModal',
    'navigation_config' => [
        'data_prop' => 'projects',
        'item_key' => 'id',
        'detail_command' => '/project-detail',
    ],
],
```

#### 3. Run Seeder and Clear Cache
```bash
php artisan db:seed --class=CommandsSeeder
php artisan cache:clear
```

#### 4. Test
```
/projects
```

**That's it! No frontend changes needed.**

---

## CRUD Actions (Future)

### Configuration for Actions
```json
{
    "navigation_config": {
        "actions": [
            {
                "key": "edit",
                "label": "Edit Task",
                "command": "/task-edit",
                "icon": "edit"
            },
            {
                "key": "delete",
                "label": "Delete Task",
                "command": "/task-delete",
                "confirm": "Are you sure?",
                "icon": "trash",
                "variant": "destructive"
            },
            {
                "key": "assign",
                "label": "Assign Agent",
                "command": "/task-assign",
                "icon": "user-plus"
            }
        ]
    }
}
```

### Handler in DataManagementModal
```typescript
// Already exists!
onAction={(action, item) => {
    if (action === 'delete' && confirm('Are you sure?')) {
        executeCommand(`/task-delete ${item.task_code}`)
    }
}}
```

---

## Summary

**The System IS Config-Driven**. The flow is:
1. Backend returns data + config
2. Config specifies component and navigation
3. Frontend extracts array using `data_prop`
4. Builds handlers from `detail_command` + `item_key`
5. User clicks trigger navigation commands

**No more hardcoded wrappers**. Just configuration.

**SprintListModal should be deprecated** once we move its features to config.