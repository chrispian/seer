# ADR-005: Modal Navigation Stack Pattern

**Status**: Accepted  
**Date**: 2025-10-12  
**Deciders**: Development Team  
**Related**: ADR-003 (Config-Driven Navigation)

---

## Context

The Fragments Engine modal system supports nested navigation (list → detail → sub-detail). We discovered two competing patterns being used:

### Pattern A: Nested Dialogs (❌ BROKEN)
```tsx
function ParentModal() {
  const [showChild, setShowChild] = useState(false)
  
  return (
    <Dialog open={isOpen}>  {/* Parent Dialog */}
      <DialogContent>
        <Button onClick={() => setShowChild(true)}>Open Child</Button>
      </DialogContent>
      
      {showChild && (
        <Dialog open={showChild}>  {/* Nested Dialog */}
          <DialogContent>Child content</DialogContent>
        </Dialog>
      )}
    </Dialog>
  )
}
```

**Problems with this approach:**
1. ESC key captured by parent Dialog, not child
2. Browser can't manage focus correctly with nested Dialogs
3. Creates duplicate overlay layers
4. Breaks accessibility (screen readers confused)
5. State management becomes complex
6. No centralized navigation history

### Pattern B: Navigation Stack (✅ CORRECT)
```tsx
// In CommandResultModal
function CommandResultModal({ result }) {
  const [viewStack, setViewStack] = useState([])
  
  const executeDetailCommand = (cmd) => {
    // Push new view to stack
    setViewStack(prev => [...prev, newView])
  }
  
  const handleBack = () => {
    // Pop from stack
    setViewStack(prev => prev.slice(0, -1))
  }
  
  // Pass handlers to child modals
  const handlers = {
    onClose: onClose,           // Exit entire stack
    executeDetailCommand,       // Push to stack
    onBackToList: handleBack,   // Pop from stack
  }
}

// In child modal
function SprintListModal({ onCreate }) {
  return (
    <Button onClick={onCreate}>Create Sprint</Button>
  )
}
```

**Advantages:**
1. Single Dialog component at all times
2. ESC/Back properly managed by CommandResultModal
3. Clean navigation history
4. Centralized state management
5. Proper accessibility
6. Works with browser back button (future)

---

## Decision

**We will use Pattern B (Navigation Stack) exclusively.**

All modal navigation MUST go through the CommandResultModal's navigation stack system. Child modals MUST NOT render their own Dialog components for navigation.

---

## Implementation Rules

### Rule 1: No Nested Dialogs
❌ **NEVER DO THIS:**
```tsx
<Dialog>
  <Dialog>  {/* ← FORBIDDEN */}
    ...
  </Dialog>
</Dialog>
```

✅ **DO THIS:**
```tsx
// Child modal receives handler, calls command
<Button onClick={() => onCreate()}>Create</Button>
```

### Rule 2: Use Command Execution for Navigation
All navigation between modals happens via command execution:

```tsx
// In CommandResultModal - pass handlers to children
props.onCreate = () => handlers.executeDetailCommand!('/sprint-create')
props.onSprintSelect = (item) => handlers.executeDetailCommand!(`/sprint-detail ${item.code}`)

// In child modal - call the handler
function SprintListModal({ onCreate, onSprintSelect }) {
  return (
    <>
      <Button onClick={onCreate}>Create</Button>
      <SprintRow onClick={() => onSprintSelect(sprint)} />
    </>
  )
}
```

### Rule 3: Navigation Handlers
CommandResultModal provides these handlers to child modals:

| Handler | Purpose | When to Use |
|---------|---------|-------------|
| `onClose` | Exit entire modal stack | Close button, final cancel |
| `onBack` | Go back one level | Back button, ESC key (detail modals only) |
| `executeDetailCommand` | Navigate forward | View item, create item, any forward nav |

### Rule 4: Modal Responsibilities

**List Modals** (SprintListModal, TaskListModal):
- Display data in table/grid
- Accept navigation handlers as props
- Call handlers when user interacts
- NO local modal state for children

**Detail Modals** (SprintDetailModal, TaskDetailModal):
- Display single item details
- Accept `onBack` prop for ESC/Back button
- Call `onBack()` when user goes back
- Can call `executeDetailCommand` for sub-navigation

**Form Modals** (SprintFormModal, TaskFormModal):
- Accept `onBack` prop
- Call `onBack()` on success/cancel
- Use Dialog `onOpenChange` to respect ESC key
- NO nesting of other Dialogs

---

## Configuration

Navigation is configured in the `commands` table:

```sql
-- Sprint list command
INSERT INTO commands (command, navigation_config) VALUES (
  '/sprints',
  '{
    "detail_command": "/sprint-detail",
    "create_command": "/sprint-create",
    "item_key": "code"
  }'
);
```

CommandResultModal automatically wires handlers based on this config:

```tsx
if (navConfig.detail_command) {
  props.onItemSelect = (item) => 
    executeDetailCommand!(`${navConfig.detail_command} ${item[navConfig.item_key]}`)
}

if (navConfig.create_command) {
  props.onCreate = () => 
    executeDetailCommand!(navConfig.create_command)
}
```

---

## Migration Guide

### Converting Nested Dialog to Navigation Stack

**Before (Nested Dialog - BROKEN):**
```tsx
function SprintListModal({ isOpen, onClose }) {
  const [showCreate, setShowCreate] = useState(false)
  const [formData, setFormData] = useState(null)
  
  const handleCreate = async () => {
    const result = await fetch('/api/commands/execute', {
      body: JSON.stringify({ command: '/sprint-create' })
    })
    setFormData(result.data)
    setShowCreate(true)
  }
  
  return (
    <>
      <DataManagementModal isOpen={isOpen} onClose={onClose}>
        <Button onClick={handleCreate}>Create</Button>
      </DataManagementModal>
      
      {showCreate && (
        <SprintFormModal 
          isOpen={showCreate} 
          onClose={() => { setShowCreate(false); onRefresh() }}
          data={formData}
        />
      )}
    </>
  )
}
```

**After (Navigation Stack - CORRECT):**
```tsx
interface SprintListModalProps {
  isOpen: boolean
  onClose: () => void
  onCreate?: () => void  // ← Handler from CommandResultModal
  onRefresh?: () => void
}

function SprintListModal({ isOpen, onClose, onCreate }) {
  return (
    <DataManagementModal isOpen={isOpen} onClose={onClose}>
      <Button onClick={onCreate}>Create</Button>
    </DataManagementModal>
  )
}
```

**In CommandResultModal:**
```tsx
// Automatically added for Sprint modals
props.onCreate = () => handlers.executeDetailCommand!('/sprint-create')
```

---

## Testing Checklist

When implementing modal navigation:

- [ ] Click item → Detail opens
- [ ] ESC on detail → Returns to list
- [ ] Click create → Form opens  
- [ ] ESC on form → Returns to list
- [ ] Submit form → Returns to list with refreshed data
- [ ] Deep navigation (list → detail → sub-detail) → ESC works at each level
- [ ] No "ghost modals" or stale state
- [ ] No duplicate overlays
- [ ] Console shows correct navigation stack logs

---

## Consequences

### Positive
- Single source of truth for navigation state
- Consistent ESC/Back behavior across all modals
- Better accessibility
- Easier to debug (stack visible in logs)
- Future: Can add browser history integration
- Future: Can add "breadcrumb" UI

### Negative
- Requires command for every navigation action
- Child modals lose some autonomy
- Initial setup more complex

### Neutral
- All navigation must be registered in database
- CommandResultModal becomes more complex

---

## Examples in Codebase

### ✅ Correct Implementations
- Sprint List → Sprint Detail (`/sprints` → `/sprint-detail`)
- Sprint Detail → Task Detail (`/sprint-detail` → `/task-detail`)
- Task List → Task Detail (`/tasks` → `/task-detail`)

### ❌ Broken Implementations (To Fix)
- Sprint List → Sprint Create (was using nested Dialog)
- Any modal rendering child Dialogs

---

## Related Documentation

- `docs/frontend/MODAL_NAVIGATION_PATTERN.md` - Detailed implementation guide
- `delegation/backlog/modal-state-cleanup-bug.md` - Bug report that led to this ADR
- `resources/js/islands/chat/CommandResultModal.tsx` - Implementation
- ADR-003 - Config-Driven Navigation Handlers

---

## Revision History

- 2025-10-12: Initial version documenting navigation stack pattern
