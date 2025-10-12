# /sprints Command Reference

## Overview
The `/sprints` command displays a list of all sprints with task counts, status, and navigation to sprint details.

## Database Configuration

### commands table entry
```json
{
  "id": 1,
  "command": "/sprints",
  "name": "List Sprints",
  "description": "Display all sprints with task counts and status",
  "category": "Orchestration",
  "type_slug": "sprint",
  "handler_class": "App\\Commands\\Orchestration\\Sprint\\ListCommand",
  "available_in_slash": true,
  "available_in_cli": false,
  "available_in_mcp": true,
  "ui_modal_container": "SprintListModal",
  "ui_layout_mode": "table",
  "ui_base_renderer": "DataManagementModal",
  "navigation_config": {
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
  "id": 1,
  "slug": "sprint",
  "display_name": "Sprint",
  "plural_name": "Sprints",
  "storage_type": "model",
  "model_class": "App\\Models\\OrchestrationSprint",
  "default_card_component": "SprintCard",
  "default_detail_component": "SprintDetailModal",
  "capabilities": ["searchable", "filterable", "sortable"],
  "hot_fields": ["code", "title", "status"]
}
```

## Dependencies

### Backend
- **Model**: `App\Models\Sprint` (aliased from OrchestrationSprint)
- **Handler**: `App\Commands\Orchestration\Sprint\ListCommand`
- **Relations**: Sprint has many SprintItems, has many WorkItems through SprintItems

### Frontend
- **Component**: `SprintListModal` (resources/js/components/orchestration/SprintListModal.tsx)
- **Base Component**: Uses `DataManagementModal` internally
- **Registered In**: `CommandResultModal.tsx` COMPONENT_MAP

## Execution Flow

### Step 1: Command Entry
User types `/sprints` in chat interface

### Step 2: Backend Processing
```
CommandController::handleWebCommand()
  ↓
CommandRegistry::getPhpCommand('sprints')
  → Returns: App\Commands\Orchestration\Sprint\ListCommand
  ↓
new ListCommand()->handle()
  ↓
Sprint::query()
  ->orderByDesc('created_at')
  ->limit(50)
  ->get()
  ↓
Transform each sprint with task counts
  ↓
BaseCommand::respond([
  'sprints' => [...],
  'unassigned_tasks' => [...]
])
```

### Step 3: Response Structure
```json
{
  "success": true,
  "component": "SprintListModal",
  "data": {
    "sprints": [
      {
        "id": 1,
        "code": "SPRINT-2025-42",
        "title": "Week 42 Sprint",
        "status": "active",
        "task_count": 15,
        "completed_tasks": 5,
        "start_date": "2025-10-14",
        "end_date": "2025-10-21"
      }
    ],
    "unassigned_tasks": []
  },
  "config": {
    "type": { /* type config */ },
    "ui": {
      "modal_container": "SprintListModal",
      "navigation": {
        "data_prop": "sprints",
        "item_key": "code",
        "detail_command": "/sprint-detail"
      }
    }
  }
}
```

### Step 4: Frontend Rendering
```typescript
// CommandResultModal.tsx
1. Receives response
2. Looks up 'SprintListModal' in COMPONENT_MAP
3. Calls buildComponentProps():
   - Sets data.sprints as main data
   - Adds onSprintSelect handler using navigation config
   - Handler: executeDetailCommand('/sprint-detail SPRINT-2025-42')
4. Renders <SprintListModal {...props} />
```

### Step 5: User Interaction
```typescript
// User clicks on sprint row
onRowClick(sprint) 
  → onSprintSelect(sprint)
  → executeDetailCommand('/sprint-detail SPRINT-2025-42')
  → Loads SprintDetailModal
  → Pushes to navigation stack
```

## Navigation Flow
```
/sprints (list)
  ↓ click row
/sprint-detail SPRINT-2025-42 (detail view)
  ↓ click task
/task-detail TASK-001 (task detail)
  ↓ back button
/sprint-detail SPRINT-2025-42
  ↓ back button
/sprints
```

## Key Features
- **Hierarchical Navigation**: Sprint → Tasks drill-down
- **Task Counts**: Shows completion metrics
- **Status Badges**: Visual status indicators
- **Sorting**: Default by updated_at desc
- **Filtering**: Can filter by status
- **Actions**: View details, edit, delete (via action menu)

## Testing Commands
```bash
# Test handler directly
php artisan tinker --execute="
\$cmd = new \App\Commands\Orchestration\Sprint\ListCommand();
dd(\$cmd->handle());
"

# Test via CommandRegistry
php artisan tinker --execute="
\$handler = \App\Services\CommandRegistry::getPhpCommand('sprints');
\$cmd = new \$handler();
dd(\$cmd->handle());
"
```

## Common Issues & Solutions

### Issue: Click doesn't navigate
**Solution**: Check navigation_config has correct item_key and detail_command

### Issue: Empty list
**Solution**: Verify Sprint model has data, check orderBy clause

### Issue: Modal not found
**Solution**: Ensure SprintListModal is registered in COMPONENT_MAP