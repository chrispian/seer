# Error Dialog Migration Guide

## Overview

We've created a reusable `ErrorDialog` component to replace native `alert()` calls with a proper UI component.

## Usage

### Basic Usage

```tsx
import { useErrorDialog } from '@/hooks/useErrorDialog'

function MyComponent() {
  const { showError, ErrorDialog } = useErrorDialog()

  const handleError = () => {
    showError('Something went wrong!')
  }

  return (
    <>
      <button onClick={handleError}>Trigger Error</button>
      {ErrorDialog}
    </>
  )
}
```

### With Details

```tsx
const { showError, ErrorDialog } = useErrorDialog()

try {
  await somethingRisky()
} catch (error) {
  showError(
    'Failed to save data',
    error instanceof Error ? error.message : 'Unknown error',
    'Save Error'
  )
}

return <>{ErrorDialog}</>
```

## Migration Examples

### Before (using alert)
```tsx
alert('Failed to submit form');
```

### After (using ErrorDialog)
```tsx
const { showError, ErrorDialog } = useErrorDialog()

// In component
showError('Failed to submit form')

// In JSX
return (
  <>
    {/* your component */}
    {ErrorDialog}
  </>
)
```

### With Try-Catch

**Before:**
```tsx
try {
  const response = await fetch('/api/data')
  if (!response.ok) {
    alert('Failed to load data')
  }
} catch (err) {
  alert('Error loading data')
}
```

**After:**
```tsx
const { showError, ErrorDialog } = useErrorDialog()

try {
  const response = await fetch('/api/data')
  if (!response.ok) {
    const errorData = await response.json()
    showError('Failed to load data', errorData.message)
  }
} catch (err) {
  showError(
    'Error loading data',
    err instanceof Error ? err.stack : undefined
  )
}

return <>{ErrorDialog}</>
```

## Files to Update

Based on search results, these files use `alert()`:

- `resources/js/components/channels/ChannelListModal.tsx`
- `resources/js/components/fragments/FragmentListModal.tsx`
- `resources/js/components/orchestration/TaskListModal.tsx`
- `resources/js/components/v2/CommandHandler.ts`
- `resources/js/components/v2/advanced/DataTableComponent.tsx`
- `resources/js/islands/chat/CommandResultModal.tsx`

## Benefits

1. ✅ **Consistent UI** - Matches application design
2. ✅ **Better UX** - Non-blocking, styled properly
3. ✅ **More Info** - Can show details, stack traces
4. ✅ **Accessible** - Proper ARIA labels, keyboard nav
5. ✅ **Testable** - Can be tested unlike native alerts
