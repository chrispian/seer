# Composite Components - Tier 3A: Menu & Dropdown

This directory contains menu and dropdown composite components for UI Builder v2.

## Components

### 1. DropdownMenu
**Purpose**: Dropdown menu with nested items, checkboxes, radio groups, and actions.

**Features**:
- Trigger component (typically a button)
- Multiple item types (item, checkbox, radio, separator, label, submenu)
- Icon support via lucide-react
- Keyboard shortcuts display
- Nested submenus
- Action handlers (command, navigate, emit, http)
- Alignment and positioning options

**Config**:
```typescript
{
  id: 'dropdown-1',
  type: 'dropdown-menu',
  props: {
    trigger: ComponentConfig,
    items: MenuItemConfig[],
    align?: 'start' | 'center' | 'end',
    side?: 'top' | 'right' | 'bottom' | 'left',
    className?: string
  }
}
```

**Example**:
```typescript
{
  id: 'user-menu',
  type: 'dropdown-menu',
  props: {
    trigger: {
      id: 'user-btn',
      type: 'button',
      props: { label: 'Account', variant: 'outline' }
    },
    items: [
      { type: 'item', label: 'Profile', icon: 'User', shortcut: '⌘P' },
      { type: 'item', label: 'Settings', icon: 'Settings' },
      { type: 'separator' },
      { type: 'item', label: 'Logout', icon: 'LogOut' }
    ]
  }
}
```

### 2. ContextMenu
**Purpose**: Right-click context menu for contextual actions.

**Features**:
- Right-click trigger on any element
- Same item types as DropdownMenu
- Icon and shortcut support
- Nested submenus
- Action handlers

**Config**:
```typescript
{
  id: 'context-1',
  type: 'context-menu',
  props: {
    items: MenuItemConfig[],
    className?: string
  },
  children?: ComponentConfig[]
}
```

**Example**:
```typescript
{
  id: 'file-context',
  type: 'context-menu',
  props: {
    items: [
      { type: 'item', label: 'Copy', icon: 'Copy', shortcut: '⌘C' },
      { type: 'item', label: 'Delete', icon: 'Trash' }
    ]
  },
  children: [
    { id: 'card', type: 'card', props: { title: 'Right-click me' } }
  ]
}
```

### 3. Menubar
**Purpose**: Application-style menu bar (File, Edit, View, etc.)

**Features**:
- Multiple top-level menus
- Same item types as DropdownMenu
- Icon and shortcut support
- Nested submenus
- Action handlers
- Keyboard navigation

**Config**:
```typescript
{
  id: 'menubar-1',
  type: 'menubar',
  props: {
    menus: Array<{
      label: string,
      items: MenuItemConfig[]
    }>,
    className?: string
  }
}
```

**Example**:
```typescript
{
  id: 'app-menu',
  type: 'menubar',
  props: {
    menus: [
      {
        label: 'File',
        items: [
          { type: 'item', label: 'New', icon: 'FilePlus', shortcut: '⌘N' },
          { type: 'item', label: 'Save', icon: 'Save', shortcut: '⌘S' }
        ]
      },
      {
        label: 'Edit',
        items: [
          { type: 'item', label: 'Undo', icon: 'Undo', shortcut: '⌘Z' },
          { type: 'item', label: 'Redo', icon: 'Redo' }
        ]
      }
    ]
  }
}
```

### 4. HoverCard
**Purpose**: Rich content displayed on hover (like tooltip but more complex).

**Features**:
- Trigger component
- Rich content area (any components)
- Configurable open/close delays
- Positioning options
- Smooth animations

**Config**:
```typescript
{
  id: 'hover-1',
  type: 'hover-card',
  props: {
    trigger: ComponentConfig,
    content: ComponentConfig[],
    openDelay?: number,
    closeDelay?: number,
    side?: 'top' | 'right' | 'bottom' | 'left',
    align?: 'start' | 'center' | 'end',
    className?: string
  }
}
```

**Example**:
```typescript
{
  id: 'user-hover',
  type: 'hover-card',
  props: {
    trigger: {
      id: 'username',
      type: 'badge',
      props: { text: '@username' }
    },
    content: [
      { id: 'avatar', type: 'avatar', props: { src: '...', fallback: 'U' } },
      { id: 'name', type: 'typography.h4', props: { text: 'User Name' } },
      { id: 'bio', type: 'typography.p', props: { text: 'Bio text...' } }
    ],
    openDelay: 200
  }
}
```

## Menu Item Types

All menu components (DropdownMenu, ContextMenu, Menubar) support these item types:

```typescript
type MenuItemConfig = {
  type: 'item' | 'checkbox' | 'radio' | 'separator' | 'label' | 'submenu';
  label?: string;
  icon?: string;          // Lucide icon name
  shortcut?: string;      // Keyboard shortcut display
  disabled?: boolean;
  checked?: boolean;      // For checkbox/radio
  value?: string;         // For radio items
  items?: MenuItemConfig[]; // For submenu
  action?: ActionConfig;  // Action to perform
}
```

## Actions

Menu items can trigger actions:

```typescript
action: {
  type: 'command',
  command: 'open:settings'
}

action: {
  type: 'navigate',
  url: '/dashboard'
}

action: {
  type: 'emit',
  event: 'user:logout',
  payload: { reason: 'manual' }
}

action: {
  type: 'http',
  url: '/api/action',
  method: 'POST',
  payload: { id: 123 }
}
```

## Keyboard Support

- **DropdownMenu**: Enter/Space to open, Arrow keys to navigate, Esc to close
- **ContextMenu**: Right-click to open, Arrow keys to navigate, Esc to close
- **Menubar**: Arrow keys to navigate menus, Enter to select, Esc to close
- **HoverCard**: Hover to open, move away to close (with delays)

## Usage with UI Builder

All components are registered in the ComponentRegistry and can be used via JSON config:

```typescript
import { renderComponent } from '@/components/v2/ComponentRegistry';

const config = {
  id: 'my-dropdown',
  type: 'dropdown-menu',
  props: { /* ... */ }
};

const element = renderComponent(config);
```

## Database Integration

Use `CompositeComponentSeeder` to seed these components in the database:

```php
php artisan db:seed --class=CompositeComponentSeeder
```

## Examples

See `examples.ts` for complete working examples of all 4 components.
