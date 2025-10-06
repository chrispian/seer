# Detail Modal Integration

## Overview
Integrated detail view navigation into the command system, allowing users to click items in list modals (sprints, tasks, agents) to view detailed information with back button support.

## Implementation

### Backend Commands
Created three new detail commands in `app/Commands/`:
- `SprintDetailCommand.php` - accepts "43" or "SPRINT-43", restructures data for modal compatibility
- `TaskDetailCommand.php` - accepts task codes (e.g., "T-ART-02-CAS")
- `AgentDetailCommand.php` - accepts agent slugs, returns formatted markdown message

All registered in `app/Services/CommandRegistry.php` with aliases (sd, td, ad).

**Key Implementation Details:**
- `SprintDetailCommand` extracts `tasks` and `stats` from nested sprint object to match `SprintDetailModal` props
- `AgentDetailCommand` formats agent data as markdown (no `AgentDetailModal` component exists yet)
- All commands use MCP tools for data fetching (`SprintDetailTool`, `TaskDetailTool`, `AgentDetailTool`)

### Frontend Changes

#### CommandResultModal.tsx
**New State Management:**
- `detailView: CommandResult | null` - stores detail command result
- `isLoadingDetail: boolean` - loading state

**New Functions:**
- `executeDetailCommand(command: string)` - executes detail commands via `/api/commands/execute`
- `handleBackToList()` - clears detail view to return to list
- `renderOrchestrationUI(currentResult, isDetail)` - renders with context awareness

**List Modal Integration:**
Updated callbacks for list modals:
- `SprintListModal.onSprintSelect` → executes `/sprint-detail ${sprint.code}`
- `TaskListModal.onTaskSelect` → executes `/task-detail ${task.task_code}`
- `AgentListModal.onAgentSelect` → executes `/agent-detail ${agent.slug}`

**Detail Modal Integration:**
- Detail modals use `onBack={isDetail ? handleBackToList : onClose}`
- Back button returns to list view when navigating from list
- ESC key closes entire modal when viewing directly
- Sprint detail modal includes `onTaskSelect` to navigate to task details
- Message-only responses (like agent details) render in a simple dialog with back button

## User Flow

### From List to Detail
1. User runs `/sprints` or `/tasks` or `/agents`
2. List modal displays
3. User clicks an item
4. `executeDetailCommand()` fetches detail via API
5. Detail modal replaces list modal
6. Back button returns to list view

### Direct Detail View
1. User runs `/sprint-detail 43` directly
2. Detail modal displays
3. Back button closes modal (no list to return to)

## Testing

```bash
# Test list modals
/sprints
/tasks  
/agents

# Test direct detail views
/sprint-detail 43
/task-detail T-ART-02-CAS
/agent-detail backend-engineer

# Test aliases
/sd 43
/td T-ART-02-CAS
/ad backend-engineer
```

## Technical Notes

### API Pattern
```typescript
const response = await fetch('/api/commands/execute', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrf
  },
  body: JSON.stringify({ command: detailCommand })
})
```

### Component Routing
Commands return `component` field that determines which modal to show:
- `SprintListModal` → list view
- `SprintDetailModal` → detail view
- Same pattern for tasks and agents

### State Management
- List view stored in `result` prop
- Detail view stored in `detailView` state
- Detail view takes precedence when present
- Clearing `detailView` returns to list

## Files Modified

**Backend:**
- `app/Commands/SprintDetailCommand.php` (new) - restructures nested data
- `app/Commands/TaskDetailCommand.php` (new)
- `app/Commands/AgentDetailCommand.php` (new) - returns markdown message
- `app/Services/CommandRegistry.php` (updated) - registered detail commands

**Frontend:**
- `resources/js/islands/chat/CommandResultModal.tsx` (major update) - detail view state management, message modal rendering
- `resources/js/components/orchestration/TaskListModal.tsx` (updated) - removed blocking alert from onRowClick
- `resources/js/components/orchestration/AgentListModal.tsx` (updated) - cleaned up row click handling

## Troubleshooting

### Issue: Row clicks not working
**Symptoms:** Clicking rows in list modals shows alerts or does nothing
**Cause:** Alert dialogs blocking callbacks or missing `onRowClick` prop
**Fix:** Ensure `onRowClick` calls the callback directly without intermediate alerts

### Issue: Detail view shows list again
**Symptoms:** Clicking an item briefly shows detail then reverts to list
**Cause:** Detail command returns `component: null`, falls through to list rendering
**Fix:** Added special case for message-only detail responses in `CommandResultModal.tsx`

### Issue: Sprint detail has no content
**Symptoms:** Sprint detail modal opens but shows no tasks/stats
**Cause:** Data structure mismatch - modal expects flat props but receives nested structure
**Fix:** Restructure data in command to extract `tasks` and `stats` from sprint object

## Next Steps

Potential improvements:
- Create `AgentDetailModal` component for richer agent visualization
- Add loading indicator during detail fetch
- Implement refresh functionality for list modals
- Add keyboard shortcuts (e.g., Ctrl+B for back)
- Cache detail views to avoid re-fetching
