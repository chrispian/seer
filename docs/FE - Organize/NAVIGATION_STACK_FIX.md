# Modal Navigation Stack Implementation - Fix Documentation

## Issue Summary
Multi-layer modal navigation (Sprints List → Sprint Detail → Task Detail) had broken ESC key handling. ESC would close the modal instead of navigating back through layers.

## Root Cause
**Stale closure bug in React useEffect** - The ESC key handler in DataManagementModal captured old `onBack` prop values in its closure, causing it to always see `onBack` as undefined even after the prop updated.

### Technical Details
1. When Task Detail popped the stack, SprintDetailModal re-rendered with new `onBack` prop
2. DataManagementModal's `useEffect` with dependencies `[isOpen, onClose, onBack]` created a new handler
3. BUT the old handler was still referenced in the event listener until the effect cleanup ran
4. ESC key was handled by the stale closure checking the old (undefined) `onBack` value
5. This caused `onClose()` to fire instead of `onBack()`, closing the modal

## Solution
**Use React ref to avoid stale closures:**

```typescript
// Store latest onBack in ref (always current)
const onBackRef = useRef(onBack)
useEffect(() => { onBackRef.current = onBack }, [onBack])

// ESC handler uses ref (always fresh)
useEffect(() => {
  const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
      if (onBackRef.current) {
        e.preventDefault()
        e.stopPropagation()
        onBackRef.current()  // ← Always uses latest value
      } else {
        onClose()
      }
    }
  }
  // Capture phase ensures we run before Dialog's handler
  document.addEventListener('keydown', handleKeyDown, { capture: true })
  return () => document.removeEventListener('keydown', handleKeyDown, { capture: true })
}, [isOpen, onClose])
```

## Key Changes

### 1. Backend - Config-Driven Component Resolution
**Files:**
- `app/Commands/BaseCommand.php` - Added `ui_base_renderer` to config
- `app/Models/Command.php` - Added `ui_base_renderer` to fillable
- `database/migrations/2025_10_11_010948_add_ui_base_renderer_to_commands_table.php` - New migration
- `app/Commands/Orchestration/Sprint/DetailCommand.php` - Use `$this->respond()`
- `app/Commands/Orchestration/Task/DetailCommand.php` - Use `$this->respond()`

**Purpose:** Commands now include component name + config in responses for proper routing.

### 2. Frontend - Navigation Stack Architecture
**File:** `resources/js/islands/chat/CommandResultModal.tsx`

**Changes:**
- Implemented navigation stack: `const [viewStack, setViewStack] = useState<CommandResult[]>([])`
- Stack push/pop for drill-down navigation
- Separate handlers:
  - `onClose` → X/Close buttons (close modal from any layer)
  - `onBack` → ESC/Back button (navigate back one layer)
- Stack reset on modal close via useEffect
- Component resolution checks `result.component` first, then config fallbacks

### 3. Modal Components - ESC Key Routing
**Files:**
- `resources/js/components/ui/DataManagementModal.tsx`
  - Added `onBack?: () => void` prop
  - **Fixed:** Ref-based ESC handler to avoid stale closures
  - Capture phase event listener to run before Dialog
  - Dialog `onOpenChange` uses `onBack` when available

- `resources/js/components/orchestration/SprintDetailModal.tsx`
  - Pass `onBack` prop through to DataManagementModal

- `resources/js/components/orchestration/TaskDetailModal.tsx`
  - Dialog `onOpenChange` uses `onBack` when available

## Expected Behavior (Now Working ✅)

### ESC Key (Navigate Back)
1. **Sprints List** (root): ESC → close modal
2. **Sprint Detail**: ESC → back to Sprints List  
3. **Task Detail**: ESC → back to Sprint Detail

### X / Close Buttons (Close Modal)
- Any layer: X or Close button → close modal entirely

### Back Button (Navigate Back)
- Same as ESC key

## Navigation Flow Example
```
/sprints 
  → Click sprint → SprintDetailModal (stack: [SprintDetail])
    → Click task → TaskDetailModal (stack: [SprintDetail, TaskDetail])
      → ESC → SprintDetailModal (stack: [SprintDetail])
        → ESC → SprintListModal (stack: [])
          → ESC → Modal closes
```

## Testing Checklist
- [x] ESC on Sprints List closes modal
- [x] ESC on Sprint Detail goes back to Sprints List
- [x] ESC on Task Detail goes back to Sprint Detail
- [x] X button closes modal from any layer
- [x] Close button closes modal from any layer
- [x] Back button navigates back one layer
- [x] Stack resets when modal closes
- [x] Reopening modal shows fresh root view

## Technical Learnings

### React Gotchas Encountered
1. **Stale Closures in useEffect** - Event handlers can capture old prop values
2. **Event Listener Timing** - Capture phase needed to intercept before Dialog
3. **Multiple ESC Handlers** - Global handler, Dialog handler, component handler all compete
4. **State Updates in Closures** - `setViewStack` in old closure sees old stack length

### Best Practices Applied
1. Use refs for values that change but shouldn't recreate handlers
2. Capture phase listeners for intercepting browser/library behavior
3. Single source of truth for navigation state (viewStack)
4. Clear separation of concerns: ESC=navigate, X/Close=close

## Future Improvements
- Consider extracting navigation logic to custom hook
- Potentially use React Router for modal navigation (hash-based)
- Add keyboard shortcuts for direct layer jumps (Cmd+1, Cmd+2, etc.)
- Implement breadcrumb navigation UI

## Related Tasks
- Task T-FE-UI-20-REFACTOR: Config field refactoring (completed)
- Navigation stack implementation (completed)

## Credit
Solution inspired by rubber duck debugging with two sub-agents who identified:
1. Stale closure root cause
2. Ref-based pattern solution
3. Capture phase event interception technique
