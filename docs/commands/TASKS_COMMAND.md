# /tasks Command Reference

## Overview
The `/tasks` command displays all tasks with status, assignment info, and navigation to task details.

## Database Configuration

### commands table entry
```json
{
  "id": 4,
  "command": "/tasks",
  "name": "List Tasks",
  "description": "Display all tasks with status and assignment info",
  "category": "Orchestration",
  "type_slug": "task",
  "handler_class": "App\\Commands\\Orchestration\\Task\\ListCommand",
  "available_in_slash": true,
  "available_in_cli": false,
  "available_in_mcp": true,
  "ui_modal_container": "TaskListModal",
  "ui_layout_mode": "table",
  "ui_base_renderer": "DataManagementModal",
  "navigation_config": {
    "data_prop": "tasks",
    "item_key": "task_code",
    "detail_command": "/task-detail"
  },
  "default_sort": {
    "field": "updated_at",
    "direction": "desc"
  },
  "pagination_default": 25
}
```

### types_registry table entry
```json
{
  "id": 3,
  "slug": "task",
  "display_name": "Task",
  "plural_name": "Tasks",
  "storage_type": "model",
  "model_class": "App\\Models\\WorkItem",
  "default_card_component": "TaskCard",
  "default_detail_component": "TaskDetailModal",
  "capabilities": ["searchable", "filterable", "sortable", "assignable"],
  "hot_fields": ["task_code", "task_name", "status", "delegation_status"]
}
```

## Dependencies

### Backend
- **Model**: `App\Models\WorkItem` (represents tasks)
- **Handler**: `App\Commands\Orchestration\Task\ListCommand`
- **Relations**: WorkItem belongsTo assignedAgent, has metadata JSON field

### Frontend
- **Component**: `TaskListModal` (resources/js/components/orchestration/TaskListModal.tsx)
- **Base Component**: Uses `DataManagementModal` internally
- **Registered In**: `CommandResultModal.tsx` COMPONENT_MAP

## Execution Flow

### Step 1: Command Entry
User types `/tasks` in chat interface

### Step 2: Backend Processing
```
CommandController::handleWebCommand()
  ↓
CommandRegistry::getPhpCommand('tasks')
  → Returns: App\Commands\Orchestration\Task\ListCommand
  ↓
new ListCommand()->handle()
  ↓
WorkItem::query()
  ->with('assignedAgent')
  ->orderByRaw("CASE WHEN status = 'todo' THEN 1 
                     WHEN status = 'backlog' THEN 2 
                     ELSE 3 END")
  ->orderBy('created_at', 'desc')
  ->limit(100)
  ->get()
  ↓
Transform each task (extract from metadata)
  ↓
BaseCommand::respond([
  'tasks' => [...],
  'meta' => [...]
])
```

### Step 3: Response Structure
```json
{
  "success": true,
  "component": "TaskListModal",
  "data": {
    "tasks": [
      {
        "id": 1,
        "task_code": "TASK-001",
        "task_name": "Implement user authentication",
        "description": "Add JWT-based auth system",
        "sprint_code": "SPRINT-2025-42",
        "status": "in-progress",
        "delegation_status": "assigned",
        "priority": "high",
        "agent_recommendation": "backend-specialist",
        "assigned_to": "Agent Smith",
        "estimate_text": "8 hours",
        "estimated_hours": 8,
        "tags": ["backend", "security"],
        "has_agent_content": true,
        "has_plan_content": true,
        "has_context": true,
        "created_at": "2025-10-01T10:00:00Z",
        "updated_at": "2025-10-11T15:30:00Z"
      }
    ],
    "meta": {
      "count": 25,
      "has_more": true,
      "filters": {
        "sprint": null,
        "status": null
      }
    }
  },
  "config": {
    "type": { /* type config */ },
    "ui": {
      "modal_container": "TaskListModal",
      "navigation": {
        "data_prop": "tasks",
        "item_key": "task_code",
        "detail_command": "/task-detail"
      }
    }
  }
}
```

### Step 4: Frontend Rendering
```typescript
// CommandResultModal.tsx
1. Receives response
2. Looks up 'TaskListModal' in COMPONENT_MAP
3. Calls buildComponentProps():
   - Sets data.tasks as main data
   - Adds onTaskSelect handler using navigation config
   - Handler: executeDetailCommand('/task-detail TASK-001')
4. Renders <TaskListModal {...props} />
```

### Step 5: User Interaction
```typescript
// User clicks on task row
onRowClick(task)
  → onTaskSelect(task) 
  → executeDetailCommand('/task-detail TASK-001')
  → Loads TaskDetailModal
  → Pushes to navigation stack
```

### Step 6: TaskListModal Internal Flow
```typescript
// TaskListModal.tsx
<DataManagementModal
  data={tasks}
  columns={[...]}
  onRowClick={(task) => onTaskSelect?.(task)}
  actionItems={[
    { key: 'view', label: 'View Details' },
    { key: 'assign', label: 'Assign Agent' },
    { key: 'edit', label: 'Edit Task' }
  ]}
  onAction={(action, task) => {
    if (action === 'view') onTaskSelect?.(task)
    // ... handle other actions
  }}
/>
```

## Navigation Flow
```
/tasks (list)
  ↓ click row
/task-detail TASK-001 (detail view)
  ↓ back button
/tasks
```

## Key Features
- **Status Indicators**: Color-coded badges for task status
- **Delegation Status**: Shows assignment state
- **Priority Levels**: Visual priority indicators
- **Agent Assignment**: Shows assigned agent name
- **Content Flags**: Indicates if task has agent/plan/context content
- **Smart Sorting**: Prioritizes todo > backlog > other statuses
- **Filtering**: Can filter by sprint, status, assignment

## Data Transformation
The handler extracts task data from WorkItem's metadata JSON field:
```php
$taskData = [
  'task_code' => $metadata['task_code'] ?? $task->id,
  'task_name' => $metadata['task_name'] ?? 'Untitled Task',
  'description' => $metadata['description'] ?? null,
  'sprint_code' => $metadata['sprint_code'] ?? null,
  // ... other fields from metadata
];
```

## Testing Commands
```bash
# Test handler directly
php artisan tinker --execute="
\$cmd = new \App\Commands\Orchestration\Task\ListCommand();
dd(\$cmd->handle());
"

# Test with filters (when implemented)
php artisan tinker --execute="
\$cmd = new \App\Commands\Orchestration\Task\ListCommand();
// Future: \$cmd->setSprintFilter('SPRINT-2025-42');
dd(\$cmd->handle());
"
```

## Common Issues & Solutions

### Issue: Tasks not showing
**Solution**: Check WorkItem table has records with proper metadata structure

### Issue: Click doesn't navigate (FIXED)
**Solution**: Updated buildComponentProps to set onTaskSelect handler from navigation_config

### Issue: Assignment not showing
**Solution**: Verify assignedAgent relationship is loaded with('assignedAgent')

### Issue: Sorting not working
**Solution**: Check orderByRaw SQL syntax for your database

## Recent Fixes
- **2025-10-12**: Fixed navigation click handler not being set for config-driven navigation (ADR-003)