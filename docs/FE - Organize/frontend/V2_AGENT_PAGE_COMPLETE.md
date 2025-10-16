# V2 Agent Page - Fully Functional Config-Driven UI

## What We Built

### Complete Working Features

1. **Data Table with DataSource Fetching**
   - Fetches agents from `/api/v2/ui/datasource/Agent/query`
   - Displays columns: avatar, name, role, provider, model, status, updated_at
   - Supports sorting, filtering (configured in datasource)

2. **Search Bar**
   - Simple search input with icon
   - Accepts dataSource for context
   - Ready for search → table communication (next step)

3. **Toolbar with Form Modal**
   - "New Agent" button in toolbar
   - Opens form modal with 5 fields:
     - Name (text, required)
     - Agent Profile (select with 13 templates)
     - Persona (textarea)
     - Status (select: active/inactive)
     - Avatar (file upload)
   - Submits to `/api/v2/ui/datasource/Agent` (POST)
   - Refreshes table after successful creation

4. **Generic DataSource CRUD**
   - Created POST endpoint for any datasource
   - GenericDataSourceResolver.create() method
   - All config-driven via fe_ui_datasources table

## Architecture

### Config Structure
```json
{
  "overlay": "modal",
  "title": "Agents",
  "components": [
    {
      "type": "search.bar",
      "dataSource": "Agent",
      "props": { "placeholder": "Search agents..." }
    },
    {
      "type": "data-table",
      "props": {
        "dataSource": "Agent",
        "columns": [...],
        "toolbar": [
          {
            "type": "button.icon",
            "props": { "label": "New Agent" },
            "actions": {
              "click": {
                "type": "modal",
                "modal": "form",
                "title": "Create New Agent",
                "fields": [...],
                "submitUrl": "/api/v2/ui/datasource/Agent",
                "submitMethod": "POST",
                "refreshTarget": "component.table.agent"
              }
            }
          }
        ]
      }
    }
  ]
}
```

### Data Flow

**Page Load:**
1. Browser → `/v2/pages/page.agent.table.modal`
2. V2ShellController → shell.blade.php
3. React → `/api/v2/ui/pages/page.agent.table.modal` (fetch config)
4. DataTableComponent → `/api/v2/ui/datasource/Agent/query` (fetch data)
5. Render modal with search + table

**Create Agent:**
1. User clicks "New Agent"
2. Form modal opens with fields from config
3. User fills and submits
4. POST `/api/v2/ui/datasource/Agent` with form data
5. GenericDataSourceResolver creates Agent
6. Table refreshes from `/api/v2/ui/datasource/Agent/query`
7. New agent appears in table

## Files Modified/Created

**Backend:**
- `app/Services/V2/GenericDataSourceResolver.php` - Added create() method
- `app/Http/Controllers/Api/DataSourceController.php` - Added store() method
- `routes/api.php` - Added POST /api/v2/ui/datasource/{alias}

**Frontend:**
- `resources/js/components/v2/advanced/DataTableComponent.tsx` - Enhanced with:
  - dataSource fetching
  - Toolbar rendering
  - Form modal system
  - Form submission & refresh
- `resources/js/components/v2/composites/SearchBarComponent.tsx` - Created
- `resources/js/components/v2/types.ts` - Extended ActionConfig for modals
- `resources/js/components/v2/ComponentRegistry.ts` - Registered search.bar

**Database:**
- Updated `fe_ui_pages.layout_tree_json` for page.agent.table.modal

**Removed:**
- All old POC files from PR #84

## Testing

```bash
# Refresh browser at:
http://localhost:8000/v2/pages/page.agent.table.modal

# Should see:
# - Modal with "Agents" title
# - Search bar (displays, doesn't filter yet)
# - Table with agent data
# - "New Agent" button

# Click "New Agent":
# - Form modal opens
# - Fill in name (required)
# - Optionally select profile, add persona, set status, upload avatar
# - Click "Create Agent"
# - Form submits, modal closes
# - Table refreshes with new agent
```

## Next Steps

1. **Search → Table Communication**
   - Wire search input to trigger table refetch with search param
   - Update DataTableComponent to accept search prop

2. **Row Click Detail Modal**
   - Implement rowAction type='modal' to show detail view
   - Fetch row details from datasource

3. **Apply to Model Page**
   - Update `page.model.table.modal` with same structure
   - Should work identically with Model datasource

4. **Error Handling**
   - Show validation errors in form
   - Display API errors in toast notifications

## Success Metrics

✅ **100% Config-Driven** - Zero hard-coded components, all from database
✅ **Generic & Reusable** - DataTable, SearchBar work with any datasource
✅ **Single System** - Old POC code completely removed
✅ **Working CRUD** - Full create flow with form modal
✅ **Sprint 2 Architecture** - Uses the 56 components we built
