# V2 Config-Driven UI System - Unified Implementation

## What We Fixed

### Problem
- PR #84's proof-of-concept agent/model pages used custom components (old TableComponent, old SearchBarComponent)
- Sprint 2 built 56 new generic components but they weren't wired up to work with dataSource
- Two conflicting systems existed

### Solution
**Removed all old POC code and enhanced Sprint 2 components to be fully config-driven:**

1. **DataTableComponent Enhanced** (`v2/advanced/DataTableComponent.tsx`)
   - Added `dataSource` support in props
   - Fetches from `/api/v2/ui/datasource/{alias}/query`
   - Added `toolbar` rendering support
   - Added `rowAction` support for modal clicks
   - Supports both static `data` and dynamic `dataSource`

2. **SearchBarComponent Created** (`v2/composites/SearchBarComponent.tsx`)
   - Simple search input with icon
   - Accepts `dataSource` for context
   - Uses Sprint 2 component structure

3. **Page Config Migrated**
   - Updated `page.agent.table.modal` to use Sprint 2 structure
   - All config in `props` object (not flat structure)
   - Uses `data-table` type (not `table`)
   - Uses `search.bar` type

## Current Structure

### Agent Page Config (`page.agent.table.modal`)
```json
{
  "overlay": "modal",
  "title": "Agents",
  "components": [
    {
      "id": "component.search.bar.agent",
      "type": "search.bar",
      "dataSource": "Agent",
      "props": {
        "placeholder": "Search agents..."
      }
    },
    {
      "id": "component.table.agent",
      "type": "data-table",
      "props": {
        "dataSource": "Agent",
        "columns": [...],
        "rowAction": {...},
        "toolbar": [...]
      }
    }
  ]
}
```

### Component Registry
**56 Sprint 2 Components + 2 config-driven:**
- `data-table` → DataTableComponent (with dataSource support)
- `search.bar` → SearchBarComponent

**Old POC components REMOVED:**
- ❌ `table` (old TableComponent) 
- ❌ `search.bar` (old version)
- ❌ `button.icon` (old ButtonIconComponent)
- ❌ `detail` (old DetailComponent)
- ❌ SlotBinder, ActionDispatcher, old hooks

## How It Works

1. User visits `/v2/pages/page.agent.table.modal`
2. V2ShellController renders shell.blade.php
3. React app loads via v2/main.tsx
4. V2ShellPage fetches config from `/api/v2/ui/pages/{key}`
5. ComponentRenderer renders each component by type
6. DataTableComponent sees `dataSource: "Agent"` in props
7. Fetches data from `/api/v2/ui/datasource/Agent/query`
8. Renders table with data

## What Still Needs Work

### 1. Toolbar Button Actions
Currently toolbar buttons just render, they don't execute actions. Need to:
- Make toolbar buttons use ComponentRenderer (not inline render)
- Wire up click actions to open modals/forms

### 2. Row Click Modal
RowAction type='modal' logs to console. Need to:
- Create modal system to open detail views
- Pass row data to modal content

### 3. Search → Table Communication
Search bar doesn't filter table yet. Need to:
- Add state management/events for component communication
- Have search updates trigger table re-fetch with search param

### 4. Model Page
Apply same structure to `page.model.table.modal`

## Testing

```bash
# 1. Build (already done)
npm run build

# 2. Start server
php artisan serve

# 3. Test
http://localhost:8000/v2/pages/page.agent.table.modal
```

**Expected:**
- Modal opens with "Agents" title
- Search bar renders (no filtering yet)
- Table fetches and displays agent data
- Toolbar "New Agent" button renders (no action yet)
- Clicking row logs to console (no modal yet)

## Files Modified

**Enhanced:**
- `resources/js/components/v2/advanced/DataTableComponent.tsx` - Added dataSource, toolbar, rowAction support

**Created:**
- `resources/js/components/v2/composites/SearchBarComponent.tsx` - Simple search input

**Updated:**
- `resources/js/components/v2/ComponentRegistry.ts` - Removed old registrations, added search.bar
- Database: `fe_ui_pages.layout_tree_json` for page.agent.table.modal

**Removed:**
- All old POC files from PR #84

## Next Session Priority

1. Wire up toolbar button actions (form modals)
2. Implement row click modals for detail views
3. Add search → table communication
4. Apply same pattern to model page
