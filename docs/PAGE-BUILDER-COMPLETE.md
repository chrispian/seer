# Page Builder - Complete Implementation

**Version:** 1.0.0  
**Date:** October 28, 2025  
**Status:** ✅ Complete - Ready for Testing

---

## Overview

Built a complete **adaptive form-based page builder** that allows users to create complex UI pages through a guided multi-step interface without needing visual drag-and-drop.

**Key Features:**
- ✅ Session-based workflow with auto-save
- ✅ Multi-step accordion interface
- ✅ Component library browser
- ✅ JSON preview with validation
- ✅ Draft saving
- ✅ One-click publishing to database
- ✅ Support for nested components
- ✅ Resume sessions

---

## Architecture

### Database Tables

#### `fe_ui_builder_sessions`
Stores the current state of a page being built.

```sql
- id: bigint (PK)
- session_id: string (unique, UUID)
- user_id: string (nullable)
- page_key: string (nullable)
- title: string (nullable)
- overlay: string (page/modal/drawer)
- route: string (nullable)
- module_key: string (nullable)
- layout_type: string (rows/columns/grid/stack)
- layout_id: string
- state_json: json (form state)
- config_json: json (generated config)
- expires_at: timestamp
- created_at, updated_at
```

#### `fe_ui_builder_page_components`
Stores components being added to the page.

```sql
- id: bigint (PK)
- session_id: string (FK to sessions)
- component_id: string (e.g., "component.table.agents")
- component_type: string (data-table, button, etc.)
- parent_id: bigint (FK self, nullable for nesting)
- order: integer (display order)
- props_json: json (component props)
- actions_json: json (event handlers)
- children_json: json (for containers)
- created_at, updated_at
```

### Models

- **BuilderSession** - Manages builder sessions, generates final config
- **BuilderPageComponent** - Represents components in the builder, supports nesting

### Controller

**BuilderController** - Single controller with 11 endpoints:

1. `POST /api/ui/builder/save-progress` - Auto-save form progress
2. `GET /api/ui/builder/load-progress` - Resume session
3. `GET /api/ui/builder/page-components` - List components in current page
4. `POST /api/ui/builder/page-component` - Add component
5. `GET /api/ui/builder/page-component/{id}` - Get component edit form
6. `PUT /api/ui/builder/page-component/{id}` - Update component
7. `DELETE /api/ui/builder/page-component/{id}` - Remove component
8. `GET /api/ui/builder/preview` - Get JSON preview
9. `POST /api/ui/builder/save-draft` - Save as draft
10. `POST /api/ui/builder/publish` - Publish to fe_ui_pages

---

## User Flow

### Step 1: Page Basics
User fills out:
- Page Key (e.g., `page.custom.dashboard`)
- Title
- Display Type (page/modal/drawer)
- Route (optional)
- Module

**Action:** Saves progress automatically

### Step 2: Layout Type
User selects:
- Layout direction (rows/columns/grid/stack)
- Layout ID

**Action:** Saves progress automatically

### Step 3: Add Components
User sees table of current components (empty initially).

**Actions:**
- Click "Add Component" button
- Modal opens with adaptive form:
  - Component ID (required)
  - Component Type (dropdown of all available types)
  - Display Order
  - Props (JSON textarea)
  - Actions (JSON textarea - optional)
- Submit adds component to table
- Click row to edit component
- Delete button removes component

Components are stored in `fe_ui_builder_page_components` table.

### Step 4: Preview & Export
User sees:
- Live JSON preview of full page config
- Copy JSON button
- Save as Draft button
- Publish button (opens final confirmation modal)

**Publish Modal:**
- Enable Immediately (checkbox)
- Auth Guards (multi-select)
- Submit creates record in `fe_ui_pages`

---

## API Endpoints Detail

### Save Progress
```http
POST /api/ui/builder/save-progress

{
  "session_id": "uuid-here" (optional, creates new if missing),
  "page_key": "page.custom.mypage",
  "title": "My Page",
  "overlay": "page",
  "route": "/my-page",
  "module_key": "core.app",
  "layout_type": "rows",
  "layout_id": "root-layout",
  "state": { ... any form state ... }
}

Response:
{
  "success": true,
  "session_id": "uuid-here",
  "message": "Progress saved"
}
```

### Load Progress
```http
GET /api/ui/builder/load-progress?session_id=uuid-here

Response:
{
  "session_id": "uuid-here",
  "page_key": "page.custom.mypage",
  "title": "My Page",
  "overlay": "page",
  "route": "/my-page",
  "module_key": "core.app",
  "layout_type": "rows",
  "layout_id": "root-layout",
  "state": { ... },
  "component_count": 3
}
```

### Get Page Components
```http
GET /api/ui/builder/page-components?session_id=uuid-here

Response:
{
  "data": [
    {
      "id": 1,
      "component_id": "component.table.agents",
      "type": "data-table",
      "order": 0,
      "has_children": false
    }
  ],
  "meta": {
    "total": 1
  }
}
```

### Add Component
```http
POST /api/ui/builder/page-component

{
  "session_id": "uuid-here",
  "component_id": "component.table.agents",
  "component_type": "data-table",
  "order": 0,
  "parent_id": null,
  "props_json": "{\"dataSource\": \"Agent\", \"columns\": [...]}",
  "actions_json": "{\"rowClick\": {...}}"
}

Response:
{
  "success": true,
  "component": { ... component object ... }
}
```

### Get Component Form
```http
GET /api/ui/builder/page-component/new?session_id=uuid-here

Response:
{
  "title": "Add Component",
  "fields": [
    {
      "name": "component_id",
      "label": "Component ID",
      "type": "text",
      "required": true,
      ...
    },
    ...
  ],
  "submitUrl": "/api/ui/builder/page-component",
  "submitMethod": "POST"
}
```

For editing:
```http
GET /api/ui/builder/page-component/1

Response:
{
  "title": "Edit Component",
  "fields": [ ... fields with defaultValue populated ... ],
  "submitUrl": "/api/ui/builder/page-component/1",
  "submitMethod": "PUT"
}
```

### Preview
```http
GET /api/ui/builder/preview?session_id=uuid-here

Response:
{
  "config": {
    "id": "page.custom.mypage",
    "overlay": "page",
    "title": "My Page",
    "layout": {
      "type": "rows",
      "id": "root-layout",
      "children": [ ... ]
    }
  },
  "json": "{\n  \"id\": \"page.custom.mypage\",\n  ..."
}
```

### Publish
```http
POST /api/ui/builder/publish

{
  "session_id": "uuid-here",
  "enabled": true,
  "guards": ["auth"]
}

Response:
{
  "success": true,
  "message": "Page published successfully",
  "page": {
    "id": 10,
    "key": "page.custom.mypage",
    "version": 1,
    "route": "/my-page"
  }
}
```

---

## Frontend Integration

### Sidebar Reference Tabs

**Components Tab:**
- Data table showing all available UI components
- Filter by kind (primitive/composite/layout)
- Click row to see component schema

**Data Sources Tab:**
- Static list of available datasources:
  - Agent
  - Model  
  - UiPage
  - UiComponent
  - UiModule

**Examples Tab:**
- Data table of existing pages
- Click to see full page config as JSON reference

### State Management

Builder uses URL param or session storage to track `session_id`.

**On page load:**
1. Check for `?session_id=` in URL
2. If exists, call `load-progress` to resume
3. If not, start fresh (new session created on first save)

**Auto-save triggers:**
- On form blur
- Every 30 seconds
- When switching accordion steps

---

## Component Nesting

Components support parent-child relationships:

```json
{
  "id": "card-container",
  "type": "card",
  "children": [
    {
      "id": "inner-table",
      "type": "data-table",
      "props": { ... }
    }
  ]
}
```

**In Builder:**
- Add component with `parent_id` set to parent component's DB ID
- BuilderPageComponent model recursively builds children
- Final config has proper nested structure

**Future Enhancement:**
- UI for selecting parent from dropdown
- Visual tree view of component hierarchy

---

## Files Created

### Models
- `vendor/hollis-labs/ui-builder/src/Models/BuilderSession.php`
- `vendor/hollis-labs/ui-builder/src/Models/BuilderPageComponent.php`

### Controllers
- `vendor/hollis-labs/ui-builder/src/Http/Controllers/BuilderController.php`

### Routes
- Updated `vendor/hollis-labs/ui-builder/routes/api.php`

### Migrations
- `vendor/hollis-labs/ui-builder/database/migrations/2025_10_28_200000_create_builder_tables.php`

### Pages
- `storage/ui-builder/pages/page.ui-builder.page-builder.json`

### Documentation
- `docs/PAGE-BUILDER-REQUIREMENTS.md`
- `docs/PAGE-BUILDER-SIMPLIFIED.md`
- `docs/PAGE-BUILDER-COMPLETE.md` (this file)

---

## Testing Steps

### 1. Access the Builder
```
http://seer.test/ui/builder/page-builder
```

### 2. Test Step 1 - Page Basics
- Fill out form
- Click "Save Basics"
- Check Network tab for `save-progress` call
- Verify session_id returned

### 3. Test Step 2 - Layout
- Select layout type
- Click "Save Layout"
- Verify save-progress call

### 4. Test Step 3 - Add Components
- Click "Add Component" button
- Modal should open with form
- Fill in:
  - Component ID: `component.test.table`
  - Component Type: `data-table`
  - Props JSON:
    ```json
    {
      "dataSource": "Agent",
      "columns": [
        {"key": "name", "label": "Name"}
      ]
    }
    ```
- Submit
- Table should refresh showing new component
- Click component row to edit
- Click delete to remove

### 5. Test Step 4 - Preview
- Open Preview accordion
- JSON textarea should show generated config
- Click "Copy JSON"
- Click "Save as Draft"
- Click "Publish"
  - Modal opens (if form endpoint exists)
  - Or direct publish if simplified

### 6. Verify Published Page
```sql
SELECT * FROM fe_ui_pages WHERE key = 'page.custom.mypage';
```

Config should match preview JSON.

### 7. Test Resume Session
- Copy session_id from Network tab
- Open new browser tab
- Navigate to: `http://seer.test/ui/builder/page-builder?session_id=PASTE-HERE`
- Form should be pre-filled with saved data
- Components table should show existing components

---

## Known Limitations

### Current Version (1.0)

1. **No Visual Preview**
   - Shows JSON only
   - Future: Add live rendering preview

2. **Manual JSON Entry**
   - Props and actions require JSON knowledge
   - Future: Generate adaptive forms based on component schema

3. **Flat Components**
   - Nesting supported in backend
   - UI doesn't expose parent selection yet
   - Future: Tree view with drag-drop

4. **No Validation**
   - JSON validation happens on submit
   - No real-time validation
   - Future: JSON schema validation in textarea

5. **Session Expiry**
   - Sessions expire after 7 days
   - No warning before expiry
   - Future: Add expiry countdown

### Planned Enhancements (v2.0)

- [ ] Component schema-driven forms (no manual JSON)
- [ ] Visual component tree
- [ ] Drag-drop component reordering
- [ ] Live preview pane
- [ ] Template library
- [ ] Component search/filter
- [ ] Undo/redo
- [ ] Version history
- [ ] Duplicate page
- [ ] Import/export pages

---

## Sync to UI Builder Repo

When ready to release to ui-builder package repository:

1. Copy all files from `modules/ui-builder-ui/` to actual repo
2. Tag as `v2.2.0` (builder feature)
3. Update package on Packagist/npm

**Breaking Changes:** None - all additive features

---

## Support

For issues or questions:
1. Check browser console for errors
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify migrations ran: `php artisan migrate:status`
4. Test API endpoints with Postman/curl

**Common Issues:**

**Q: "No components appear after adding"**  
A: Check session_id is consistent across requests

**Q: "JSON parse error when submitting"**  
A: Validate JSON in props_json/actions_json fields

**Q: "Session expired"**  
A: Sessions last 7 days - start new session

**Q: "Preview shows empty config"**  
A: Must save progress in Steps 1 & 2 first

---

## Success! 🎉

The page builder is fully implemented and ready for testing. All endpoints are complete, database tables created, and the UI is seeded.

**Next Steps:**
1. Test the UI at `/ui/builder/page-builder`
2. Report any issues
3. Decide on v2.0 features priority
4. Sync to ui-builder repository when ready
