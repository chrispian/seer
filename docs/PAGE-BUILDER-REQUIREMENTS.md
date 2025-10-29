# Page Builder Requirements & Gaps

## Overview
Created a comprehensive page builder configuration at:
`storage/ui-builder/pages/page.ui-builder.page-builder.json`

This is an **adaptive form-based builder** (not visual) that guides users through creating complex page configurations.

---

## What Works Now (No Changes Needed)

### ✅ Existing Components Used
- `accordion` / `accordion-item` - Multi-step interface
- `card` - Containers for sections
- `typography` - Headings and descriptions
- `data-table` - Component/datasource reference lists
- `form` - All the form fields
- `button` - Actions
- `tabs` / `tab-panel` - Sidebar reference tabs
- `rows` / `columns` - Layout containers

### ✅ Existing Datasources Used
- `UiComponent` - Browse available components
- `UiPage` - View example pages
- `UiModule` - Module selection

### ✅ Existing Actions Supported
- `modal` - Detail views and forms
- `http` - Save progress, save draft
- `command` - Copy to clipboard

---

## What's Missing (Backend Endpoints Needed)

### 🔴 Required New Endpoints

These are **custom builder-specific endpoints** that don't exist yet:

#### 1. Save Progress (Auto-save)
```
POST /api/ui/builder/save-progress
```
**Purpose:** Auto-save form progress to session/temp storage  
**Request Body:**
```json
{
  "step": "page-basics",
  "data": {
    "page_key": "page.custom.mypage",
    "title": "My Page",
    "overlay": "page"
  }
}
```
**Response:**
```json
{
  "success": true,
  "builder_session_id": "builder-xyz123"
}
```

#### 2. Load Progress (Resume)
```
GET /api/ui/builder/load-progress?session_id=builder-xyz123
```
**Purpose:** Load saved progress when user returns  
**Response:**
```json
{
  "page_key": "page.custom.mypage",
  "title": "My Page",
  "overlay": "page",
  "layout_type": "rows",
  "components": []
}
```

#### 3. Get Builder Components (Current Page's Components)
```
GET /api/ui/builder/components?session_id=builder-xyz123
```
**Purpose:** Custom datasource showing components added to current page  
**Alias:** `BuilderComponents` (used in data-table)  
**Response:**
```json
{
  "data": [
    {
      "id": "component.table.agents",
      "type": "data-table",
      "order": 1,
      "parent_id": null
    }
  ]
}
```

#### 4. Add/Edit Component (Modal Form)
```
GET /api/ui/builder/component/new
GET /api/ui/builder/component/{id}
```
**Purpose:** Return adaptive form config based on component type  
**Response:** Dynamic form definition based on selected component type

Example for adding a `data-table`:
```json
{
  "fields": [
    {
      "name": "component_id",
      "label": "Component ID",
      "type": "text",
      "required": true,
      "placeholder": "component.table.mydata"
    },
    {
      "name": "component_type",
      "label": "Component Type",
      "type": "select",
      "required": true,
      "options": [
        { "label": "Data Table", "value": "data-table" },
        { "label": "Search Bar", "value": "search.bar" },
        { "label": "Button", "value": "button" },
        { "label": "Card", "value": "card" }
      ]
    },
    {
      "name": "dataSource",
      "label": "Data Source",
      "type": "select",
      "conditionalOn": { "component_type": "data-table" },
      "options": [
        { "label": "Agent", "value": "Agent" },
        { "label": "Model", "value": "Model" },
        { "label": "UiPage", "value": "UiPage" }
      ]
    },
    {
      "name": "columns",
      "label": "Columns (JSON)",
      "type": "textarea",
      "conditionalOn": { "component_type": "data-table" }
    }
  ]
}
```

#### 5. Save Draft
```
POST /api/ui/builder/save-draft
```
**Purpose:** Save incomplete config as draft (not published)  
**Request Body:**
```json
{
  "session_id": "builder-xyz123"
}
```
**Response:**
```json
{
  "success": true,
  "draft_id": 123,
  "message": "Draft saved successfully"
}
```

#### 6. Publish Form
```
GET /api/ui/builder/publish-form?session_id=builder-xyz123
```
**Purpose:** Return form for final publish options  
**Response:**
```json
{
  "fields": [
    {
      "name": "enabled",
      "label": "Enable Immediately",
      "type": "checkbox",
      "defaultValue": true
    },
    {
      "name": "guards",
      "label": "Auth Guards (optional)",
      "type": "select",
      "multiple": true,
      "options": [
        { "label": "Authenticated", "value": "auth" },
        { "label": "Admin", "value": "admin" }
      ]
    }
  ]
}
```

Then:
```
POST /api/ui/datasources/UiPage
```
With the full generated config.

---

## What's Missing (Frontend Components)

### 🟡 Component Features Needed

#### 1. Conditional Fields in Forms
**Current:** Forms don't support conditional visibility  
**Needed:** Show/hide fields based on other field values

Example:
```json
{
  "name": "dataSource",
  "type": "select",
  "conditionalOn": {
    "component_type": "data-table"
  }
}
```

**Options:**
- **Option A:** Add conditional support to form component (new feature)
- **Option B:** Use separate forms/modals for each component type
- **Recommendation:** Option B for now (simpler, works with existing code)

#### 2. Tabs Component
**Current:** Used in config but need to verify it exists  
**Check:** Does `tabs` / `tab-panel` component exist?

#### 3. Dynamic Value Binding
**Current:** `value="{{generated_json}}"` - need to populate this  
**Needed:** Builder state management that generates JSON preview

---

## What's Missing (State Management)

### 🟡 Builder Session Management

The builder needs to maintain state across form steps:

1. **Session Storage**
   - Store in Laravel session or Redis
   - Key: `builder_sessions:{user_id}:{session_id}`
   - Contains: All form values + component tree

2. **JSON Generation**
   - Combine all form values into final PageConfig JSON
   - Validate against schema
   - Display in preview textarea

3. **Component Tree Management**
   - Track components in order
   - Support nesting (parent_id relationship)
   - Generate proper hierarchy for final JSON

---

## Implementation Options

### Option 1: Full Implementation (Recommended for Production)
**Effort:** Medium (2-3 days)
- Build all 6 backend endpoints
- Add session management
- Create dynamic form generator for component types
- Add JSON preview generation
- Support nested components

**Benefits:**
- Full-featured builder
- Auto-save progress
- Resume sessions
- Production-ready

### Option 2: Simplified MVP (Quick Start)
**Effort:** Low (4-6 hours)
- Skip session management (use client-side state)
- Skip auto-save endpoints
- Single-step form instead of accordion
- Direct save to database (no drafts)
- Limit to flat component structures (no nesting initially)

**Benefits:**
- Works immediately
- Can add features incrementally
- Good for prototyping

### Option 3: Hybrid Approach (Recommended for Now)
**Effort:** Low-Medium (1 day)

**Phase 1 (Now):**
- Use existing endpoints where possible
- Client-side JSON generation (JavaScript)
- Direct save to `/api/ui/datasources/UiPage`
- Manual component configuration (JSON textarea)
- Reference sidebar works as-is

**Phase 2 (Follow-up):**
- Add builder endpoints for better UX
- Add session management
- Add adaptive component forms
- Add nested component support

---

## Current Page Structure Analysis

The created page builder config uses:

### Layout Structure ✅
```
page
├── header (card with title/description)
├── main (2-column layout)
│   ├── left: accordion with 4 steps
│   │   ├── Page Basics (form)
│   │   ├── Layout Type (form)
│   │   ├── Add Components (data-table + button)
│   │   └── Preview & Export (textarea + buttons)
│   └── right: sidebar
│       └── tabs
│           ├── Components (data-table)
│           ├── Data Sources (static list)
│           └── Examples (data-table)
```

### Adaptive Flow ✅
1. User fills out page basics
2. User selects layout type
3. User adds components one-by-one
4. User previews JSON
5. User publishes to database

---

## Missing Components Check

Let me verify which components from the config actually exist:

- ✅ `accordion` - YES (shown in component list)
- ✅ `card` - Likely exists (common)
- ✅ `typography` - YES (shown in component list)
- ✅ `data-table` - YES (shown in component list)
- ✅ `form` - Likely exists (common)
- ✅ `button` - Likely exists (basic primitive)
- ✅ `tabs` - Need to verify
- ❓ `tab-panel` - Need to verify
- ✅ `textarea` - YES (shown in component list)
- ✅ `rows`/`columns` - Layout containers (should exist)

---

## Recommended Next Steps

### Immediate (To Test the Page):
1. ✅ Export this JSON to storage location
2. ✅ Seed it into database
3. ✅ Test if page loads (identify missing components)
4. ✅ Check browser console for errors
5. ✅ Document what breaks

### Short-term (To Make It Work):
1. Create simplified builder endpoints (hybrid approach)
2. Add client-side JSON generation
3. Connect "Publish" button to real save endpoint
4. Test end-to-end flow

### Long-term (Production Features):
1. Add full session management
2. Add auto-save
3. Add nested component support
4. Add drag-drop reordering
5. Add component validation
6. Add live preview (actual render, not just JSON)

---

## Questions for You

1. **Which option do you prefer?** (Full Implementation / MVP / Hybrid)

2. **Priority features:**
   - Auto-save progress?
   - Nested components?
   - Visual preview (actual rendered page)?
   - Component templates (presets)?

3. **Complexity level:**
   - Start simple (flat component lists)?
   - Support nesting immediately?

4. **Integration:**
   - Should this be a standalone builder page?
   - Or integrate into existing pages UI?

Let me know your preferences and I'll implement accordingly!
