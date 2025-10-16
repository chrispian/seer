# UI Builder: Slot-Based Layout Architecture

**Date:** October 16, 2025  
**Status:** Core Architecture Document

## Overview

The Seer UI Builder uses a **slot-based, composable architecture** where pages are built from nested layout components. This document explains the fundamental architecture that makes the system truly config-driven and composable.

## The Problem (What We're Fixing)

### ❌ Incorrect Implementation (Flat Array)

```json
{
  "id": "page.agent.table.modal",
  "overlay": "modal",
  "title": "Agents",
  "components": [
    { "type": "search.bar", "id": "search" },
    { "type": "data-table", "id": "table" }
  ]
}
```

**Problems:**
- Components are siblings in a flat array
- No explicit layout container
- Can't easily swap search for a different header
- Layout is hardcoded in PageRenderer (`<div className="space-y-4">`)
- Not composable or flexible

### ✅ Correct Implementation (Nested Slots)

```json
{
  "id": "page.agent.table.modal",
  "overlay": "modal",
  "title": "Agents",
  "layout": {
    "type": "rows",
    "id": "root-layout",
    "children": [
      {
        "type": "search.bar",
        "id": "header-slot",
        "dataSource": "Agent",
        "props": { "placeholder": "Search agents..." }
      },
      {
        "type": "data-table",
        "id": "content-slot",
        "dataSource": "Agent",
        "props": { "columns": [...] }
      }
    ]
  }
}
```

**Benefits:**
- Explicit layout component (`rows`)
- Components are children of the layout
- Swapping search → navbar is just changing the first child
- Fully composable and config-driven
- Can nest layouts infinitely

---

## Architecture Principles

### 1. Everything is a Component

There are NO special cases. Layouts, primitives, composites - all are components that:
- Accept a `config` prop
- Can have `children` (optional)
- Render based on config
- Are registered in ComponentRegistry

### 2. Layouts Define Structure

Layout components (`rows`, `columns`, `grid`, etc.) define **how** children are arranged:

```tsx
// RowsLayout.tsx
export function RowsLayout({ config, children }: LayoutProps) {
  return <div className="flex flex-col gap-4">{children}</div>
}
```

They don't care **what** the children are - just how to arrange them.

### 3. Pages Have a Root Layout

Every page has a single root layout component:

```
Page
  └─ Layout (rows)
       ├─ Component A (search)
       └─ Component B (table)
```

The root layout's children are rendered recursively.

### 4. Slots are Named Children

Slots are just positions in the children array. You can think of them as "named children":

```json
{
  "layout": {
    "type": "rows",
    "children": [
      { ... },  // Slot 0: "header"
      { ... },  // Slot 1: "content"
      { ... }   // Slot 2: "footer"
    ]
  }
}
```

To swap components, just change what's in that position.

---

## Component Types

### Layout Components (Container)

**Purpose:** Arrange children spatially

**Examples:**
- `rows` - Vertical stack
- `columns` - Horizontal row
- `grid` - CSS Grid
- `card` - Card wrapper
- `tabs` - Tabbed interface

**Key Trait:** Accept `children` prop

```tsx
interface LayoutProps {
  config: ComponentConfig
  children?: React.ReactNode
}
```

### Leaf Components (Content)

**Purpose:** Render actual content

**Examples:**
- `search.bar` - Search input
- `data-table` - Data table
- `button` - Button
- `text` - Text content

**Key Trait:** No children prop (they're leaves in the tree)

```tsx
interface LeafProps {
  config: ComponentConfig
}
```

---

## How Rendering Works

### PageRenderer Flow

```
1. Fetch page config from API
2. Find root layout component from config.layout
3. Recursively render:
   - Get component from registry
   - If it has children, render them recursively
   - If it's a leaf, just render it
4. Return the tree
```

### Recursive Rendering

```tsx
const renderComponent = (componentConfig: ComponentConfig): React.ReactNode => {
  const Component = componentRegistry.get(componentConfig.type)
  
  if (!Component) return null
  
  // If component has children, render them recursively
  const children = componentConfig.children?.map(child => 
    renderComponent(child)
  )
  
  return (
    <Component key={componentConfig.id} config={componentConfig}>
      {children}
    </Component>
  )
}
```

---

## Schema Structure

### Page Config Schema

```typescript
interface PageConfig {
  id: string                    // Page identifier
  title: string                 // Page title
  overlay?: 'modal' | 'drawer'  // Optional overlay type
  layout: ComponentConfig       // Root layout component
}
```

### Component Config Schema

```typescript
interface ComponentConfig {
  id: string                     // Unique component ID
  type: string                   // Component type (e.g., 'rows', 'search.bar')
  props?: Record<string, any>    // Component-specific props
  dataSource?: string            // Optional data source
  children?: ComponentConfig[]   // Optional nested children
  actions?: Record<string, any>  // Optional actions/events
}
```

---

## Real-World Examples

### Example 1: Simple Modal with Search and Table

```json
{
  "id": "page.agent.table.modal",
  "title": "Agents",
  "overlay": "modal",
  "layout": {
    "type": "rows",
    "id": "root",
    "children": [
      {
        "type": "search.bar",
        "id": "search",
        "dataSource": "Agent",
        "props": {
          "placeholder": "Search agents..."
        },
        "result": {
          "target": "table"
        }
      },
      {
        "type": "data-table",
        "id": "table",
        "dataSource": "Agent",
        "props": {
          "columns": [
            { "key": "name", "label": "Name", "sortable": true },
            { "key": "status", "label": "Status", "filterable": true }
          ]
        }
      }
    ]
  }
}
```

**Renders as:**
```
Modal
  └─ Rows Layout
       ├─ Search Bar
       └─ Data Table
```

### Example 2: Swap Search for Navbar

Just change the first child:

```json
{
  "layout": {
    "type": "rows",
    "id": "root",
    "children": [
      {
        "type": "navbar",
        "id": "header",
        "props": {
          "title": "Agents",
          "actions": [...]
        }
      },
      {
        "type": "data-table",
        "id": "table",
        "dataSource": "Agent",
        "props": { ... }
      }
    ]
  }
}
```

**No code changes required!** Just update the config.

### Example 3: Complex Nested Layout

```json
{
  "layout": {
    "type": "rows",
    "id": "root",
    "children": [
      {
        "type": "navbar",
        "id": "header"
      },
      {
        "type": "columns",
        "id": "main-content",
        "children": [
          {
            "type": "sidebar",
            "id": "sidebar",
            "props": { "width": "250px" }
          },
          {
            "type": "rows",
            "id": "content-area",
            "children": [
              { "type": "breadcrumb", "id": "breadcrumb" },
              { "type": "data-table", "id": "table" }
            ]
          }
        ]
      },
      {
        "type": "footer",
        "id": "footer"
      }
    ]
  }
}
```

**Renders as:**
```
Rows Layout
  ├─ Navbar
  ├─ Columns Layout
  │    ├─ Sidebar
  │    └─ Rows Layout
  │         ├─ Breadcrumb
  │         └─ Data Table
  └─ Footer
```

Infinite nesting, fully config-driven!

---

## Implementation Checklist

### Phase 1: Core Architecture ✅

- [x] Create layout components (RowsLayout, ColumnsLayout)
- [x] Define ComponentConfig interface with children support
- [x] Create ComponentRegistry

### Phase 2: Rendering Engine ✅

- [x] Update PageRenderer to use `config.layout` instead of `config.components`
- [x] Implement recursive rendering with children support
- [x] Register layout components in ComponentRegistry
- [x] Handle nested component props properly
- [x] Update V2ShellPage to use slot-based architecture
- [x] Add children support to ComponentRenderer

### Phase 3: Migration ✅

- [x] Update page config schema in database (renamed `layout_tree_json` → `config`)
- [x] Migrate existing pages to new nested structure
- [x] Update seeders to generate nested configs
- [x] Remove duplicate V2ShellPage.tsx from components/v2/

### Phase 4: Documentation & Testing (In Progress)

- [x] Document all available layout components
- [x] Create component composition examples
- [ ] Write tests for nested rendering
- [ ] Create visual component library
- [ ] Test component swapping in slots

---

## Component Communication

### Event-Based Communication

Components communicate via custom events:

```typescript
// Search bar emits
window.dispatchEvent(new CustomEvent('component:search', {
  detail: {
    target: 'table',  // ID of target component
    search: 'query'
  }
}))

// Table listens
window.addEventListener('component:search', (event) => {
  if (event.detail.target === config.id) {
    // Update search term
  }
})
```

**Key Points:**
- Events use component IDs for targeting
- No hard coupling between components
- Works across any nesting level

### SlotBinder (Advanced)

For more complex state management:

```typescript
slotBinder.bind('table', { refresh: true })
slotBinder.update('table', { filters: {...} })
```

---

## Best Practices

### 1. Start with Layout

Always define your page layout first:
```json
{
  "layout": {
    "type": "rows",  // or columns, grid, etc.
    "children": []
  }
}
```

### 2. Use Semantic IDs

Give components meaningful IDs:
```json
{ "id": "header-search" }      // ✅ Good
{ "id": "component-1" }         // ❌ Bad
```

### 3. Keep Layouts Simple

Prefer shallow nesting:
```json
// ✅ Good
rows -> [search, table]

// ❌ Avoid unless necessary
rows -> columns -> rows -> card -> table
```

### 4. Leverage Component Communication

Use events for cross-component interaction:
```json
{
  "type": "search.bar",
  "result": {
    "target": "my-table"  // References other component's ID
  }
}
```

---

## Common Patterns

### Pattern 1: Header + Content

```json
{
  "layout": {
    "type": "rows",
    "children": [
      { "type": "header", ... },
      { "type": "content", ... }
    ]
  }
}
```

### Pattern 2: Sidebar + Main

```json
{
  "layout": {
    "type": "columns",
    "children": [
      { "type": "sidebar", "props": { "width": "250px" } },
      { "type": "main-content", ... }
    ]
  }
}
```

### Pattern 3: Card Container

```json
{
  "layout": {
    "type": "card",
    "props": { "title": "My Card" },
    "children": [
      { "type": "content", ... }
    ]
  }
}
```

---

## Migration Guide

### Before (Flat Array)
```json
{
  "components": [
    { "type": "search.bar", ... },
    { "type": "data-table", ... }
  ]
}
```

### After (Nested Layout)
```json
{
  "layout": {
    "type": "rows",
    "children": [
      { "type": "search.bar", ... },
      { "type": "data-table", ... }
    ]
  }
}
```

**Steps:**
1. Create a root layout object
2. Move components into `layout.children`
3. Remove top-level `components` array
4. Update PageRenderer to use `config.layout`

---

## FAQ

### Q: Can I nest layouts infinitely?

**A:** Yes! Layouts can contain other layouts. Common pattern:
```
rows -> columns -> rows -> content
```

### Q: How do I swap components?

**A:** Just change the component config at that position:
```json
// Before
{ "type": "search.bar", ... }

// After
{ "type": "navbar", ... }
```

### Q: Do all components support children?

**A:** No. Only layout components support children. Leaf components (search, button, etc.) don't.

### Q: Can components communicate across nesting levels?

**A:** Yes! Components use IDs for communication, which work at any nesting level.

### Q: How do I know if a component is a layout?

**A:** Check the component's TypeScript interface. If it accepts `children`, it's a layout.

---

## Implementation Summary (October 16, 2025)

### Problem Identified: Duplicate V2ShellPage Files

**Root Cause:** Two V2ShellPage.tsx files existed with different implementations:
- `/resources/js/v2/V2ShellPage.tsx` - **Actual entrypoint** used by the app
- `/resources/js/components/v2/V2ShellPage.tsx` - Demo component (now deleted)

The actual entrypoint was looking for `layout_tree_json` but the API now returns `config` with nested `layout` objects.

**Resolution:**
- Updated `/resources/js/v2/V2ShellPage.tsx` to use slot-based architecture
- Deleted duplicate demo file to prevent confusion
- Updated seeders to use `config` column instead of `layout_tree_json`

### Files Modified

**TypeScript/React:**
1. `/resources/js/components/v2/types.ts` - Added `PageConfig` with `layout` property
2. `/resources/js/v2/V2ShellPage.tsx` - Updated to use `config.layout` instead of `layout_tree_json`
3. `/resources/js/v2/ComponentRenderer.tsx` - Added `children?: React.ReactNode` support
4. `/resources/js/components/v2/V2ShellPage.tsx` - **DELETED** (duplicate demo file)
5. `/resources/js/v2/registerCoreComponents.ts` - **NEW**: Eager synchronous registration for critical components
6. `/resources/js/v2/main.tsx` - Added `registerCoreComponents()` call before async registrations
7. `/resources/js/components/v2/ComponentRegistry.ts` - Added `table` alias for `data-table`

**Backend/Database:**
1. `database/seeders/V2UiBuilderSeeder.php` - Changed `layout_tree_json` → `config`
2. `modules/UiBuilder/database/seeders/V2UiBuilderSeeder.php` - Changed `layout_tree_json` → `config`
3. `delegation/tasks/ui-builder/frontend/page.agent.table.modal.json` - Converted to slot-based structure

**Previously Modified (Earlier Session):**
- `modules/UiBuilder/app/Models/Page.php` - Uses `config` column
- `app/Http/Controllers/V2/UiPageController.php` - Returns `$page->config`
- Database migration - Column renamed `layout_tree_json` → `config`

### Complete Architecture Flow

```
Database (fe_ui_pages.config column)
    ↓ JSON stored as: { "id": "...", "layout": { "type": "rows", "children": [...] } }
    ↓
Eloquent Model (Modules\UiBuilder\app\Models\Page)
    ↓ Returns config as array via $casts
    ↓
API Controller (app/Http/Controllers/V2/UiPageController)
    ↓ GET /api/v2/ui/pages/{key} returns $page->config
    ↓
React Route (/v2/pages/{key})
    ↓ Fetches from API
    ↓
V2ShellPage.tsx (resources/js/v2/V2ShellPage.tsx)
    ↓ Reads config.layout and renders recursively
    ↓
ComponentRenderer.tsx
    ↓ Looks up component type in ComponentRegistry
    ↓ Passes children to layout components
    ↓
RowsLayout / ColumnsLayout
    ↓ Renders children with proper spacing
    ↓
Nested Components (SearchBar, DataTable, etc.)
    ✓ Final rendered UI
```

### Test URL

**Ready to test:** http://localhost:8000/v2/pages/page.agent.table.modal

**Expected Result:**
- Modal opens with title "Agents"
- Search bar appears at top
- Data table appears below search bar
- Table shows agent data with sortable/filterable columns
- Add agent button in toolbar

### What Makes This Work

1. **Recursive Component Rendering**: V2ShellPage recursively processes `config.layout.children`
2. **Children Support**: ComponentRenderer passes `children` to components that accept them
3. **Component Registry**: All layout and leaf components registered in ComponentRegistry
4. **Proper TypeScript Types**: `PageConfig` interface matches API response structure
5. **Database Schema**: `config` column stores complete nested layout JSON

### Critical Fixes Applied

#### Fix #1: Synchronous Component Registration

**Problem:** Async component imports caused race condition where pages tried to render before components were registered.

**Solution:** Created `registerCoreComponents.ts` with synchronous eager imports for critical components:
- `rows`, `columns` (layouts)
- `table`, `data-table` (tables)
- `search.bar` (search)
- `button.icon` (buttons)

These are registered **first** in `main.tsx` before async registrations, ensuring they're always available.

#### Fix #2: Component Config Structure

**Problem:** Components expect props nested under `props` object, but JSON had them at root level.

**Incorrect:**
```json
{
  "type": "table",
  "columns": [...],
  "dataSource": "Agent"
}
```

**Correct:**
```json
{
  "type": "table",
  "props": {
    "columns": [...],
    "dataSource": "Agent"
  }
}
```

**Solution:** Updated `page.agent.table.modal.json` to nest component-specific props under `props` object.

### Row Click Behavior Fix

**Issue:** Row clicks were showing `alert()` dialogs instead of proper detail modals.

**Root Cause:** Config used `type: 'command'` which triggers CommandHandler's alert-based handlers (temporary POC code).

**Solution:** Changed rowAction to `type: 'modal'` which:
- Fetches detail data from API endpoint
- Opens proper modal dialog with fields
- Matches the working POC implementation from Oct 15, 2025

**Working Config:**
```json
"rowAction": {
  "type": "modal",
  "title": "Agent Details",
  "url": "/api/v2/ui/types/Agent/{{row.id}}",
  "fields": [
    { "key": "name", "label": "Name", "type": "text" },
    { "key": "status", "label": "Status", "type": "badge" }
  ]
}
```

### Testing Component Swapping

To test swapping the search bar for a different component:

```bash
# Update the database directly
psql seer_db -c "
  UPDATE fe_ui_pages 
  SET config = jsonb_set(
    config, 
    '{layout,children,0,type}', 
    '\"navbar\"'
  )
  WHERE key = 'page.agent.table.modal';
"
```

Then refresh the page - **no code changes needed!**

## Next Steps

1. ✅ Read this document
2. ✅ Implement PageRenderer recursive rendering
3. ✅ Update seeders to new schema
4. ⬜ Test in browser: http://localhost:8000/v2/pages/page.agent.table.modal
5. ⬜ Test component swapping by updating config
6. ⬜ Build your first composed page!

---

## Conclusion

This slot-based architecture makes the UI Builder truly **composable** and **config-driven**:

✅ No hardcoded layouts  
✅ Infinite nesting  
✅ Easy component swapping  
✅ Fully declarative  
✅ Blueprint for all future pages  

Once this is working, every new page follows the same pattern. This is the foundation for the entire system.
