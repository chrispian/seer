# Sprint Create Modal Navigation Fix

**Date**: 2025-10-12  
**Issue**: Sprint create form didn't use navigation stack, causing ESC to close entire modal system  
**Resolution**: Migrated from nested Dialog pattern to navigation stack pattern

---

## Problem Summary

### What Was Broken

When creating a sprint:
1. User clicks "Create Sprint" from `/sprints` list
2. SprintFormModal opened as **nested Dialog** inside SprintListModal
3. Pressing ESC closed the entire modal system (both list and form)
4. **Expected**: ESC should go back to sprint list
5. **Actual**: ESC closed everything and returned to chat

### Root Cause

The original implementation used **nested Dialogs**:

```tsx
// SprintListModal (BROKEN PATTERN)
<DataManagementModal isOpen={isOpen}>  {/* Outer Dialog */}
  <Button onClick={fetchAndShowForm}>Create</Button>
  
  {showCreateModal && (
    <SprintFormModal isOpen={showCreateModal}>  {/* Nested Dialog ❌ */}
      <Form />
    </SprintFormModal>
  )}
</DataManagementModal>
```

**Problems with nested Dialogs:**
1. Browser can't properly manage focus with multiple Dialog elements
2. ESC key handlers conflict - parent Dialog captures before child
3. Z-index layering becomes unpredictable
4. DataManagementModal's ESC handler runs first, but has no `onBack` prop
5. Accessibility broken (screen readers confused by overlapping dialogs)

### Why This Is Tricky

Multiple layers of async timing and event propagation:
- Dialog component registers ESC listener on mount
- DataManagementModal has its own ESC handler with `preventDefault/stopPropagation`
- SprintFormModal's Dialog tries to handle ESC but parent already captured it
- React event bubbling vs native browser event capturing
- State updates are async, so timing matters for which Dialog is "on top"

As you said: "Layers and timings are always hard. Add in closures/promises/awaits and it's a good way to melt your brain." 🧠💥

---

## Solution

### Navigation Stack Pattern

Instead of nested Dialogs, use CommandResultModal's navigation stack:

```tsx
// SprintListModal (CORRECT PATTERN)
function SprintListModal({ onCreate }) {
  return (
    <DataManagementModal isOpen={isOpen}>
      <Button onClick={onCreate}>Create</Button>  {/* ← Calls command */}
    </DataManagementModal>
  )
}

// CommandResultModal manages stack
function CommandResultModal({ result }) {
  const [viewStack, setViewStack] = useState([])
  
  const executeDetailCommand = (cmd) => {
    fetch('/api/commands/execute', { body: JSON.stringify({ command: cmd }) })
      .then(result => setViewStack(prev => [...prev, result]))  // Push to stack
  }
  
  const handleBack = () => {
    setViewStack(prev => prev.slice(0, -1))  // Pop from stack
  }
  
  // Pass handlers to children
  props.onCreate = () => executeDetailCommand('/sprint-create')
  props.onBack = handleBack  // For form modals in stack
}
```

**Key Changes:**

1. **SprintListModal** no longer manages child modal state
2. **onClick={onCreate}** calls command system instead of opening nested Dialog
3. **CommandResultModal** pushes SprintFormModal to navigation stack
4. **Only ONE Dialog** rendered at a time (stack replaces base view)
5. **SprintFormModal** receives `onBack` prop, uses it for ESC

---

## Implementation Details

### Files Modified

#### 1. `SprintListModal.tsx`
**Before:**
- Managed `showCreateModal` state
- Called `/api/commands/execute` directly
- Rendered nested `<SprintFormModal>`

**After:**
- Accepts `onCreate` handler prop
- Calls `onCreate()` when button clicked
- No nested Dialog rendering

#### 2. `CommandResultModal.tsx`
Added two key fixes:

**Fix #1: Pass `onCreate` handler to Sprint list modals**
```tsx
if (componentName.includes('Sprint')) {
  props.onSprintSelect = (item) => executeDetailCommand!(`/sprint-detail ${item.code}`)
  props.onCreate = () => executeDetailCommand!('/sprint-create')  // ← Added
}
```

**Fix #2: Pass `onBack` handler to Form modals (not just Detail)**
```tsx
// Before: Only Detail modals got onBack
if (componentName.includes('Detail')) {
  props.onBack = handlers.onBackToList
}

// After: Form modals also get onBack
if (componentName.includes('Detail') || componentName.includes('Form')) {
  props.onBack = handlers.onBackToList
}
```

#### 3. `SprintFormModal.tsx`
**Before:**
- `onOpenChange={onClose}` - always closed entire stack

**After:**
```tsx
onOpenChange={(open) => {
  if (!open) {
    if (onBack) {
      onBack()  // Go back in stack
    } else {
      onClose()  // Close entire system
    }
  }
}}
```

Also on form submit success:
```tsx
if (result.success) {
  if (onBack) {
    onBack()  // Return to list with refresh
  } else {
    onClose()
  }
}
```

#### 4. Database
Registered `/sprint-create` command:
```sql
INSERT INTO commands (command, name, handler_class, ui_modal_container, type_slug)
VALUES (
  '/sprint-create',
  'Create Sprint',
  'App\Commands\Orchestration\Sprint\CreateCommand',
  'SprintFormModal',
  'sprint'
);
```

---

## Navigation Flow

### Before (Broken)
```
1. /sprints → SprintListModal renders (Dialog 1)
2. Click "Create" → SprintFormModal renders (Dialog 2, nested)
3. Press ESC → DataManagementModal captures event
4. → Checks for onBack: false
5. → Calls onClose()
6. → ENTIRE modal system closes ❌
```

### After (Fixed)
```
1. /sprints → SprintListModal renders
   viewStack: []
   
2. Click "Create" → onCreate() called
   → executeDetailCommand('/sprint-create')
   → Pushes to stack
   viewStack: [SprintFormModal]
   
3. Press ESC → SprintFormModal's Dialog captures event
   → onOpenChange(false) called
   → Checks for onBack: true ✅
   → Calls onBack()
   → Pops from stack
   viewStack: []
   → SprintListModal visible again ✅
```

---

## Console Log Flow (Working)

```
[CommandResultModal v2.0 - NAVIGATION STACK] viewStack length: 0
[CommandResultModal] Using ui.modal_container: SprintListModal

[SprintListModal] Create button clicked, onCreate exists? true
Executing detail command: /sprint-create

Detail command result: {success: true, type: 'sprint', component: 'SprintFormModal'}
[CommandResultModal v2.0 - NAVIGATION STACK] viewStack length: 1
[CommandResultModal] Rendering from stack - component: SprintFormModal

[SprintFormModal] onOpenChange called, open: false has onBack? true
[SprintFormModal] Calling onBack()

CommandResultModal: Going back one level stack length: 1
[CommandResultModal v2.0 - NAVIGATION STACK] viewStack length: 0
[CommandResultModal] Using ui.modal_container: SprintListModal
```

---

## Key Learnings

### The Pattern
**NEVER nest Dialogs for navigation. Always use the navigation stack.**

```tsx
❌ DON'T: Nested Dialogs
<Dialog>
  <Dialog>  // ← Browser can't handle this properly
    ...
  </Dialog>
</Dialog>

✅ DO: Navigation Stack
<Button onClick={() => executeCommand('/next-view')}>
  // CommandResultModal handles rendering ONE dialog at a time
</Button>
```

### ESC Key Handling
The component with the highest z-index should handle ESC:
1. Dialog component registers native `keydown` listener
2. DataManagementModal also registers `keydown` listener with `capture: true`
3. Both try to handle ESC - parent wins due to event capturing
4. **Solution**: Only render ONE Dialog, managed centrally

### Async State Timing
```tsx
// ❌ Race condition
onClick={() => {
  setShowChild(true)  // Async
  // Child Dialog mounts, but parent already has ESC handler
}}

// ✅ Stack manages rendering
onClick={() => {
  executeCommand('/child')  // Single source of truth
  // CommandResultModal decides what to render
}}
```

---

## Future Improvements

### 1. Config-Driven Create Commands
Add `create_command` to navigation config:

```json
{
  "navigation_config": {
    "detail_command": "/sprint-detail",
    "create_command": "/sprint-create",
    "item_key": "code"
  }
}
```

Then automatically wire `onCreate` handler:
```tsx
if (navConfig.create_command) {
  props.onCreate = () => executeDetailCommand!(navConfig.create_command)
}
```

### 2. Breadcrumb UI
Show navigation path:
```
Sprints > Create New Sprint
         [X] [< Back]
```

### 3. Browser History Integration
Map navigation stack to browser history:
```tsx
history.pushState({ view: '/sprint-create' })
// Browser back button = pop stack
```

---

## Related Documentation

- **ADR-005**: Modal Navigation Stack Pattern (decision record)
- **MODAL_NAVIGATION_PATTERN.md**: Implementation guide
- **modal-state-cleanup-bug.md**: Original bug report
- **ADR-003**: Config-Driven Navigation Handlers

---

## Testing Checklist

✅ Click "Create Sprint" → Form opens via stack  
✅ ESC on form → Returns to list (not close all)  
✅ Cancel button → Returns to list  
✅ Submit form → Saves, returns to list with refresh  
✅ From list, click sprint → Detail opens  
✅ From detail, ESC → Returns to list  
✅ From detail, click task → Task detail opens  
✅ Deep navigation (list → detail → task) → ESC works at each level  
✅ No "ghost modals" or stale state  
✅ Console shows correct stack lengths  

---

## Summary

**Problem**: Nested Dialogs caused ESC key to close entire modal system instead of going back one level.

**Root Cause**: Multiple Dialog components competing for ESC key handling, with parent capturing before child.

**Solution**: Use navigation stack pattern - only ONE Dialog rendered at a time, managed centrally by CommandResultModal.

**Result**: ESC key now properly navigates back through modal stack, matching user expectations.

**Lesson**: Don't nest Dialogs. Don't nest Dialogs. **Don't nest Dialogs.**

🎉 Sprint CRUD create functionality now works correctly with proper navigation!
