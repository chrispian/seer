# Unified Type + Command System Architecture

## Core Philosophy

### Three-Layer Model
1. **Fragments** = Raw input stream (unstructured data)
   - User messages, imports, API data, file uploads
   - Gets processed → intent/entities extracted → creates typed objects
   
2. **Types** = Data definitions (structured data)
   - Model-backed (own tables): `sprint`, `task`, `agent`, `project`
   - Fragment-backed (stored as fragments): `note`, `log`, `link`, `bookmark`, `contact`
   
3. **Commands** = Action controllers (app builder layer)
   - Define operations on types (list, detail, create, edit, delete)
   - Configure UI presentation (modal, layout, cards)
   - Control availability (slash/CLI/MCP)

---

## Database Schema

### `types_registry` Table
```sql
CREATE TABLE types_registry (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    plural_name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7),
    
    -- Data Layer
    storage_type ENUM('model', 'fragment') NOT NULL,
    model_class VARCHAR(255) NULL,  -- e.g., '\App\Models\Sprint'
    schema JSON NULL,  -- For fragment-based types
    
    -- Display Defaults
    default_card_component VARCHAR(255),  -- 'SprintCard', 'NoteCard'
    default_detail_component VARCHAR(255),  -- 'SprintDetailModal'
    
    -- Capabilities
    capabilities JSON,  -- ['searchable', 'filterable', 'sortable', 'taggable']
    hot_fields JSON,  -- ['title', 'status'] - quick access fields
    
    -- System Flags
    is_enabled BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_storage_type (storage_type),
    INDEX idx_enabled (is_enabled)
);
```

### `commands` Table
```sql
CREATE TABLE commands (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    command VARCHAR(255) UNIQUE NOT NULL,  -- '/sprints', 'orchestration:sprints'
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),  -- 'Orchestration', 'Navigation', 'Admin'
    
    -- Data Binding
    type_slug VARCHAR(100),  -- FK to types_registry.slug
    handler_class VARCHAR(255) NOT NULL,  -- '\App\Commands\Sprint\ListCommand'
    
    -- Availability Flags
    available_in_slash BOOLEAN DEFAULT FALSE,
    available_in_cli BOOLEAN DEFAULT FALSE,
    available_in_mcp BOOLEAN DEFAULT FALSE,
    
    -- UI Configuration (NULL = no UI)
    ui_modal_container VARCHAR(100) NULL,  -- 'DataManagementModal', 'Dialog', 'Drawer'
    ui_layout_mode VARCHAR(50) NULL,  -- 'list', 'grid', 'table'
    ui_card_component VARCHAR(255) NULL,  -- Override type default
    ui_detail_component VARCHAR(255) NULL,  -- Override type default
    
    -- View Configuration
    filters JSON NULL,
    default_sort JSON NULL,
    pagination_default INT DEFAULT 25,
    
    -- Metadata
    usage_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (type_slug) REFERENCES types_registry(slug) ON DELETE CASCADE,
    INDEX idx_availability (available_in_slash, available_in_cli, available_in_mcp),
    INDEX idx_category (category)
);
```

---

## Example Configurations

### Model-Backed Type: Sprint
```php
// types_registry
[
    'slug' => 'sprint',
    'display_name' => 'Sprint',
    'plural_name' => 'Sprints',
    'storage_type' => 'model',
    'model_class' => '\App\Models\Sprint',
    'schema' => null,
    'default_card_component' => 'SprintCard',
    'default_detail_component' => 'SprintDetailModal',
    'capabilities' => ['searchable', 'filterable', 'sortable'],
    'hot_fields' => ['code', 'title', 'status']
]

// commands
[
    'command' => '/sprints',
    'type_slug' => 'sprint',
    'handler_class' => 'App\Commands\Orchestration\Sprint\ListCommand',
    'available_in_slash' => true,
    'available_in_cli' => false,
    'available_in_mcp' => true,
    'ui_modal_container' => 'DataManagementModal',
    'ui_layout_mode' => 'table',
    'ui_card_component' => null,  // Use type default
    'pagination_default' => 25
],
[
    'command' => '/sprint-detail',
    'type_slug' => 'sprint',
    'handler_class' => 'App\Commands\Orchestration\Sprint\DetailCommand',
    'available_in_slash' => true,
    'ui_modal_container' => 'Dialog',
    'ui_detail_component' => null  // Use type default
]
```

### Fragment-Backed Type: Note
```php
// types_registry
[
    'slug' => 'note',
    'display_name' => 'Note',
    'plural_name' => 'Notes',
    'storage_type' => 'fragment',
    'model_class' => null,
    'schema' => [
        'type' => 'object',
        'properties' => [
            'title' => ['type' => 'string', 'required' => true],
            'content' => ['type' => 'string'],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string']]
        ]
    ],
    'default_card_component' => 'NoteCard',
    'default_detail_component' => 'UnifiedDetailModal',
    'capabilities' => ['searchable', 'taggable', 'ai_processable']
]

// commands
[
    'command' => '/notes',
    'type_slug' => 'note',
    'handler_class' => 'App\Commands\Fragment\NoteListCommand',
    'available_in_slash' => true,
    'ui_modal_container' => 'DataManagementModal',
    'ui_layout_mode' => 'list'
]
```

---

## Fragment Processing Flow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER INPUT (Raw Fragment)                                │
│    - Chat message, file upload, API import                  │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. FRAGMENT PROCESSOR                                        │
│    - AI analyzes content                                     │
│    - Extracts intent, entities, metadata                     │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. INTENT ROUTER                                             │
│    - Determine what typed objects to create/update           │
└────────┬────────┬────────┬────────┬───────────────────────┘
         ↓        ↓        ↓        ↓
    ┌────────┐ ┌──────┐ ┌──────┐ ┌──────────┐
    │  Note  │ │Contact│ │ Todo │ │ Reminder │
    │(frag)  │ │(frag) │ │(model│ │ (model)  │
    └────────┘ └──────┘ └──────┘ └──────────┘
    
┌─────────────────────────────────────────────────────────────┐
│ 4. TYPED OBJECTS                                             │
│    - Stored according to storage_type                        │
│    - Available via commands                                  │
└─────────────────────────────────────────────────────────────┘

Example:
  Input: "Remind me to call John about the project tomorrow"
  
  Creates:
    1. Contact (fragment): name="John"
    2. Reminder (model): due_date=tomorrow
    3. Note (fragment): content="call about project", linked_to=[Contact, Reminder]
```

---

## Future: Recall Views System 🚀

### Concept
**Recall Views** = User-defined query templates + markdown layouts for data retrieval

### Use Case
```
User: "/getNews {input}"
Input: "Get me the last 15 news articles on AI development"

Without View:
  - AI generates arbitrary response
  - Costs tokens for generation
  - Inconsistent format

With Saved View:
  - Converts NL → stored query (one-time LLM cost)
  - Reusable template applied
  - Consistent output format
  - Lower token costs
```

### Proposed Schema (Future)
```sql
CREATE TABLE recall_views (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Query Definition
    query_type ENUM('sql', 'eloquent', 'vector_search') NOT NULL,
    query_template TEXT NOT NULL,  -- Parameterized query
    query_params JSON,  -- Parameter definitions
    
    -- Output Definition
    output_format ENUM('markdown', 'json', 'html', 'table') NOT NULL,
    markdown_template TEXT NULL,  -- Jinja/Blade-style template
    
    -- Type Binding
    type_slug VARCHAR(100),  -- Can query specific types
    
    -- Metadata
    is_public BOOLEAN DEFAULT FALSE,
    created_by BIGINT,
    usage_count INT DEFAULT 0,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (type_slug) REFERENCES types_registry(slug),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Example Recall View
```php
[
    'slug' => 'news-ai-articles',
    'name' => 'AI News Articles',
    'query_type' => 'eloquent',
    'query_template' => 'Fragment::where("type", "news")->where("tags", "contains", "AI")->latest()->take({limit})',
    'query_params' => [
        'limit' => ['type' => 'integer', 'default' => 15]
    ],
    'output_format' => 'markdown',
    'markdown_template' => <<<MD
# AI News Articles

{% for article in results %}
## {{ article.title }}
*Published: {{ article.published_at }}*

{{ article.summary }}

[Read more]({{ article.url }})

---
{% endfor %}
MD
]
```

### Integration with Commands
```php
// commands table - add recall_view_slug field in future migration
[
    'command' => '/getNews',
    'type_slug' => 'news',
    'handler_class' => 'App\Commands\Recall\RecallViewCommand',
    'recall_view_slug' => 'news-ai-articles',  // NEW FIELD
    'available_in_slash' => true
]
```

### Benefits
✅ **Token savings**: Reusable queries, no generation  
✅ **Consistency**: Same format every time  
✅ **Flexibility**: Users customize their own views  
✅ **Composability**: Views can be combined in commands  
✅ **Export ready**: Views = export templates  

### Implementation Notes for Later
- Recall views are like "Laravel Blade components for data"
- Users can create via UI (query builder + template editor)
- Views can be shared (is_public flag)
- Commands can reference views (recall_view_slug FK)
- Export system can use same view templates
- Jinja2-style templating (familiar to users, secure)

---

## Migration Sprint Plan

### Pre-Sprint Prep
- [x] Document architecture
- [x] Document recall views concept
- [ ] Create migration files (types_registry, commands)
- [ ] Create seed data (existing types + commands)

### Sprint 1: Schema & DB Foundation (2-3 hours)
**Tasks:**
- [ ] T-UNIFY-01: Create `types_registry` migration
  - Add storage_type, model_class, schema fields
  - Remove old UI routing fields (container_component, row_display_mode, etc.)
  - Create indexes
- [ ] T-UNIFY-02: Create `commands` table migration
  - Full schema with availability flags, UI config, view config
  - Add foreign keys to types_registry
- [ ] T-UNIFY-03: Create Type model (`App\Models\Type`)
  - Scopes: enabled, model-backed, fragment-backed
  - Relationships: commands, fragments (for fragment-backed types)
- [ ] T-UNIFY-04: Create Command model (`App\Models\Command`)
  - Scopes: available_in_X, by_category
  - Relationships: type
  - Accessors: full config array for handler
- [ ] T-UNIFY-05: Seed initial data
  - Migrate existing `fragment_type_registry` data
  - Create command entries for all existing commands
  - Map hard-coded components to DB config

**Deliverable:** Clean DB schema with seeded data

---

### Sprint 2: Command System Refactor (3-4 hours)
**Tasks:**
- [ ] T-UNIFY-06: Update `CommandRegistry` service
  - Load commands from DB instead of reflection
  - Filter by availability (slash/CLI/MCP)
  - Cache command definitions
- [ ] T-UNIFY-07: Update `BaseCommand` abstract class
  - Accept Type model in constructor
  - Provide helper methods: getTypeConfig(), getUIConfig()
  - Auto-inject type config into response
- [ ] T-UNIFY-08: Update existing command classes
  - Remove hard-coded component names
  - Use type config for data structure
  - Return unified response format: `['type' => $type, 'data' => $data]`
- [ ] T-UNIFY-09: Update command execution flow
  - `CommandController` injects Type model
  - `McpCommandHandler` respects availability flags
  - CLI commands filter by available_in_cli

**Deliverable:** All commands load from DB, use type config

---

### Sprint 3: Frontend Integration (2-3 hours)
**Tasks:**
- [ ] T-UNIFY-10: Update `CommandResultModal.tsx`
  - Remove hard-coded component switch statement
  - Use `typeConfig.container_component` + `command.ui_modal_container`
  - Use `typeConfig.layout_mode` + `command.ui_layout_mode`
  - Support overrides (command > type > default)
- [ ] T-UNIFY-11: Create missing card components
  - Audit types: ensure all have card components
  - Create defaults where missing (ProjectCard, VaultCard, BookmarkCard)
- [ ] T-UNIFY-12: Update `UnifiedListModal` component
  - Handle both storage types (model vs fragment)
  - Use command config for filters, sorts
  - Dynamic card rendering based on type config
- [ ] T-UNIFY-13: Create `TypeConfigProvider` context
  - Provide type config to nested components
  - Handle card overrides
  - Manage modal state

**Deliverable:** All slash commands render correctly with DB-driven UI

---

### Sprint 4: CLI & MCP Integration (1-2 hours)
**Tasks:**
- [ ] T-UNIFY-14: Update CLI command discovery
  - Filter `commands` where `available_in_cli = true`
  - Generate artisan signatures from DB
  - Update command listing (`php artisan list`)
- [ ] T-UNIFY-15: Update MCP tool generation
  - Filter `commands` where `available_in_mcp = true`
  - Generate tool definitions from DB
  - Map command params to MCP input schema
- [ ] T-UNIFY-16: Test cross-interface consistency
  - Same command via slash, CLI, MCP
  - Verify data consistency
  - Verify response format

**Deliverable:** Commands work identically across all interfaces

---

### Sprint 5: Cleanup & Documentation (1-2 hours)
**Tasks:**
- [ ] T-UNIFY-17: Remove deprecated code
  - Old `fragment_type_registry` UI fields
  - `TypePackLoader`, `TypePackManager` classes
  - Hard-coded component mappings
- [ ] T-UNIFY-18: Update command development guide
  - How to create new types
  - How to create new commands
  - Type config reference
  - Command config reference
- [ ] T-UNIFY-19: Create admin UI for types/commands
  - CRUD for types_registry
  - CRUD for commands
  - Test command execution
- [ ] T-UNIFY-20: Update existing documentation
  - CLAUDE.md
  - COMMAND_DEVELOPMENT_GUIDE.md
  - Migration notes

**Deliverable:** Clean codebase, updated docs, admin tools

---

## Post-Sprint: Future Enhancements

### Phase 2: User-Defined Types (Future)
- UI for creating fragment-based types
- Schema builder (drag-drop field definitions)
- Auto-generate commands for new types
- Type inheritance/composition

### Phase 3: Recall Views System (Future)
- Implement `recall_views` table
- Query template engine
- Markdown template renderer
- View builder UI
- Integration with commands
- Export system using views

### Phase 4: Advanced Features (Future)
- Command versioning
- Command permissions/ACL
- Type migrations (schema evolution)
- Command analytics
- A/B testing for UI configs

---

## Key Decisions Summary

✅ **Types define data** (model or fragment-backed)  
✅ **Commands define actions** (controller layer)  
✅ **Fragments are raw input** (processed → typed objects)  
✅ **storage_type distinction** (model vs fragment)  
✅ **Availability flags** (slash/CLI/MCP)  
✅ **UI overrides** (command > type > default)  
✅ **Recall views deferred** (architecture documented for future)  
✅ **User-defined types deferred** (once core is stable)  

---

## Files to Create This Weekend

### Migrations
- `database/migrations/YYYY_MM_DD_create_types_registry_table.php`
- `database/migrations/YYYY_MM_DD_create_commands_table.php`
- `database/migrations/YYYY_MM_DD_migrate_fragment_types_to_types.php`

### Models
- `app/Models/Type.php`
- `app/Models/Command.php`

### Seeders
- `database/seeders/TypesSeeder.php`
- `database/seeders/CommandsSeeder.php`

### Services
- Update `app/Services/CommandRegistry.php`

### Documentation
- `docs/UNIFIED_ARCHITECTURE.md` (this document)
- `docs/TYPE_COMMAND_SYSTEM.md` (developer guide)
- `docs/RECALL_VIEWS_SPEC.md` (future spec)

---

**Total Estimate: 9-14 hours**  
**Can be completed in 2-3 focused sessions over the weekend** 🚀

---

## Architecture Benefits

### For Developers
- **Single source of truth**: Commands table defines all routes
- **No magic**: Explicit configuration over convention
- **Type safety**: Strong typing between types and commands
- **Testable**: Mock command/type configs easily

### For Users
- **Consistent UX**: Same patterns across all commands
- **Customizable**: Override display for specific workflows
- **Discoverable**: All commands in one place
- **Flexible**: User-defined types and views (future)

### For System
- **Scalable**: Add new types/commands without code changes
- **Maintainable**: Clear separation of concerns
- **Performant**: DB queries cached, no reflection overhead
- **Extensible**: Plugin system via commands table

---

**Document Version:** 1.0  
**Last Updated:** 2025-10-10  
**Status:** Planning Phase - Ready for Implementation
