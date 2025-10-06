# Modal State Cleanup Bug - Command Modal System

**Created**: 2025-10-06  
**Priority**: Medium  
**Category**: UI/UX Bug  
**Component**: Chat Command Modals  
**Estimated Effort**: 2-3 hours

---

## Problem Statement

When navigating through nested command modals (list → detail → exit), then opening a new command modal, a stale modal from the previous navigation sequence is displayed. The user must press ESC to dismiss the ghost modal before the expected modal appears.

### User Flow with Bug
1. Execute a slash command that opens a modal (e.g., `/sprints`)
2. Click through to a detail view (e.g., select a sprint to view tasks)
3. Exit/close the modal system
4. Execute a different slash command (e.g., `/tasks`)
5. **BUG**: The previous detail modal briefly appears
6. Press ESC to dismiss the ghost modal
7. The expected modal now appears correctly

---

## Technical Context

### Architecture Overview

The slash command system is located in:
- **Modal Orchestrator**: `resources/js/islands/chat/CommandResultModal.tsx`
- **Slash Extension**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`
- **Chat Container**: `resources/js/islands/chat/ChatIsland.tsx`

### Key Components

#### CommandResultModal.tsx (Lines 51-461)
This component manages the command result display and modal routing:
- **State Management**:
  - `detailView` state (line 57): Tracks the current detail view
  - `isLoadingDetail` state (line 58): Loading indicator for detail commands
  
- **Navigation Functions**:
  - `handleBackToList()` (line 64): Clears `detailView` state
  - `executeDetailCommand()` (line 69): Fetches and sets detail view data

- **Modal Flow**:
  - List modals (SprintList, TaskList, AgentList) call `executeDetailCommand()` when user selects an item
  - Detail modals (SprintDetail, TaskDetail) receive `onBack` prop that calls `handleBackToList()`
  - Each modal's `onClose` handler calls both `handleBackToList()` and parent `onClose()`

#### ChatIsland.tsx (Lines 26-28, 400-427)
Manages parent modal state:
- `isCommandModalOpen` state: Controls visibility of CommandResultModal
- `commandResult` state: Stores the command execution result
- System modal states (inbox, type system, scheduler, todo)

### Root Cause

The `detailView` state in CommandResultModal is **not being reset** when:
1. The modal is closed (`isOpen` changes to `false`)
2. A new command is executed (new `result` prop received)

This causes the stale `detailView` to render on the next modal open, since the conditional check at line 292-328 evaluates the non-null `detailView` before checking the current `result`.

---

## Reproduction Steps

1. In Fragments Engine chat interface, type `/sprints` and execute
2. Click on any sprint in the list
3. Wait for sprint detail modal to appear
4. Close the modal (X button or ESC)
5. Type `/tasks` and execute
6. **Observe**: Sprint detail modal appears instead of task list
7. Press ESC
8. **Observe**: Task list modal now appears correctly

---

## Proposed Solution

Add a `useEffect` hook in `CommandResultModal.tsx` to reset `detailView` state when:
- The modal closes (`isOpen` becomes `false`)
- A new command result is received (different `command` or `result.type`)

```typescript
// Add around line 58, after state declarations
useEffect(() => {
  if (!isOpen) {
    setDetailView(null)
  }
}, [isOpen])

useEffect(() => {
  // Clear detail view when switching to a new command
  setDetailView(null)
}, [command, result?.type])
```

### Alternative Approach
Move `detailView` state up to ChatIsland.tsx and pass as prop, allowing parent to control lifecycle.

---

## Files to Review

### Primary
- `resources/js/islands/chat/CommandResultModal.tsx` (state management, cleanup)
- `resources/js/islands/chat/ChatIsland.tsx` (parent modal orchestration)

### Related Modal Components
- `resources/js/components/orchestration/SprintListModal.tsx`
- `resources/js/components/orchestration/SprintDetailModal.tsx`
- `resources/js/components/orchestration/TaskListModal.tsx`
- `resources/js/components/orchestration/TaskDetailModal.tsx`
- `resources/js/components/orchestration/AgentListModal.tsx`

### UI Primitives
- `resources/js/components/ui/dialog.tsx` (Radix UI Dialog wrapper)

---

## Testing Checklist

After implementing fix:

- [ ] Test `/sprints` → select sprint → close → `/tasks` (no ghost modal)
- [ ] Test `/tasks` → select task → close → `/agents` (no ghost modal)
- [ ] Test `/agents` → select agent → close → `/sprints` (no ghost modal)
- [ ] Test rapid modal opening/closing (no flashing/race conditions)
- [ ] Test back button navigation within nested modals (proper state clearing)
- [ ] Test ESC key handling at each modal level
- [ ] Test clicking overlay to close (proper cleanup)

---

## Additional Notes

### Related Systems
- All command modals use the same state management pattern
- System modals (TodoManagement, Inbox, TypeSystem, Scheduler) are managed separately in ChatIsland and may not exhibit this bug
- The bug is specific to the CommandResultModal's nested navigation pattern

### Debug Console Logs
The component has extensive console.log statements that can help trace the issue:
- Line 65: "Going back to list view"
- Line 70: "Executing detail command"
- Line 102: "CommandResultModal render - detailView:..."
- Lines 126, 146, 166, etc.: Modal-specific onClose logging

### Prior Work
This is part of the PHP slash command migration (SPRINT-YAML-MIGRATION). The modal system was designed to support both legacy YAML commands and new PHP command handlers.

---

## Success Criteria

✅ Opening any command modal after closing a nested detail modal shows the correct modal immediately  
✅ No ghost/stale modals appear  
✅ ESC key handling works as expected (closes current modal, not multiple)  
✅ Back button navigation maintains proper state  
✅ Rapid modal interactions don't cause UI glitches  

---

## Related Issues

- Command system: `docs/ORCH_COMMANDS.md`
- Modal migration: SPRINT-YAML-MIGRATION
- UX improvements: SPRINT-BOOKMARK-UX
