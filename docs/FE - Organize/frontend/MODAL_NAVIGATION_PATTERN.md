# Modal Navigation Pattern

## Overview
This document describes the navigation pattern used for nested modals in the Fragments Engine UI. This pattern ensures proper "back" behavior when users press ESC or click X/Close buttons.

## The Problem
When you have nested modals (e.g., Sprint List → Sprint Create Form), the Dialog component's default behavior is to close the entire modal stack when the user presses ESC or clicks the X button. This is jarring for users who expect to go "back" to the previous modal, not exit entirely.

## The Solution
Use the `onBack` prop pattern to implement proper navigation stack behavior.

### Pattern Implementation

```tsx
// Parent Modal (e.g., SprintListModal)
export function ParentModal({ isOpen, onClose }) {
  const [showChildModal, setShowChildModal] = useState(false)
  
  const handleChildClose = () => {
    setShowChildModal(false)
    onRefresh?.() // Refresh parent data if needed
  }
  
  return (
    <>
      <DataManagementModal isOpen={isOpen} onClose={onClose}>
        {/* Parent content */}
        <Button onClick={() => setShowChildModal(true)}>Open Child</Button>
      </DataManagementModal>
      
      {showChildModal && (
        <ChildModal
          isOpen={showChildModal}
          onClose={onClose}        // Full close - exits entire stack
          onBack={handleChildClose} // Go back - returns to parent
        />
      )}
    </>
  )
}

// Child Modal (e.g., SprintFormModal)
interface ChildModalProps {
  isOpen: boolean
  onClose: () => void
  onBack?: () => void  // Optional - for nested modals
}

export function ChildModal({ isOpen, onClose, onBack }) {
  return (
    <Dialog 
      open={isOpen} 
      onOpenChange={(open) => {
        if (!open) {
          if (onBack) {
            onBack()  // Prefer onBack if provided
          } else {
            onClose() // Fall back to onClose
          }
        }
      }}
    >
      <DialogContent>
        {/* Form content */}
        <Button onClick={() => {
          // On successful action (e.g., save)
          if (onBack) {
            onBack()  // Go back to parent
          } else {
            onClose() // Or close entirely
          }
        }}>
          Submit
        </Button>
      </DialogContent>
    </Dialog>
  )
}
```

## Key Points

1. **Two Props**: Child modals should accept both `onClose` and `onBack`
   - `onClose`: Exits the entire modal stack
   - `onBack`: Returns to the previous modal in the stack

2. **Dialog onOpenChange**: Always check for `onBack` first
   ```tsx
   onOpenChange={(open) => {
     if (!open) {
       if (onBack) {
         onBack()
       } else {
         onClose()
       }
     }
   }}
   ```

3. **Parent Wiring**: Parent passes both callbacks
   ```tsx
   <ChildModal
     onClose={onClose}        // Pass through for full exit
     onBack={handleChildClose} // Local handler for back
   />
   ```

4. **Success Actions**: After successful operations (save, delete, etc.), prefer `onBack`
   ```tsx
   if (result.success) {
     if (onBack) {
       onBack()
     } else {
       onClose()
     }
   }
   ```

## Examples in Codebase

### Working Examples
- `TaskDetailModal` - Uses this pattern perfectly
- `SprintDetailModal` - Uses this pattern  
- `SprintFormModal` - Uses this pattern (as of 2025-10-12)

### Navigation Flow Example
```
/sprints (SprintListModal)
  ├─ ESC/X → closes modal (onClose)
  └─ Create Sprint → (SprintFormModal)
      ├─ ESC/X → back to list (onBack)
      ├─ Cancel → back to list (onBack)
      └─ Submit success → back to list (onBack)
```

## Common Mistakes

❌ **Don't do this:**
```tsx
<Dialog open={isOpen} onOpenChange={onClose}>
  {/* Always calls onClose, can't go back */}
</Dialog>
```

✅ **Do this instead:**
```tsx
<Dialog open={isOpen} onOpenChange={(open) => {
  if (!open) {
    if (onBack) {
      onBack()
    } else {
      onClose()
    }
  }
}}>
```

## Testing Checklist

When implementing a new nested modal, test:

- [ ] ESC key goes back (not exit)
- [ ] X button goes back (not exit)
- [ ] Cancel button goes back (not exit)
- [ ] Submit success goes back and refreshes parent
- [ ] Submit error stays on form with error message
- [ ] From parent modal, ESC/X closes entire stack

## Related Files
- `resources/js/components/orchestration/SprintFormModal.tsx`
- `resources/js/components/orchestration/SprintListModal.tsx`
- `resources/js/components/orchestration/TaskDetailModal.tsx`
- `resources/js/components/ui/DataManagementModal.tsx`
