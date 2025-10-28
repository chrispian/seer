# UI Builder API Documentation

**Version:** 2.1.0  
**Base URL:** `http://seer.test/api/ui`  
**Format:** JSON

---

## Components API

### Get All Components

```http
GET /api/ui/datasources/UiComponent
```

**Query Parameters:**
- `search` (optional) - Search term
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 15)
- `sort` (optional) - Sort field (default: updated_at)
- `direction` (optional) - Sort direction: asc|desc (default: desc)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "key": "component.button.default",
      "type": "button",
      "kind": "primitive",
      "variant": "default",
      "config": {},
      "schema_json": {
        "props": {
          "label": { "type": "string", "required": true },
          "onClick": { "type": "action" }
        }
      },
      "defaults_json": {
        "label": "Click me"
      },
      "capabilities_json": ["clickable", "styled"],
      "version": 1,
      "hash": "abc123...",
      "created_at": "2025-10-28T10:00:00Z",
      "updated_at": "2025-10-28T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 75,
    "last_page": 5
  }
}
```

### Get Single Component

```http
GET /api/ui/datasources/UiComponent/{id}
```

**Response:**
```json
{
  "id": 1,
  "key": "component.button.default",
  "type": "button",
  "kind": "primitive",
  "variant": "default",
  "config": {},
  "schema_json": { ... },
  "defaults_json": { ... },
  "capabilities_json": [ ... ],
  "version": 1,
  "hash": "abc123...",
  "created_at": "2025-10-28T10:00:00Z",
  "updated_at": "2025-10-28T10:00:00Z"
}
```

### Create Component

```http
POST /api/ui/datasources/UiComponent
Content-Type: application/json
```

**Request Body:**
```json
{
  "key": "component.card.custom",
  "type": "card",
  "kind": "composite",
  "variant": "outlined",
  "config": {},
  "schema_json": {
    "props": {
      "title": { "type": "string" },
      "content": { "type": "string" }
    }
  },
  "defaults_json": {
    "title": "Default Title"
  },
  "capabilities_json": ["expandable", "closeable"]
}
```

**Response:** `201 Created` with created component object

### Update Component

```http
PUT /api/ui/datasources/UiComponent/{id}
Content-Type: application/json
```

**Request Body:** Same as Create (all fields)

**Response:** `200 OK` with updated component object

### Delete Component

```http
DELETE /api/ui/datasources/UiComponent/{id}
```

**Response:** 
```json
{
  "success": true,
  "message": "Record deleted successfully"
}
```

---

## Pages API

### Get All Pages

```http
GET /api/ui/datasources/UiPage
```

Same query parameters as Components API.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "key": "page.ui-builder.pages.list",
      "route": "/ui/pages",
      "module_key": "core.ui-builder",
      "enabled": true,
      "config": { ... },
      "meta_json": {},
      "guards_json": [],
      "version": 20,
      "hash": "5b1fdb9b...",
      "created_at": "2025-10-15T05:55:03Z",
      "updated_at": "2025-10-17T13:51:49Z"
    }
  ],
  "meta": { ... }
}
```

### Get Single Page

```http
GET /api/ui/pages/{key}
```

**Example:**
```http
GET /api/ui/pages/page.ui-builder.pages.list
```

**Response:** Full page configuration object (see Page Config Structure below)

### Create Page

```http
POST /api/ui/datasources/UiPage
Content-Type: application/json
```

**Request Body:**
```json
{
  "key": "page.custom.dashboard",
  "route": "/dashboard",
  "module_key": "core.app",
  "enabled": true,
  "config": {
    "id": "page.custom.dashboard",
    "overlay": "page",
    "title": "Dashboard",
    "layout": {
      "type": "rows",
      "id": "root-layout",
      "children": [ ... ]
    }
  },
  "meta_json": {
    "description": "Custom dashboard page"
  },
  "guards_json": ["auth"]
}
```

**Response:** `201 Created` with created page object

---

## Page Config Structure

Pages use a hierarchical component structure:

```typescript
interface PageConfig {
  id: string;                    // Unique page identifier
  overlay: "page" | "modal" | "drawer";
  title: string;
  layout: LayoutConfig;
  meta?: Record<string, any>;
}

interface LayoutConfig {
  type: "rows" | "columns" | "grid" | "stack";
  id: string;
  children: ComponentConfig[];
  props?: Record<string, any>;
}

interface ComponentConfig {
  id: string;                    // Unique component instance ID
  type: string;                  // Component type (button, data-table, search.bar, etc.)
  props?: Record<string, any>;   // Component-specific properties
  actions?: {                    // Optional action handlers
    click?: ActionConfig;
    rowClick?: ActionConfig;
  };
  result?: {                     // For search components
    target: string;              // Target component ID
    open: "inline" | "modal";
  };
  children?: ComponentConfig[];  // For container components
}

interface ActionConfig {
  type: "command" | "modal" | "navigate" | "http";
  command?: string;              // For type: command
  url?: string;                  // For type: modal, navigate, http
  title?: string;                // For type: modal
  fields?: FieldConfig[];        // For type: modal
  method?: "GET" | "POST" | "PUT" | "DELETE";  // For type: http
  payload?: Record<string, any>;
}

interface FieldConfig {
  key: string;                   // Data key to display
  label: string;                 // Display label
  type: "text" | "date" | "number" | "boolean";
}
```

### Example: Data Table Component

```json
{
  "id": "component.table.agents",
  "type": "data-table",
  "props": {
    "dataSource": "Agent",
    "columns": [
      {
        "key": "name",
        "label": "Name",
        "sortable": true
      },
      {
        "key": "status",
        "label": "Status",
        "filterable": true
      }
    ],
    "rowAction": {
      "type": "modal",
      "title": "Agent Details",
      "url": "/api/ui/datasources/Agent/{{row.id}}",
      "fields": [
        {
          "key": "name",
          "label": "Name",
          "type": "text"
        },
        {
          "key": "created_at",
          "label": "Created",
          "type": "date"
        }
      ]
    }
  }
}
```

### Example: Search Bar Component

```json
{
  "id": "component.search.bar.agents",
  "type": "search.bar",
  "props": {
    "placeholder": "Search agents..."
  },
  "result": {
    "target": "component.table.agents",
    "open": "inline"
  }
}
```

### Example: Button Component

```json
{
  "id": "component.button.create",
  "type": "button",
  "props": {
    "label": "Create New",
    "variant": "primary"
  },
  "actions": {
    "click": {
      "type": "modal",
      "title": "Create Agent",
      "url": "/api/ui/forms/create-agent"
    }
  }
}
```

---

## Component Types Reference

### Available Component Types

| Type | Kind | Description |
|------|------|-------------|
| `button` | primitive | Clickable button |
| `button.icon` | primitive | Icon-only button |
| `input` | primitive | Text input field |
| `label` | primitive | Text label |
| `badge` | primitive | Status badge |
| `data-table` | composite | Data table with sorting/filtering |
| `search.bar` | composite | Search input with results |
| `card` | composite | Container card |
| `form` | composite | Form container |
| `modal` | composite | Modal dialog |

### Component Kinds

- **primitive**: Basic UI elements (button, input, label)
- **composite**: Complex components built from primitives (data-table, form)
- **container**: Layout components (card, panel, section)

---

## Database Schema

### fe_ui_components

```sql
CREATE TABLE fe_ui_components (
  id BIGINT PRIMARY KEY,
  key VARCHAR(255) UNIQUE NOT NULL,      -- component.button.default
  type VARCHAR(100) NOT NULL,             -- button, input, card, etc.
  kind VARCHAR(50) NOT NULL,              -- primitive, composite, container
  variant VARCHAR(50),                    -- default, primary, outlined, etc.
  config JSON,                            -- Additional component config
  schema_json JSON,                       -- Props schema definition
  defaults_json JSON,                     -- Default prop values
  capabilities_json JSON,                 -- Component capabilities array
  hash VARCHAR(64),                       -- SHA-256 hash of config
  version INT DEFAULT 1,                  -- Auto-incremented on changes
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### fe_ui_pages

```sql
CREATE TABLE fe_ui_pages (
  id BIGINT PRIMARY KEY,
  key VARCHAR(255) UNIQUE NOT NULL,      -- page.ui-builder.pages.list
  route VARCHAR(255),                     -- /ui/pages (optional)
  module_key VARCHAR(255),                -- core.ui-builder
  enabled BOOLEAN DEFAULT true,
  config JSON NOT NULL,                   -- Full page configuration
  meta_json JSON,                         -- Metadata (description, etc.)
  guards_json JSON,                       -- Auth guards array
  hash VARCHAR(64),                       -- SHA-256 hash of config
  version INT DEFAULT 1,                  -- Auto-incremented on changes
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Complete Page Example

```json
{
  "id": "page.custom.agents",
  "overlay": "page",
  "title": "Agents Management",
  "layout": {
    "type": "rows",
    "id": "root-layout",
    "children": [
      {
        "id": "toolbar",
        "type": "toolbar",
        "props": {
          "items": [
            {
              "id": "btn-create",
              "type": "button",
              "props": {
                "label": "Create Agent",
                "variant": "primary"
              },
              "actions": {
                "click": {
                  "type": "command",
                  "command": "agent:create"
                }
              }
            }
          ]
        }
      },
      {
        "id": "search-bar",
        "type": "search.bar",
        "props": {
          "placeholder": "Search agents..."
        },
        "result": {
          "target": "agents-table",
          "open": "inline"
        }
      },
      {
        "id": "agents-table",
        "type": "data-table",
        "props": {
          "dataSource": "Agent",
          "columns": [
            {
              "key": "name",
              "label": "Name",
              "sortable": true
            },
            {
              "key": "designation",
              "label": "Designation",
              "sortable": true
            },
            {
              "key": "status",
              "label": "Status",
              "filterable": true
            }
          ],
          "rowAction": {
            "type": "modal",
            "title": "Agent Details",
            "url": "/api/ui/datasources/Agent/{{row.id}}",
            "fields": [
              {
                "key": "name",
                "label": "Name",
                "type": "text"
              },
              {
                "key": "designation",
                "label": "Designation",
                "type": "text"
              },
              {
                "key": "status",
                "label": "Status",
                "type": "text"
              }
            ]
          }
        }
      }
    ]
  }
}
```

---

## Validation & Best Practices

### Component Keys
- Format: `component.{type}.{variant}`
- Example: `component.button.primary`
- Must be unique across all components

### Page Keys
- Format: `page.{module}.{name}`
- Example: `page.ui-builder.pages.list`
- Must be unique across all pages

### Component IDs (in page config)
- Format: `component.{type}.{instance}`
- Example: `component.table.agents`
- Must be unique within a page
- Used for targeting (search results, actions)

### Data Sources
Available via `dataSource` prop in data-table:
- `Agent` - Orchestration agents
- `UiPage` - UI pages
- `UiComponent` - UI components
- `UiModule` - UI modules

### Hash & Version
- `hash`: SHA-256 of config JSON (auto-generated on save)
- `version`: Auto-incremented when hash changes
- Used for change detection and caching

---

## Error Responses

```json
{
  "error": "Error message here"
}
```

**Common HTTP Status Codes:**
- `200 OK` - Success
- `201 Created` - Resource created
- `400 Bad Request` - Invalid input
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

---

## Need Help?

- Check existing pages: `GET /api/ui/datasources/UiPage`
- Check available components: `GET /api/ui/datasources/UiComponent`
- View live example: `GET /api/ui/pages/page.ui-builder.pages.list`
