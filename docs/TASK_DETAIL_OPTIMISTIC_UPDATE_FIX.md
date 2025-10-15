# Task Detail Optimistic Update Fix

## Status: ✅ FIXED

## Problem
1. **White card flash** - When navigating from sprint → task, a white card would flash before the detail card appeared (sometimes twice)
2. **Full UI refresh** - After assigning sprint/agent, the entire modal would refresh like a page reload
3. **Poor UX** - Felt slow and janky, like an old-school meta refresh

## Root Cause
The component was treating the `task` prop as immutable and calling `onRefresh()` after every save, which:
1. Re-executed the `/orch-task` command
2. Re-fetched all data from backend
3. Replaced the entire component in the navigation stack
4. Caused React to remount everything with a forced key change

This was unnecessary because the backend already returns the updated task in the API response.

## Solution: Optimistic Updates

### 1. Use Local State for Task
Changed from read-only prop to mutable state:

```tsx
// BEFORE: task was a prop
export function TaskDetailModal({ task, ... }) {
  // task is immutable
}

// AFTER: task is local state initialized from prop
export function TaskDetailModal({ task: initialTask, ... }) {
  const [task, setTask] = useState<Task>(initialTask)
  
  useEffect(() => {
    setTask(initialTask)
  }, [initialTask])
}
```

### 2. Update State from API Response
Instead of refreshing the whole view, update just the task state:

```tsx
// BEFORE: Full refresh
const handleSaveField = async (field, value) => {
  await fetch('/api/orchestration/tasks/${task.id}/field', ...)
  if (onRefresh) {
    await onRefresh() // ❌ Triggers full re-fetch and remount
  }
}

// AFTER: Optimistic update
const handleSaveField = async (field, value) => {
  const response = await fetch('/api/orchestration/tasks/${task.id}/field', ...)
  const data = await response.json()
  
  if (data.success && data.task) {
    setTask(prev => ({
      ...prev,
      ...data.task,
      // Map backend fields to frontend
      task_name: data.task.title,
      updated_at: data.task.updated_at,
    }))
  }
}
```

### 3. Enhanced Backend Response
Updated `TaskController::updateField()` to return formatted data:

```php
// BEFORE: Raw model
return response()->json([
    'success' => true,
    'task' => $task,  // Missing sprint_code, assignee_name
]);

// AFTER: Properly formatted
return response()->json([
    'success' => true,
    'task' => [
        'id' => $task->id,
        'task_code' => $task->task_code,
        'task_name' => $task->title,
        'description' => $task->description,
        'sprint_code' => $sprintCode,      // ✅ Looked up from sprint_id
        'assignee_name' => $assigneeName,  // ✅ Looked up from assignee_id
        'assignee_type' => $task->assignee_type,
        // ... all other fields
    ],
]);
```

### 4. Removed Forced Key Remount
Removed the key-based forced remount since it's no longer needed:

```tsx
// BEFORE: Forced remount on every update
const viewKey = `${viewStack.length}-${currentView.data?.task?.updated_at}`
return <div key={viewKey}>{renderComponent(...)}</div>

// AFTER: Natural React updates
return renderComponent(currentView, stackHandlers, isOpen)
```

## How It Works Now

### User Flow: Assign Sprint
1. User clicks "Assign Sprint" button
2. Modal opens with sprint list
3. User selects a sprint (e.g., "SPRINT-002")
4. **Frontend**: Sends PATCH to `/api/orchestration/tasks/73/field` with `{ field: 'sprint_code', value: 'SPRINT-002' }`
5. **Backend**: 
   - Finds sprint by code
   - Updates `task.sprint_id`
   - Looks up `sprint_code` from the new sprint
   - Returns formatted task with `sprint_code: 'SPRINT-002'`
6. **Frontend**: 
   - Receives response
   - Updates `task` state with new data
   - **React automatically re-renders** with new sprint_code
   - **No page refresh, no white flash** ✨

### User Flow: Assign Agent
Same pattern:
1. User selects agent
2. Backend updates `task.assignee_id` and `task.assignee_type`
3. Backend looks up agent name
4. Returns `assignee_name: 'Agent Name'`
5. Frontend updates state
6. UI updates instantly

## Performance Improvements

### Before
- 🐌 Full command re-execution (~200-500ms)
- 🐌 Network round-trip for refresh
- 🐌 Component unmount/remount
- 🐌 All child components re-initialize
- 👎 White flash visible to user

### After
- ⚡ Local state update (~1ms)
- ⚡ Only affected DOM nodes re-render
- ⚡ Smooth, instant UI update
- 👍 Professional UX

## Files Modified

### Frontend
- **`resources/js/components/orchestration/TaskDetailModal.tsx`**
  - Changed `task` prop to `initialTask`
  - Added `const [task, setTask] = useState(initialTask)`
  - Updated `handleSaveField` to update local state from API response
  - Removed `onRefresh()` call

- **`resources/js/islands/chat/CommandResultModal.tsx`**
  - Removed forced key-based remount
  - Kept refresh logic for edge cases but it's rarely used now

### Backend
- **`app/Http/Controllers/Orchestration/TaskController.php`**
  - Enhanced `updateField()` return value
  - Now returns formatted task with computed fields
  - Looks up `sprint_code` from `sprint_id`
  - Looks up `assignee_name` from `assignee_id`

## Testing

### Manual Test
1. `/orch` → click sprint → click task
   - ✅ Should transition smoothly, no white flash
2. Click "Assign Sprint" → select different sprint
   - ✅ Sprint code updates instantly in UI
   - ✅ No full refresh
3. Click "Assign Agent" → select agent
   - ✅ Agent name updates instantly in UI
   - ✅ No full refresh
4. Edit other fields (status, priority, description)
   - ✅ All update instantly

### Expected Behavior
- ✅ Smooth, instant updates
- ✅ No white flashes
- ✅ No "meta refresh" feeling
- ✅ Professional, modern UX
- ✅ Same as before when it was working

## Why This Works

The key insight is that **the backend already returns the updated data** in the response. We don't need to:
- ❌ Re-fetch from the database
- ❌ Re-execute the command
- ❌ Rebuild the component tree

We just need to:
- ✅ Update local state
- ✅ Let React diff and patch the DOM
- ✅ Trust React's rendering optimization

## Related Issues Fixed
- Sprint assignment updates immediately
- Agent assignment updates immediately
- No more white card flash during navigation
- No more "page refresh" feeling
- Matches the UX from the previous working version

## Previous Approach Comparison

The old working version (commit `0c12c99`) used `InlineEditSelect` which:
- Had its own local state
- Updated immediately on change
- Called the API in the background
- Didn't need `onRefresh`

Our new approach achieves the same UX by:
- Using local task state
- Updating from API response
- No full view refresh
- Clean separation of concerns
