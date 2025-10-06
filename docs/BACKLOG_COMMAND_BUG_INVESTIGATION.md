# Backlog Command Bug Investigation

**Date**: 2025-10-06  
**Reporter**: User  
**Status**: Investigated - Ready to Fix

---

## Problem Statement

When typing `/backlog` or `/backlog-list` in the chat composer, the slash command shows **current sprints** instead of backlog items.

---

## Root Cause Analysis

### Issue Identified

**File**: `app/Commands/BacklogListCommand.php` (Line 14)

The BacklogListCommand is correctly:
1. ✅ Querying backlog tasks (`status = 'backlog'`)
2. ✅ Formatting task data properly
3. ❌ **BUT returning wrong component type**

**Current Code (Line 14)**:
```php
return [
    'type' => 'task',
    'component' => 'TaskListModal',  // ❌ WRONG - This is generic
    'data' => $tasks
];
```

### Why It Shows Sprints

Looking at the modal routing in `resources/js/islands/chat/CommandResultModal.tsx`:

**Line 141-160**: The `TaskListModal` component is designed for showing tasks **within a sprint context**, not standalone backlog items.

**Expected Behavior**:
- `/tasks` → Show tasks in current/selected sprint
- `/backlog-list` → Show backlog items (tasks not assigned to any sprint)

**Actual Behavior**:
- Both commands use `TaskListModal`
- The modal shows whatever data is passed
- Backlog command passes backlog tasks but uses sprint-oriented modal

---

## Comparison with Working Commands

### SprintListCommand (Working Correctly)
```php
// app/Commands/SprintListCommand.php
return [
    'type' => 'sprint',
    'component' => 'SprintListModal',  // ✅ Correct dedicated component
    'data' => $sprintData
];
```

### BacklogListCommand (Bug)
```php
// app/Commands/BacklogListCommand.php  
return [
    'type' => 'task',
    'component' => 'TaskListModal',  // ❌ Wrong - needs dedicated component
    'data' => $tasks
];
```

---

## Solution Options

### Option 1: Create Dedicated BacklogListModal (Recommended)

**Create**: `resources/js/components/orchestration/BacklogListModal.tsx`

**Pros**:
- Clean separation of concerns
- Backlog-specific UI/UX
- Can add backlog-specific features (e.g., "Add to Sprint" button)
- Consistent with SprintListModal pattern

**Cons**:
- More code (new component)
- ~30-45 minutes implementation time

**Changes Required**:
1. Create `BacklogListModal.tsx` (based on TaskListModal)
2. Update `BacklogListCommand.php` line 14: `'component' => 'BacklogListModal'`
3. Import and route in `CommandResultModal.tsx`
4. Add backlog-specific columns/actions

---

### Option 2: Reuse Existing TaskListModal with Flag

**Modify**: `app/Commands/BacklogListCommand.php`

```php
return [
    'type' => 'task',
    'component' => 'TaskListModal',
    'data' => $tasks,
    'context' => 'backlog'  // Add context flag
];
```

**Pros**:
- Quick fix (~5 minutes)
- Less code duplication

**Cons**:
- TaskListModal becomes more complex
- Harder to add backlog-specific features later
- Mixes sprint tasks and backlog concerns

---

### Option 3: Fix Modal Routing Logic

**Issue**: The modal routing might be showing sprints due to fallback logic

**Investigation Needed**:
- Check if `type: 'task'` triggers sprint modal somehow
- Review CommandResultModal.tsx lines 100-112 (isOrchestrationCommand logic)

---

## Recommended Solution: Option 1

Create dedicated `BacklogListModal` component.

### Implementation Plan

#### Step 1: Create BacklogListModal Component
**File**: `resources/js/components/orchestration/BacklogListModal.tsx`

Base it on `TaskListModal.tsx` but with:
- Title: "Backlog Items"
- Columns: Task Code, Name, Priority, Estimate, Status
- Remove "Sprint" column (backlog items aren't in sprints)
- Add "Add to Sprint" action button (future enhancement)
- Add "Create Task" button

#### Step 2: Update BacklogListCommand
**File**: `app/Commands/BacklogListCommand.php`

Change line 14:
```php
return [
    'type' => 'backlog',  // More specific type
    'component' => 'BacklogListModal',  // Dedicated component
    'data' => $tasks
];
```

#### Step 3: Update Modal Router
**File**: `resources/js/islands/chat/CommandResultModal.tsx`

Add import (around line 10):
```typescript
import { BacklogListModal } from '@/components/orchestration/BacklogListModal'
```

Add case in switch (around line 160):
```typescript
case 'BacklogListModal':
  return (
    <BacklogListModal
      isOpen={isOpen}
      onClose={() => {
        console.log('BacklogListModal onClose called')
        handleBackToList()
        onClose()
      }}
      tasks={currentResult.data}
      onTaskSelect={(task) => {
        console.log('Backlog task selected:', task)
        executeDetailCommand(`/task-detail ${task.task_code}`)
      }}
      onRefresh={() => {
        console.log('Backlog refresh requested')
        alert('Backlog refresh functionality not implemented yet.')
      }}
    />
  )
```

---

## Testing Checklist

After fix:
- [ ] `/backlog` shows backlog items (not sprints)
- [ ] `/backlog-list` shows same as `/backlog`
- [ ] `/tasks` still shows sprint tasks correctly
- [ ] `/sprints` still shows sprint list correctly
- [ ] Can click through to task detail from backlog
- [ ] Modal closes properly
- [ ] No console errors
- [ ] TypeScript builds without errors

---

## Files to Modify

1. **Create**: `resources/js/components/orchestration/BacklogListModal.tsx` (~150 lines)
2. **Modify**: `app/Commands/BacklogListCommand.php` (line 14: change component name)
3. **Modify**: `resources/js/islands/chat/CommandResultModal.tsx` (add import + case statement)

---

## Estimated Time

- Investigation: ✅ Done (15 minutes)
- Implementation: 30-45 minutes
  - Create BacklogListModal: 25 minutes
  - Update BacklogListCommand: 2 minutes
  - Update CommandResultModal: 5 minutes
  - Testing: 10 minutes
- **Total**: ~1 hour

---

## Alternative Quick Fix (If Urgent)

If we need a quick fix right now:

**File**: `app/Commands/BacklogListCommand.php` (Line 11-14)

Change to return sprint-like structure temporarily:
```php
return [
    'type' => 'task',
    'component' => 'TaskListModal',
    'data' => $tasks,
    'title' => 'Backlog Items',  // Override modal title
    'hideSprintColumn' => true    // Flag to hide sprint column
];
```

This is a **hack** but would work immediately. Not recommended for long-term.

---

## Related Issues

- The TaskListModal might need refactoring to better handle different contexts
- Consider creating a generic `DataListModal` component that both Sprint and Backlog can extend
- The `/bl` alias works correctly (it calls BacklogListCommand)

---

## Command Registry Audit

All backlog-related commands:

### CommandRegistry.php
```php
// Line 82-83: Old PHP Command System
'backlog-list' => BacklogListCommand::class,
'bl' => BacklogListCommand::class, // alias

// Line 101: New PHP Command System  
'backlog-list' => \App\Commands\BacklogListCommand::class,
```

### YAML Commands
- `fragments/commands/backlog-list/command.yaml` - YAML version exists
- Slash trigger: `/backlog-list`
- **Note**: PHP command system takes precedence

---

## Next Steps

1. Create ORCH task for bug fix
2. Implement Option 1 (dedicated BacklogListModal)
3. Test thoroughly
4. Commit with clear description
