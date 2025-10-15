# Tier 1C Feedback Components - Examples

Config-driven feedback components for UI Builder v2.

## Alert Component

### Basic Alert
```typescript
{
  id: 'alert-1',
  type: 'alert',
  props: {
    variant: 'default',
    description: 'This is an informational message.'
  }
}
```

### Alert with Title
```typescript
{
  id: 'alert-2',
  type: 'alert',
  props: {
    variant: 'success',
    title: 'Success',
    description: 'Your changes have been saved successfully.'
  }
}
```

### Warning Alert with Custom Icon
```typescript
{
  id: 'alert-3',
  type: 'alert',
  props: {
    variant: 'warning',
    title: 'Warning',
    description: 'This action may have unintended consequences.',
    icon: '⚠️'
  }
}
```

### Dismissible Error Alert
```typescript
{
  id: 'alert-4',
  type: 'alert',
  props: {
    variant: 'destructive',
    title: 'Error',
    description: 'Failed to save changes. Please try again.',
    dismissible: true
  }
}
```

## Progress Component

### Basic Progress Bar
```typescript
{
  id: 'progress-1',
  type: 'progress',
  props: {
    value: 45
  }
}
```

### Progress with Label
```typescript
{
  id: 'progress-2',
  type: 'progress',
  props: {
    value: 75,
    showLabel: true
  }
}
```

### Success Progress (Complete)
```typescript
{
  id: 'progress-3',
  type: 'progress',
  props: {
    value: 100,
    showLabel: true,
    variant: 'success'
  }
}
```

### Error Progress
```typescript
{
  id: 'progress-4',
  type: 'progress',
  props: {
    value: 30,
    variant: 'error',
    size: 'lg'
  }
}
```

### Small Progress Bar
```typescript
{
  id: 'progress-5',
  type: 'progress',
  props: {
    value: 60,
    size: 'sm'
  }
}
```

## Toast Component

### Info Toast
```typescript
{
  id: 'toast-1',
  type: 'toast',
  props: {
    title: 'Update Available',
    description: 'A new version is available for download.'
  }
}
```

### Success Toast
```typescript
{
  id: 'toast-2',
  type: 'toast',
  props: {
    variant: 'success',
    title: 'Saved',
    description: 'Your changes have been saved.'
  }
}
```

### Error Toast (Longer Duration)
```typescript
{
  id: 'toast-3',
  type: 'toast',
  props: {
    variant: 'destructive',
    title: 'Error',
    description: 'Failed to connect to server.',
    duration: 7000
  }
}
```

### Warning Toast
```typescript
{
  id: 'toast-4',
  type: 'toast',
  props: {
    variant: 'warning',
    title: 'Session Expiring',
    description: 'Your session will expire in 5 minutes.',
    duration: 10000
  }
}
```

## Empty State Component

### Basic Empty State
```typescript
{
  id: 'empty-1',
  type: 'empty',
  props: {
    title: 'No Results Found',
    description: 'Try adjusting your search criteria.'
  }
}
```

### Empty State with Custom Icon
```typescript
{
  id: 'empty-2',
  type: 'empty',
  props: {
    icon: '📭',
    title: 'No Messages',
    description: 'You don\'t have any messages yet.'
  }
}
```

### Empty State with Action Button
```typescript
{
  id: 'empty-3',
  type: 'empty',
  props: {
    icon: '📝',
    title: 'No Projects',
    description: 'Get started by creating your first project.',
    action: {
      id: 'create-btn',
      type: 'button',
      props: {
        label: 'Create Project',
        variant: 'default'
      },
      actions: {
        click: {
          type: 'command',
          command: 'project:create'
        }
      }
    }
  }
}
```

### Empty Search Results
```typescript
{
  id: 'empty-4',
  type: 'empty',
  props: {
    title: 'No search results',
    description: 'We couldn\'t find any matches for your search.',
    action: {
      id: 'clear-btn',
      type: 'button',
      props: {
        label: 'Clear Search',
        variant: 'outline'
      },
      actions: {
        click: {
          type: 'emit',
          event: 'search:clear'
        }
      }
    }
  }
}
```

## Combined Example: Loading State with Progress and Alert

```typescript
{
  id: 'upload-status',
  type: 'container',
  props: {
    className: 'space-y-4'
  },
  children: [
    {
      id: 'upload-progress',
      type: 'progress',
      props: {
        value: 67,
        showLabel: true,
        variant: 'default'
      }
    },
    {
      id: 'upload-alert',
      type: 'alert',
      props: {
        variant: 'default',
        title: 'Uploading',
        description: 'Please wait while your files are being uploaded...'
      }
    }
  ]
}
```

## Type Definitions

All components follow the config-driven pattern:

```typescript
interface AlertConfig {
  id: string;
  type: 'alert';
  props: {
    variant?: 'default' | 'destructive' | 'warning' | 'success';
    title?: string;
    description: string;
    icon?: string;
    dismissible?: boolean;
    className?: string;
  };
}

interface ProgressConfig {
  id: string;
  type: 'progress';
  props: {
    value: number; // 0-100
    showLabel?: boolean;
    variant?: 'default' | 'success' | 'error' | 'warning';
    size?: 'sm' | 'default' | 'lg';
    className?: string;
  };
}

interface ToastConfig {
  id: string;
  type: 'toast';
  props: {
    title: string;
    description?: string;
    variant?: 'default' | 'destructive' | 'success' | 'warning';
    duration?: number; // milliseconds
  };
}

interface EmptyConfig {
  id: string;
  type: 'empty';
  props: {
    icon?: string;
    title: string;
    description?: string;
    action?: ButtonConfig;
    className?: string;
  };
}
```

## ARIA & Accessibility

All components include proper ARIA attributes:

- **Alert**: `role="alert"`, `aria-live="polite|assertive"`
- **Progress**: `aria-valuenow`, `aria-valuemin`, `aria-valuemax`, `aria-label`
- **Toast**: Auto-dismiss, keyboard accessible close button
- **Empty**: `role="status"`, `aria-live="polite"`

## Responsive Design

All components are fully responsive and work across all screen sizes using Tailwind utility classes.
