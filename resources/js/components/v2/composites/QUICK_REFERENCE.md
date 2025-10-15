# Quick Reference - Menu & Dropdown Components

## DropdownMenu

```typescript
{
  id: 'my-dropdown',
  type: 'dropdown-menu',
  props: {
    trigger: ComponentConfig,
    items: MenuItemConfig[],
    align?: 'start' | 'center' | 'end',
    side?: 'top' | 'right' | 'bottom' | 'left'
  }
}
```

## ContextMenu

```typescript
{
  id: 'my-context',
  type: 'context-menu',
  props: {
    items: MenuItemConfig[]
  },
  children: ComponentConfig[]
}
```

## Menubar

```typescript
{
  id: 'my-menubar',
  type: 'menubar',
  props: {
    menus: Array<{
      label: string,
      items: MenuItemConfig[]
    }>
  }
}
```

## HoverCard

```typescript
{
  id: 'my-hover',
  type: 'hover-card',
  props: {
    trigger: ComponentConfig,
    content: ComponentConfig[],
    openDelay?: number,
    closeDelay?: number,
    side?: 'top' | 'right' | 'bottom' | 'left',
    align?: 'start' | 'center' | 'end'
  }
}
```

## MenuItem Types

```typescript
// Standard item
{ type: 'item', label: 'Copy', icon: 'Copy', shortcut: '⌘C' }

// Checkbox
{ type: 'checkbox', label: 'Show Toolbar', checked: true }

// Radio
{ type: 'radio', label: 'Light', value: 'light' }

// Separator
{ type: 'separator' }

// Label
{ type: 'label', label: 'Section' }

// Submenu
{ type: 'submenu', label: 'More', items: [...] }
```

## Actions

```typescript
// Command
action: { type: 'command', command: 'open:settings' }

// Navigate
action: { type: 'navigate', url: '/dashboard' }

// Event
action: { type: 'emit', event: 'user:logout' }

// HTTP
action: { type: 'http', url: '/api/action', method: 'POST' }
```
