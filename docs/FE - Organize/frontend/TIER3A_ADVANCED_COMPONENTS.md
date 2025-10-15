# Tier 3A Advanced Navigation Components

**Status**: ✅ Complete  
**Date**: 2025-10-15  
**Components**: NavigationMenu, Command, Combobox

## Overview

Built three advanced composite components for UI Builder v2 following Phase 1 & 2 patterns. These components are complex, config-driven, and provide Shadcn parity with enhanced functionality.

## Components Delivered

### 1. NavigationMenu Component

**Location**: `resources/js/components/v2/composites/NavigationMenuComponent.tsx`

**Features**:
- Horizontal/vertical orientation
- Mega menu support with rich content
- Nested item structures
- Icon support via Lucide
- Hover/click triggers (via Radix)
- Keyboard navigation
- ARIA compliant

**Config Structure**:
```typescript
{
  id: string;
  type: 'navigation-menu';
  props: {
    items: Array<{
      label: string;
      trigger?: 'hover' | 'click';
      content?: ComponentConfig[];  // Mega menu
      href?: string;
      items?: Array<{               // Simple items
        label: string;
        href: string;
        description?: string;
        icon?: string;
      }>;
    }>;
    orientation?: 'horizontal' | 'vertical';
    className?: string;
  };
}
```

**Capabilities**:
- `mega_menu` - Rich content layouts in dropdowns
- `hover_trigger` - Hover-activated menus
- `click_trigger` - Click-activated menus
- `nested_items` - Multi-level navigation
- `keyboard_navigation` - Full keyboard support
- `icons` - Icon support for items
- `descriptions` - Item descriptions
- `rich_content` - Arbitrary component content

### 2. Command Component

**Location**: `resources/js/components/v2/composites/CommandComponent.tsx`

**Features**:
- Command palette (cmdk)
- Fuzzy search built-in
- Keyboard shortcut display
- ⌘K to open (auto-registers)
- Grouped commands
- Dialog or inline mode
- Action support (navigate, emit, http, command)
- Portal rendering

**Config Structure**:
```typescript
{
  id: string;
  type: 'command';
  props: {
    placeholder?: string;
    emptyText?: string;
    groups: Array<{
      heading?: string;
      items: Array<{
        label: string;
        icon?: string;
        shortcut?: string;      // e.g., "⌘K", "⇧⌘N"
        value?: string;
        disabled?: boolean;
      }>;
    }>;
    open?: boolean;             // Controlled mode
    defaultOpen?: boolean;      // Dialog mode
    showShortcut?: boolean;
    className?: string;
  };
  actions?: {
    onSelect?: ActionConfig;
  };
}
```

**Capabilities**:
- `fuzzy_search` - Built-in search with cmdk
- `keyboard_shortcuts` - Display shortcuts
- `grouped_commands` - Organized command groups
- `icons` - Icon support
- `portal` - Portal rendering for dialogs
- `keyboard_navigation` - Full keyboard control
- `cmd_k_trigger` - Auto ⌘K trigger
- `actions` - Integrated action system

**Keyboard Shortcuts**:
- `⌘K` / `Ctrl+K` - Toggle command palette
- `↑` `↓` - Navigate items
- `Enter` - Select item
- `Esc` - Close

### 3. Combobox Component

**Location**: `resources/js/components/v2/composites/ComboboxComponent.tsx`

**Features**:
- Searchable select dropdown
- Autocomplete filtering
- Icon support
- Checkmark for selected item
- Disabled options
- Action support (onChange)
- Popover positioning
- Keyboard navigation

**Config Structure**:
```typescript
{
  id: string;
  type: 'combobox';
  props: {
    placeholder?: string;
    emptyText?: string;
    searchPlaceholder?: string;
    options: Array<{
      value: string;
      label: string;
      icon?: string;
      disabled?: boolean;
    }>;
    value?: string;
    defaultValue?: string;
    searchable?: boolean;
    disabled?: boolean;
    className?: string;
  };
  actions?: {
    onChange?: ActionConfig;
  };
}
```

**Capabilities**:
- `searchable` - Filter options by typing
- `autocomplete` - Real-time filtering
- `keyboard_navigation` - Arrow keys + Enter
- `icons` - Option icons
- `disabled_options` - Disable specific options
- `actions` - onChange action support
- `popover` - Popover positioning

## Registry Integration

All components registered in `ComponentRegistry.ts`:

```typescript
export function registerCompositeComponents() {
  // ... existing composites ...
  
  import('./composites/NavigationMenuComponent').then(({ NavigationMenuComponent }) => {
    registry.register('navigation-menu', NavigationMenuComponent as ComponentRenderer);
  });

  import('./composites/CommandComponent').then(({ CommandComponent }) => {
    registry.register('command', CommandComponent as ComponentRenderer);
  });

  import('./composites/ComboboxComponent').then(({ ComboboxComponent }) => {
    registry.register('combobox', ComboboxComponent as ComponentRenderer);
  });
}
```

## Type Definitions

Added to `resources/js/components/v2/types.ts`:
- `NavigationMenuConfig`
- `CommandConfig`
- `ComboboxConfig`

## Database Seeder

Updated `CompositeComponentSeeder.php` with:
- `component.navigation-menu.default`
- `component.command.default`
- `component.combobox.default`

Each includes full schema, defaults, and capabilities.

## Example Configurations

### Navigation Menu Example
`resources/js/components/v2/examples/composites/navigation-menu-example.json`

Multi-level navigation with:
- Getting Started section (3 items)
- Components section (4 items)
- Direct links (Resources, Examples)
- Icons and descriptions

### Command Palette Example
`resources/js/components/v2/examples/composites/command-example.json`

Organized into 3 groups:
- Suggestions (Calendar, Emoji, Calculator)
- Settings (Profile, Billing, Settings)
- Actions (New Document, New Folder, Sign Out)

All with keyboard shortcuts (⌘C, ⌘P, ⌘N, etc.)

### Combobox Example
`resources/js/components/v2/examples/composites/combobox-example.json`

Framework selector with:
- 8 popular frameworks
- Icons for each option
- Searchable/filterable
- onChange event emission

## Technical Implementation

### NavigationMenu
- Uses Shadcn NavigationMenu (Radix UI)
- Supports both simple links and mega menus
- Grid layouts for mega menu content
- Icon integration via Lucide
- Renders arbitrary child components in mega menu

### Command
- Uses Shadcn Command (cmdk)
- Auto-registers ⌘K listener
- Supports dialog and inline modes
- Action system integration
- Fuzzy search built-in
- CommandShortcut display

### Combobox
- Uses Shadcn Command + Popover + Button
- Checkmark indicator for selection
- Icon support in trigger and options
- Action system integration
- Controlled/uncontrolled modes
- Width customizable via className

## Action System Integration

All components support the standard ActionConfig:

```typescript
interface ActionConfig {
  type: 'command' | 'navigate' | 'emit' | 'http';
  command?: string;
  url?: string;
  event?: string;
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
  payload?: Record<string, any>;
}
```

- **Command**: Execute onSelect with value
- **Combobox**: Execute onChange with value

## Build Status

✅ TypeScript compilation successful  
✅ Vite build successful  
✅ No type errors  
✅ All imports resolved

## Usage

### NavigationMenu
```json
{
  "id": "main-nav",
  "type": "navigation-menu",
  "props": {
    "items": [
      {
        "label": "Products",
        "items": [
          {
            "label": "Analytics",
            "href": "/products/analytics",
            "description": "Real-time analytics dashboard",
            "icon": "BarChart"
          }
        ]
      }
    ]
  }
}
```

### Command (Dialog Mode)
```json
{
  "id": "app-command",
  "type": "command",
  "props": {
    "defaultOpen": false,
    "groups": [
      {
        "heading": "Actions",
        "items": [
          {
            "label": "New File",
            "icon": "FileText",
            "shortcut": "⌘N"
          }
        ]
      }
    ]
  },
  "actions": {
    "onSelect": {
      "type": "emit",
      "event": "command:execute"
    }
  }
}
```

### Combobox
```json
{
  "id": "framework-select",
  "type": "combobox",
  "props": {
    "placeholder": "Select framework...",
    "options": [
      { "value": "react", "label": "React", "icon": "Atom" },
      { "value": "vue", "label": "Vue", "icon": "Component" }
    ]
  },
  "actions": {
    "onChange": {
      "type": "http",
      "url": "/api/preferences",
      "method": "POST"
    }
  }
}
```

## Testing

To test the components:

1. Run seeder:
   ```bash
   php artisan db:seed --class=CompositeComponentSeeder
   ```

2. Load examples in UI Builder

3. Test keyboard shortcuts:
   - NavigationMenu: Tab, Arrow keys, Enter, Esc
   - Command: ⌘K to open, Arrow keys, Enter, Esc
   - Combobox: Space to open, Type to filter, Arrow keys, Enter

## Next Steps

These components complete Tier 3A of the UI Builder v2 roadmap. They provide:
- Advanced navigation patterns
- Command palette functionality
- Enhanced select/autocomplete

They integrate with the existing action system and can be composed with all other v2 components.

## Related Components

**Phase 1 (Primitives)**: Button, Input, Badge, Avatar, Typography, etc.  
**Phase 2 (Layouts)**: Card, Accordion, Tabs, Sidebar  
**Phase 2 (Overlays)**: Dialog, Popover, Tooltip, Sheet, Drawer  
**Phase 3A (Advanced)**: NavigationMenu, Command, Combobox ← **New**

## Files Created/Modified

**Created**:
- `resources/js/components/v2/composites/NavigationMenuComponent.tsx`
- `resources/js/components/v2/composites/CommandComponent.tsx`
- `resources/js/components/v2/composites/ComboboxComponent.tsx`
- `resources/js/components/ui/navigation-menu.tsx` (Shadcn)
- `resources/js/components/v2/examples/composites/navigation-menu-example.json`
- `resources/js/components/v2/examples/composites/command-example.json`
- `resources/js/components/v2/examples/composites/combobox-example.json`

**Modified**:
- `resources/js/components/v2/types.ts` (added 3 configs)
- `resources/js/components/v2/ComponentRegistry.ts` (registered 3 components)
- `database/seeders/CompositeComponentSeeder.php` (added 3 entries)
