# UI Builder v2 - Quick Start Guide for New Contributors

## Overview

The UI Builder v2 system is a **100% config-driven UI framework** where entire pages, components, and interactions are defined in the database rather than hard-coded. This enables non-developers to create and modify UIs without touching code.

### Core Concept

Instead of writing React components manually, you:
1. Store page configuration in `fe_ui_pages` table (JSON)
2. Define data sources in `fe_ui_datasources` table
3. Reference component types that are registered in the system
4. The system renders everything dynamically from the config

**Working Demo:** `/v2/pages/page.agent.table.modal`
- Search filters table in real-time
- "New Agent" button opens config-driven form
- Click rows to view details
- All driven by database config, zero hard-coded UI

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Database Config                          │
│  fe_ui_pages → layout_tree_json (component tree)                │
│  fe_ui_datasources → model mappings, transforms, capabilities   │
│  fe_ui_registry → component type definitions                    │
│  fe_ui_feature_flags → A/B testing, rollouts                    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Backend (Laravel)                           │
│  Routes: /v2/pages/{key}, /api/v2/ui/datasource/{alias}/*      │
│  Controllers: V2ShellController, UiPageController, DataSource   │
│  Services: GenericDataSourceResolver (CRUD any model)           │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    Frontend (React + Vite)                       │
│  Entry: resources/js/v2/main.tsx                                │
│  Shell: V2ShellPage.tsx (fetches config, renders components)    │
│  Registry: ComponentRegistry.ts (56 components registered)      │
│  Renderer: ComponentRenderer.tsx (dynamic instantiation)        │
└─────────────────────────────────────────────────────────────────┘
```

---

## File Structure

### Backend PHP Files

```
app/
├── Http/Controllers/
│   ├── Api/
│   │   ├── DataSourceController.php      # Generic CRUD for any datasource
│   │   └── TypesController.php           # Typed data queries
│   └── V2/
│       ├── V2ShellController.php         # Renders shell.blade.php for pages
│       └── UiPageController.php          # Returns page config JSON
│
├── Services/V2/
│   ├── GenericDataSourceResolver.php     # Fetches/creates data from datasources
│   └── FeatureFlagService.php            # A/B testing, percentage rollouts
│
└── Models/
    ├── FeUiPage.php                      # Pages with layout_tree_json config
    ├── FeUiDatasource.php                # Model → API mappings
    ├── FeUiRegistry.php                  # Component type registry
    ├── FeUiFeatureFlag.php               # Feature flags
    ├── FeUiModule.php                    # UI modules (grouping)
    └── FeUiTheme.php                     # Theme configurations
```

### Frontend React Files

```
resources/
├── js/
│   ├── v2/
│   │   ├── main.tsx                      # Entry point, registers all components
│   │   ├── V2ShellPage.tsx               # Fetches config, renders modal/sheet/page
│   │   └── ComponentRenderer.tsx         # Looks up component by type, renders
│   │
│   └── components/v2/
│       ├── ComponentRegistry.ts          # 56 components registered here
│       ├── types.ts                      # TypeScript interfaces for configs
│       │
│       ├── primitives/                   # 21 basic components
│       │   ├── ButtonComponent.tsx
│       │   ├── InputComponent.tsx
│       │   ├── BadgeComponent.tsx
│       │   └── ... (18 more)
│       │
│       ├── layouts/                      # 10 layout components
│       │   ├── CardComponent.tsx
│       │   ├── AccordionComponent.tsx
│       │   └── ... (8 more)
│       │
│       ├── navigation/                   # 10 navigation components
│       │   ├── TabsComponent.tsx
│       │   ├── BreadcrumbComponent.tsx
│       │   └── ... (8 more)
│       │
│       ├── composites/                   # 13 interactive components
│       │   ├── DialogComponent.tsx
│       │   ├── SearchBarComponent.tsx    # ✅ WIRED (emits search events)
│       │   └── ... (11 more)
│       │
│       ├── forms/                        # 9 form components
│       │   ├── FormComponent.tsx
│       │   ├── DatePickerComponent.tsx
│       │   └── ... (7 more)
│       │
│       └── advanced/                     # 3 advanced components
│           ├── DataTableComponent.tsx    # ✅ WIRED (dataSource, modals, search)
│           ├── ChartComponent.tsx
│           └── CarouselComponent.tsx
│
└── views/v2/
    └── shell.blade.php                   # Blade template that loads main.tsx
```

### Routes

```
routes/
├── web.php
│   └── /v2/pages/{key}                   # Renders page shell
│
└── api.php
    └── /api/v2/ui/
        ├── pages/{key}                   # Returns page config JSON
        ├── datasource/{alias}/query      # GET: Query data with filters/search
        ├── datasource/{alias}            # POST: Create new record
        ├── types/{alias}/query           # GET: Query typed data
        └── types/{alias}/{id}            # GET: Get single record details
```

### Build Configuration

```
vite.config.ts                            # Entry points: app.tsx, v2/main.tsx
package.json                              # npm run build / npm run dev
```

---

## Database Tables

### Core Tables

**1. `fe_ui_pages`** - Page definitions
```sql
SELECT id, key, layout_tree_json, module_key, enabled FROM fe_ui_pages;
```
- `key`: Page identifier (e.g., `page.agent.table.modal`)
- `layout_tree_json`: Component tree with types, props, actions
- Example: Search bar + data table + toolbar + modals

**2. `fe_ui_datasources`** - Model mappings
```sql
SELECT alias, model_class, capabilities_json, schema_json FROM fe_ui_datasources;
```
- `alias`: Friendly name (e.g., `Agent`, `Model`)
- `model_class`: Laravel model (e.g., `App\Models\Agent`)
- `capabilities_json`: searchable, filterable, sortable fields
- `schema_json.transform`: How to map model → API response

**3. `fe_ui_registry`** - Component types
```sql
SELECT component_key, category, is_enabled FROM fe_ui_registry;
```
- `component_key`: Type name (e.g., `data-table`, `search.bar`)
- `category`: primitives, layouts, navigation, composites, forms, advanced
- Links database config to React components

**4. `fe_ui_feature_flags`** - A/B testing
```sql
SELECT key, is_enabled, percentage, conditions FROM fe_ui_feature_flags;
```
- `percentage`: Rollout % (0-100)
- `conditions`: JSON rules for targeting

### Example Page Config

```json
{
  "overlay": "modal",
  "title": "Agents",
  "components": [
    {
      "id": "component.search.bar.agent",
      "type": "search.bar",
      "dataSource": "Agent",
      "result": { "target": "component.table.agent" },
      "props": { "placeholder": "Search agents..." }
    },
    {
      "id": "component.table.agent",
      "type": "data-table",
      "props": {
        "dataSource": "Agent",
        "columns": [
          { "key": "name", "label": "Name", "sortable": true },
          { "key": "role", "label": "Role" }
        ],
        "toolbar": [
          {
            "type": "button.icon",
            "props": { "label": "New Agent" },
            "actions": {
              "click": {
                "type": "modal",
                "modal": "form",
                "fields": [...],
                "submitUrl": "/api/v2/ui/datasource/Agent"
              }
            }
          }
        ],
        "rowAction": {
          "type": "modal",
          "url": "/api/v2/ui/types/Agent/{{row.id}}"
        }
      }
    }
  ]
}
```

---

## How It Works: Data Flow

### 1. User Visits Page
```
Browser → /v2/pages/page.agent.table.modal
       ↓
V2ShellController renders shell.blade.php
       ↓
Loads resources/js/v2/main.tsx (React app)
```

### 2. Fetch Config
```
V2ShellPage.tsx → GET /api/v2/ui/pages/page.agent.table.modal
       ↓
UiPageController returns layout_tree_json from database
       ↓
V2ShellPage loops through components array
```

### 3. Render Components
```
For each component config:
  ComponentRenderer.tsx
       ↓
  Look up type in ComponentRegistry
       ↓
  Render React component with config as props
```

### 4. DataTable Fetches Data
```
DataTableComponent sees dataSource: "Agent"
       ↓
GET /api/v2/ui/datasource/Agent/query
       ↓
DataSourceController → GenericDataSourceResolver
       ↓
Resolves to Agent model, applies transforms
       ↓
Returns JSON array to table
```

### 5. User Interactions
```
Search bar changes → Emits "component:search" event
       ↓
DataTable listens for event with its component ID
       ↓
Refetches with ?search=term parameter

Button click → Reads actions.click from config
       ↓
If type="modal", opens form modal
       ↓
Form submits → POST /api/v2/ui/datasource/Agent
       ↓
Table refetches data
```

---

## Component Wiring Status

### ✅ Fully Wired (2 components)

1. **DataTableComponent** (`data-table`)
   - ✅ Fetches from dataSource
   - ✅ Supports search/filter parameters
   - ✅ Toolbar with action handlers
   - ✅ Form modals for create
   - ✅ Detail modals for view
   - ✅ Listens for component events

2. **SearchBarComponent** (`search.bar`)
   - ✅ Emits search events (300ms debounce)
   - ✅ Targets specific components by ID
   - ✅ Works with dataSource context

### ⚠️ Partially Wired (54 components)

All 56 Sprint 2 components are **registered** in ComponentRegistry but only 2 are **fully config-driven**. The rest need wiring to:
- Accept config from database
- Support dataSource integration (where applicable)
- Handle actions from config
- Support nested components/children

**See `WIRING_COMPONENTS_GUIDE.md` for detailed instructions.**

---

## Outstanding Tasks

### 1. Wire Remaining Components (Priority Order)

**High Priority (Commonly Used):**
- [ ] **FormComponent** - Better form modals with validation
- [ ] **CardComponent** - Layout structure
- [ ] **TabsComponent** - Organizing content
- [ ] **DialogComponent** - Better modal system
- [ ] **SheetComponent** - Side panels

**Medium Priority (Form Fields):**
- [ ] Input, Select, Textarea already work in form modals but need standalone wiring
- [ ] DatePickerComponent - Date selection
- [ ] CheckboxComponent - Boolean inputs
- [ ] RadioGroupComponent - Single choice

**Lower Priority (Navigation):**
- [ ] BreadcrumbComponent
- [ ] PaginationComponent
- [ ] NavigationMenuComponent

**Advanced:**
- [ ] ChartComponent - Data visualization
- [ ] CarouselComponent - Image/content slider

### 2. DataSource Enhancements

- [ ] **Update Support** - Add PUT endpoint for editing records
- [ ] **Delete Support** - Add DELETE endpoint
- [ ] **Batch Operations** - Support bulk actions
- [ ] **Relationships** - Load related data via `with` parameter
- [ ] **Validation** - Return validation errors from API

### 3. Action System Improvements

- [ ] **Command Actions** - Execute artisan commands from UI
- [ ] **Navigate Actions** - Route navigation
- [ ] **HTTP Actions** - Generic API calls
- [ ] **Emit Actions** - Custom events
- [ ] **Chained Actions** - Execute multiple actions in sequence

### 4. Component Communication

- [ ] **Event Bus** - Better than window.dispatchEvent
- [ ] **State Management** - Share state between components
- [ ] **Slots/Targeting** - More sophisticated component binding

### 5. Developer Experience

- [ ] **Hot Module Replacement** - Fix `npm run dev` (currently buggy, use `npm run build`)
- [ ] **Component Preview** - Storybook-like preview mode
- [ ] **Config Validation** - Catch errors before runtime
- [ ] **Type Safety** - Better TypeScript types for configs

### 6. Documentation

- [ ] **Component API Docs** - What props each component accepts
- [ ] **Config Examples** - Real-world page configurations
- [ ] **Video Walkthrough** - Screen recording of building a page
- [ ] **Migration Guide** - Converting old pages to v2

---

## Getting Started: Your First Contribution

### Step 1: Run the Demo
```bash
npm run build
php artisan serve

# Visit: http://localhost:8000/v2/pages/page.agent.table.modal
```

Interact with search, create, row clicks to understand the system.

### Step 2: Study Working Examples

**Read these files in order:**
1. `resources/js/v2/V2ShellPage.tsx` - How pages load
2. `resources/js/components/v2/advanced/DataTableComponent.tsx` - Full wiring example
3. `resources/js/components/v2/composites/SearchBarComponent.tsx` - Simple wiring example
4. Database: `SELECT * FROM fe_ui_pages WHERE key = 'page.agent.table.modal'`

### Step 3: Wire a Simple Component

**Good starter component: BadgeComponent**

1. Open `resources/js/components/v2/primitives/BadgeComponent.tsx`
2. Currently just renders with static props
3. Enhance to accept `dataSource` and fetch dynamic data
4. Example use case: "Status badge that fetches agent status from API"

Follow the pattern in `WIRING_COMPONENTS_GUIDE.md`:
- Accept `dataSource` in props
- Use `useEffect` to fetch
- Handle loading/error states
- Emit events if needed

### Step 4: Test Your Changes

1. Add component to a page config in database
2. Build: `npm run build`
3. Refresh browser
4. Check DevTools console for errors

### Step 5: Create PR

Follow the commit convention:
```
feat(ui-builder): wire BadgeComponent for config-driven usage

- Accept dataSource prop
- Fetch data from /api/v2/ui/datasource/{alias}/query
- Support loading/error states
- Emit badge:click events

Example usage in fe_ui_pages config: {...}
```

---

## Key Concepts to Understand

### 1. Everything is Config-Driven
Never hard-code UI. If you need a new page, add to `fe_ui_pages`. If you need data, define in `fe_ui_datasources`.

### 2. Components are Dumb
Components receive `config` prop and render based on it. They don't know about the business logic or models—that's handled by DataSourceResolver.

### 3. Actions Define Behavior
Button clicks, form submits, row clicks are all defined in `actions` object in config. Components read and execute these actions.

### 4. DataSource is Polymorphic
`dataSource: "Agent"` can be any model. The system resolves it dynamically. This makes components reusable across any entity.

### 5. Events Enable Communication
Search bar doesn't talk directly to table. It emits an event. Table listens for events targeting its ID. This keeps components decoupled.

---

## Common Patterns

### Pattern 1: Fetch Data from DataSource
```typescript
const [data, setData] = useState([]);

useEffect(() => {
  if (props.dataSource) {
    fetch(`/api/v2/ui/datasource/${props.dataSource}/query`)
      .then(res => res.json())
      .then(result => setData(result.data));
  }
}, [props.dataSource]);
```

### Pattern 2: Handle Actions
```typescript
const handleClick = () => {
  const action = config.actions?.click;
  
  if (action?.type === 'modal') {
    setModalOpen(true);
  } else if (action?.type === 'http') {
    fetch(action.url, {
      method: action.method,
      body: JSON.stringify(action.payload)
    });
  }
};
```

### Pattern 3: Emit Events
```typescript
const handleSearch = (value: string) => {
  window.dispatchEvent(new CustomEvent('component:search', {
    detail: {
      target: config.result?.target,  // Who should listen
      search: value
    }
  }));
};
```

### Pattern 4: Listen for Events
```typescript
useEffect(() => {
  const handler = (event: CustomEvent) => {
    if (event.detail.target === config.id) {
      // Do something with event.detail
    }
  };
  
  window.addEventListener('component:search', handler as EventListener);
  return () => window.removeEventListener('component:search', handler as EventListener);
}, [config.id]);
```

---

## Troubleshooting

### Build Fails
```bash
# Clear node modules and rebuild
rm -rf node_modules
npm install
npm run build
```

### Component Not Rendering
1. Check if component is registered in `ComponentRegistry.ts`
2. Check if type in database matches registration key
3. Check browser DevTools console for errors

### Data Not Loading
1. Verify datasource exists: `SELECT * FROM fe_ui_datasources WHERE alias = 'YourAlias'`
2. Check Network tab in DevTools for API calls
3. Verify model class exists and has data

### Modal Won't Close
Check V2ShellPage.tsx has `onOpenChange` handler that sets `modalOpen` to false.

---

## Resources

### Documentation Files
- `WIRING_COMPONENTS_GUIDE.md` - Step-by-step wiring instructions
- `V2_AGENT_PAGE_COMPLETE.md` - Architecture deep dive
- `V2_SYSTEM_UNIFIED.md` - System overview
- `SPRINT2_COMPLETE.md` - Sprint 2 delivery summary

### Reference
- Shadcn UI Docs: https://ui.shadcn.com/
- React Table Docs: https://tanstack.com/table/
- Laravel Eloquent: https://laravel.com/docs/eloquent

### Ask for Help
- Review PR #86 for full implementation
- Check `resources/js/components/v2/advanced/DataTableComponent.tsx` (263 lines) for comprehensive example
- Look at existing page configs in `fe_ui_pages` table

---

## Quick Reference: CLI Commands

```bash
# Development
npm run build                  # Build assets (use this, dev is buggy)
php artisan serve             # Start Laravel server

# Database
php artisan migrate           # Run migrations
php artisan db:seed           # Seed data

# Cache
php artisan config:clear      # Clear config cache
php artisan route:clear       # Clear route cache

# Create Resources
php artisan make:ui-page      # Create page config (if command exists)

# View Routes
php artisan route:list --path=v2
```

---

## Success Checklist

Before starting work:
- [ ] Ran demo page successfully
- [ ] Read DataTableComponent.tsx
- [ ] Read SearchBarComponent.tsx
- [ ] Reviewed page config in database
- [ ] Read WIRING_COMPONENTS_GUIDE.md

When wiring a component:
- [ ] Component accepts config prop
- [ ] DataSource fetching implemented (if applicable)
- [ ] Action handlers implemented
- [ ] Events emitted/listened (if applicable)
- [ ] Tested with database config
- [ ] Build succeeds without errors
- [ ] Documentation updated

---

Welcome to the UI Builder v2 system! Start with the demo, study the wired components, and pick a simple component to wire up. You'll be building config-driven UIs in no time! 🚀
