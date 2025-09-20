# Toast Notification System

## Overview

The toast notification system provides severity-based messaging with user-configurable verbosity levels and automatic duplicate suppression to reduce noise in the chat interface.

## Features

### Severity-Based Styling
- **Success**: Green theme for successful operations
- **Error**: Red theme for error messages
- **Warning**: Amber theme for warning messages
- **Info**: Blue theme for informational messages

### Verbosity Controls
- **Minimal**: Only shows errors and warnings
- **Normal**: Shows all notification types (default)
- **Verbose**: Shows all notifications with additional details

### Duplicate Suppression
- Automatically suppresses duplicate success toasts within a 10-second window
- Error toasts are never suppressed (always shown)
- Uses cache-based deduplication per user session

## Usage

### Backend (PHP)

#### ToastService API

```php
use App\Services\ToastService;

$toastService = app(ToastService::class);

// Check if a toast should be shown based on user preferences
$shouldShow = $toastService->shouldShowToast(
    ToastService::SEVERITY_SUCCESS,
    auth()->user()
);

// Check for duplicate toasts
$isDuplicate = $toastService->isDuplicate(
    ToastService::SEVERITY_SUCCESS,
    'Operation completed successfully',
    auth()->user()
);
```

#### ChatInterface Integration

The `ChatInterface` class automatically handles verbosity and duplicate checking:

```php
// Show a success toast (respects user preferences and duplicate suppression)
$this->showSuccessToast(
    'Operation Complete',
    'The task was completed successfully.',
    'fragment',
    $fragmentId
);

// Show an error toast (always shown, no duplicate suppression)
$this->showErrorToast('An error occurred while processing your request.');
```

### Frontend (Blade/Alpine.js)

#### Unified Toast Component

```blade
<!-- Use pre-configured toast components -->
<x-success-toast />
<x-error-toast />
<x-undo-toast />

<!-- Or create custom toast -->
<x-toast
    variant="warning"
    toastId="custom-toast"
    title="Custom Warning"
    message="This is a custom warning message"
/>
```

#### JavaScript Integration

```javascript
// Get toast element and display a message
const toastElement = document.getElementById('success-toast');
if (toastElement && toastElement._x_dataStack) {
    toastElement._x_dataStack[0].display(
        'success',      // variant
        'Success!',     // title
        'Message text', // message
        [],            // actions
        5              // duration in seconds
    );
}
```

## User Settings

### Changing Toast Verbosity

Users can modify their toast preferences through the settings button in the chat interface:

1. Click the amber settings icon in the ribbon
2. Select desired verbosity level:
   - **Minimal**: Only errors and warnings
   - **Normal**: All notifications (recommended)
   - **Verbose**: All notifications with details
3. Settings are automatically saved to the user's profile

### Database Schema

The user's toast preference is stored in the `users` table:

```sql
ALTER TABLE users ADD COLUMN toast_verbosity VARCHAR(255) DEFAULT 'normal';
```

Possible values:
- `minimal`
- `normal` (default)
- `verbose`

## Architecture

### Components

1. **ToastService** (`app/Services/ToastService.php`)
   - Handles verbosity logic
   - Manages duplicate suppression
   - Provides configuration constants

2. **Unified Toast Component** (`resources/views/components/toast.blade.php`)
   - Alpine.js-powered component
   - Supports all severity types
   - Accessible markup with keyboard support

3. **ChatInterface Integration** (`app/Filament/Resources/FragmentResource/Pages/ChatInterface.php`)
   - Integrates ToastService into Livewire component
   - Provides user preference management
   - Handles toast display logic

### Styling

Toast styles are defined in `resources/css/app.css` using the existing synthwave color palette:

- Success: Emerald colors
- Error: Rose colors
- Warning: Amber colors
- Info: Blue colors

All toasts include:
- Severity-specific icons
- Glow effects matching the design system
- Smooth slide-up animations
- Keyboard accessibility (ESC to dismiss)

## Testing

### Unit Tests

```bash
vendor/bin/phpunit tests/Unit/ToastServiceTest.php
```

Tests cover:
- Verbosity preference handling
- Duplicate suppression logic
- Service configuration methods

### Feature Tests

```bash
vendor/bin/phpunit tests/Feature/ToastVerbosityFeatureTest.php
```

Tests cover:
- User preference updates
- Livewire component integration
- Settings UI functionality

## Migration Guide

### From Legacy Toast System

1. The old empty toast components (`success-toast.blade.php`, `error-toast.blade.php`, `undo-toast.blade.php`) now use the unified toast component
2. All existing JavaScript event listeners continue to work unchanged
3. Backend toast dispatch methods (`showSuccessToast`, `showErrorToast`) now include verbosity and duplicate checking

### No Breaking Changes

- All existing toast functionality continues to work
- JavaScript event names remain the same
- Component IDs are preserved for backward compatibility

## Configuration

### Constants (ToastService)

```php
// Verbosity levels
ToastService::VERBOSITY_MINIMAL   // 'minimal'
ToastService::VERBOSITY_NORMAL    // 'normal'
ToastService::VERBOSITY_VERBOSE   // 'verbose'

// Severity levels
ToastService::SEVERITY_SUCCESS    // 'success'
ToastService::SEVERITY_ERROR      // 'error'
ToastService::SEVERITY_WARNING    // 'warning'
ToastService::SEVERITY_INFO       // 'info'
```

### Customization

To modify duplicate suppression window (default 10 seconds):

```php
$toastService->isDuplicate($severity, $message, $user, 30); // 30 seconds
```

To add new verbosity levels, update:
1. `ToastService::getVerbosityOptions()`
2. `ToastService::shouldShowToast()` match statement
3. Database migration for new values
4. Settings UI options