# Navigation Components (Tier 2B)

Config-driven navigation components for UI Builder v2.

## Components

### 1. Tabs
Tabbed navigation with content panels using Shadcn Tabs.

**Features:**
- Multiple tabs with individual content
- Nested component support in each tab
- Disabled tab support
- Custom styling

**Example:**
```typescript
{
  id: 'tabs-1',
  type: 'tabs',
  props: {
    defaultValue: 'overview',
    tabs: [
      {
        value: 'overview',
        label: 'Overview',
        content: [/* ComponentConfig[] */]
      }
    ]
  }
}
```

### 2. Breadcrumb
Navigation breadcrumb trail with customizable separators.

**Features:**
- Multiple separator styles (chevron, slash, none)
- Current page highlighting
- Link support for non-current items
- Accessible navigation

**Example:**
```typescript
{
  id: 'breadcrumb-1',
  type: 'breadcrumb',
  props: {
    items: [
      { label: 'Home', href: '/' },
      { label: 'Products', href: '/products' },
      { label: 'Laptops', current: true }
    ],
    separator: 'chevron'
  }
}
```

### 3. Pagination
Page navigation with numbers, prev/next, and first/last buttons.

**Features:**
- Page number buttons with ellipsis for large ranges
- Optional first/last page buttons
- Optional previous/next buttons
- Event emission on page change
- Smart page number display (shows relevant range)
- Configurable max visible pages

**Example:**
```typescript
{
  id: 'pagination-1',
  type: 'pagination',
  props: {
    currentPage: 1,
    totalPages: 10,
    showFirstLast: true,
    showPrevNext: true,
    onPageChange: {
      type: 'emit',
      event: 'page:changed',
      payload: {}
    }
  }
}
```

### 4. Sidebar
Collapsible side navigation with nested items using Shadcn Sidebar.

**Features:**
- Collapsible (icon mode) or fixed
- Nested menu items
- Icon support (Lucide icons)
- Badge support
- Active state highlighting
- Grouped navigation
- Left/right positioning
- Multiple variants (sidebar, floating, inset)

**Example (Simple):**
```typescript
{
  id: 'sidebar-1',
  type: 'sidebar',
  props: {
    collapsible: true,
    defaultOpen: true,
    items: [
      {
        label: 'Dashboard',
        icon: 'Home',
        href: '/dashboard',
        active: true
      }
    ]
  }
}
```

**Example (Grouped):**
```typescript
{
  id: 'sidebar-2',
  type: 'sidebar',
  props: {
    groups: [
      {
        label: 'Main',
        items: [
          { label: 'Dashboard', icon: 'Home', href: '/dashboard' }
        ]
      }
    ]
  }
}
```

## Usage

Import and register the navigation components:

```typescript
import { registerNavigationComponents } from './ComponentRegistry';

registerNavigationComponents();
```

## Event Handling

Navigation components emit events for user interactions:

- **Pagination**: `onPageChange` action with page number in event detail
  ```javascript
  window.addEventListener('page:changed', (e) => {
    console.log('New page:', e.detail.page);
  });
  ```

## Accessibility

All navigation components follow accessibility best practices:
- Proper ARIA labels and roles
- Keyboard navigation support
- Current page/active state indication
- Screen reader friendly
