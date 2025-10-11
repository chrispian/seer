# Current System Architecture
## Hash: #PM-CACHE-2025-10-11

**Last Updated**: October 11, 2025  
**Status**: Authoritative Reference - Read This First

---

## ⚠️ READ THIS FIRST

**ALL** of the following tables are **CURRENT and ACTIVE**. None are legacy:
- ✅ `fragment_type_registry` - CURRENT
- ✅ `types_registry` - CURRENT  
- ✅ `commands` - CURRENT

**ONLY** these are deprecated/legacy:
- ❌ `command_registry` - OLD YAML system, being removed
- ❌ `fragments/commands/*.yaml` - OLD YAML files, being removed

---

## The Three Active Tables

### 1. `commands` Table
**Purpose**: Registry for all slash commands  
**Examples**: `/sprints`, `/tasks`, `/agents`, `/projects`, `/bookmarks`  
**Created**: October 10, 2025 (Type+Command Unification Sprint)

**Key Fields**:
```
command               VARCHAR   UNIQUE  "/sprints", "/tasks", etc.
type_slug             VARCHAR   FK      References types_registry or fragment_type_registry
handler_class         VARCHAR           "App\Commands\Orchestration\Sprint\ListCommand"
ui_modal_container    VARCHAR           "SprintListModal", "DataManagementModal"
navigation_config     JSON              Navigation metadata (item_key, detail_command, children)
available_in_slash    BOOLEAN           Can be used from web chat UI
available_in_mcp      BOOLEAN           Can be used by AI agents via MCP
available_in_cli      BOOLEAN           Can be invoked programmatically
```

**Who Uses It**:
- `CommandRegistry` service loads all active commands into cache
- `CommandController` resolves slash commands to handler classes
- `BaseCommand` composes config responses from this data

### 2. `types_registry` Table
**Purpose**: Configuration for model-backed types (types with Eloquent models)  
**Examples**: `sprint`, `task`, `agent`, `project`, `vault`  
**Created**: October 10, 2025 (Type+Command Unification Sprint)

**Key Fields**:
```
slug                         VARCHAR   UNIQUE  "sprint", "task", "agent"
display_name                 VARCHAR           "Sprint", "Task", "Agent"
plural_name                  VARCHAR           "Sprints", "Tasks", "Agents"
storage_type                 ENUM              'model' (vs 'fragment')
model_class                  VARCHAR           "App\Models\Sprint"
default_card_component       VARCHAR           "SprintCard"
default_detail_component     VARCHAR           "SprintDetailModal"
capabilities                 JSON              ["searchable", "filterable", "sortable"]
hot_fields                   JSON              ["code", "title", "status"]
```

**Who Uses It**:
- `Command` model via `type_slug` foreign key
- `BaseCommand.getTypeConfig()` returns this data in responses
- Used for types that have dedicated database tables + Eloquent models

### 3. `fragment_type_registry` Table
**Purpose**: Configuration for fragment-backed types (schema-based JSON storage)  
**Examples**: `user`, `assistant`, `system`, `bookmark`, `todo`, `note`, `log`  
**Created**: October 3, 2025

**Key Fields**:
```
slug                  VARCHAR   UNIQUE  "note", "bookmark", "todo"
display_name          VARCHAR           "Note", "Bookmark", "Todo"
plural_name           VARCHAR           "Notes", "Bookmarks", "Todos"
schema                JSON              JSON Schema for validation
container_component   VARCHAR           "DataManagementModal", "UnifiedListModal"
detail_component      VARCHAR           "UnifiedDetailModal"
capabilities          JSON              ["user_created", "taggable"]
is_enabled            BOOLEAN           Can be disabled by users
is_system             BOOLEAN           Cannot be deleted
```

**Who Uses It**:
- Fragment storage system for schemaless JSON documents
- Types that DON'T need dedicated database tables
- Can be managed through Type Management UI

---

## How They Work Together

### Example: `/sprints` Command Flow

```
1. User types: /sprints

2. CommandController receives request
   ↓
3. CommandRegistry.getCommand('sprints')
   → Queries: commands table WHERE command = '/sprints'
   → Returns: Command model (ID 1)
   ↓
4. Command model has:
   - handler_class = "App\Commands\Orchestration\Sprint\ListCommand"
   - type_slug = "sprint" (references types_registry)
   - ui_modal_container = "SprintListModal"
   - navigation_config = {data_prop: "sprints", item_key: "code", ...}
   ↓
5. BaseCommand loads Type model:
   → Queries: types_registry WHERE slug = 'sprint'
   → Returns: Type model with model_class, default components, etc.
   ↓
6. BaseCommand.getFullConfig() composes response:
   {
     config: {
       type: { slug, display_name, model_class, ... },
       ui: { modal_container, navigation, ... },
       command: { command, name, category, ... }
     },
     data: [ ...sprint data... ]
   }
   ↓
7. Frontend receives config
   → CommandResultModal.getComponentName(result)
   → Reads: config.ui.modal_container = "SprintListModal"
   → Loads: COMPONENT_MAP["SprintListModal"]
   → Renders: <SprintListModal ... />
```

### Example: Fragment Type (notes, bookmarks)

```
Fragment types work differently - they may NOT have slash commands.

If you have a /notes command:
  → Uses commands table (same as /sprints)
  → type_slug references fragment_type_registry instead of types_registry
  → Rest of flow is identical

If accessing fragments directly (not via slash command):
  → Query fragment_type_registry for container_component
  → Use that component directly
```

---

## Storage Type Differences

### Model-Backed Types (`storage_type = 'model'`)

**What**: Types with dedicated database tables and Eloquent models  
**Examples**: Sprint (sprints table), Task (work_items table), Agent (agent_profiles table)  
**Registered In**: `types_registry` table  
**Model Class**: Required (e.g., `App\Models\Sprint`)  
**Schema**: NULL (schema is database table schema)

**When to Use**:
- Complex relational data
- Need Eloquent relationships
- Performance-critical queries
- Data with specific structure

### Fragment-Backed Types (`storage_type = 'fragment'`)

**What**: Types stored as schemaless JSON in fragments table  
**Examples**: Note, Bookmark, Todo, Log  
**Registered In**: `fragment_type_registry` table  
**Model Class**: NULL (uses Fragment model)  
**Schema**: JSON Schema for validation

**When to Use**:
- Flexible/evolving data structures
- User-created content
- Simple documents
- Rapid prototyping

---

## Priority and Overrides

### Component Resolution Priority

For any command, component name is chosen by:

```
1. commands.ui_modal_container               (Highest - command-specific override)
2. commands.ui_card_component                (Transformed: Card → ListModal)
3. types_registry.default_card_component     (Type default for model types)
   OR fragment_type_registry.container_component (Type default for fragment types)
4. result.component field                    (Deprecated, being removed)
5. UnifiedListModal                          (Fallback, always works)
```

**Example**:
```
/sprints command:
  commands.ui_modal_container = "SprintListModal"  ← Use this (Priority 1)
  types_registry.default_card_component = "SprintCard"  ← Ignored (lower priority)
```

### Config Composition

`BaseCommand.php` composes the config object sent to frontend:

```php
return [
    'type' => $this->getTypeConfig(),        // From types_registry or fragment_type_registry
    'ui' => $this->getUIConfig(),            // From commands table
    'command' => [                           // From commands table
        'command' => $this->command->command,
        'name' => $this->command->name,
        ...
    ],
];
```

---

## Cache System (CRITICAL)

### CommandRegistry Cache

**Location**: `app/Services/CommandRegistry.php` line 20  
**Key**: `command_registry`  
**TTL**: 1 hour (3600 seconds)  
**Data**: All active commands with their config

**When to Clear**:
```bash
# After running CommandsSeeder
php artisan db:seed --class=CommandsSeeder
php artisan cache:clear  # ← REQUIRED

# After updating commands table manually
php artisan tinker
# > DB::table('commands')->where(...)->update(...)
# > App\Services\CommandRegistry::clearCache()
# > exit

# After migrations that affect commands/types tables
php artisan migrate
php artisan cache:clear  # ← REQUIRED
```

**Symptom of Stale Cache**:
- Wrong component loads
- Database has correct value but app shows old behavior
- Navigation broken despite correct config
- Changes don't take effect for up to 1 hour

**Debug Commands**:
```bash
# Check if cache exists
php artisan tinker
# > Cache::has('command_registry')

# Clear specific cache key
# > Cache::forget('command_registry')

# Clear all cache
php artisan cache:clear
```

### Other Caches

**Blade Views**: `storage/framework/views/`
```bash
php artisan view:clear
```

**Application Config**: `bootstrap/cache/config.php`
```bash
php artisan config:clear
```

**Vite Dev Server HMR**: `node_modules/.vite/`
```bash
rm -rf node_modules/.vite
npm run dev
```

---

## Common Confusions (What Made Investigation Hard)

### Confusion #1: "Legacy" Means Different Things

**In documentation, "legacy" refers to**:
1. YAML command system ← YES, truly deprecated
2. fragment_type_registry table ← NO, this is current!
3. Hardcoded switch statements ← YES, removed
4. Type-specific props ← YES, being phased out

**Solution**: Use precise terms:
- "Deprecated YAML system" (command_registry table)
- "Current fragment system" (fragment_type_registry table)
- "Current model system" (types_registry table)

### Confusion #2: Two Type Systems Look Redundant

**Why both `types_registry` AND `fragment_type_registry`?**

They serve different storage backends:
- `types_registry` → Eloquent models (sprints table, work_items table, etc.)
- `fragment_type_registry` → JSON fragments (fragments table with schema validation)

Both are needed. Not redundant. Different use cases.

### Confusion #3: Component Names in Multiple Places

`SprintListModal` appears in:
1. `commands.ui_modal_container` (database)
2. `CommandResultModal.tsx` COMPONENT_MAP (TypeScript)
3. File: `SprintListModal.tsx` (actual component)

**One typo anywhere = broken navigation**

**Solution**: Seeder validation (check component exists in COMPONENT_MAP)

### Confusion #4: Cache Makes Database Appear Wrong

When cache is stale:
- Database shows: `ui_modal_container = "SprintListModal"` ✅
- App uses cached: `ui_modal_container = "DataManagementModal"` ❌
- Developer checks database, sees correct value, gets confused
- Developer changes value, clears cache accidentally, it works
- Developer thinks database was wrong, but it was cache all along

**Solution**: Always check cache first when debugging data issues

---

## Quick Reference Card

### I Want To...

**Add a new slash command**:
1. Add to `database/seeders/CommandsSeeder.php`
2. Register component in `CommandResultModal.tsx` COMPONENT_MAP
3. Run: `php artisan db:seed --class=CommandsSeeder && php artisan cache:clear`

**Add a new model-backed type** (sprint, task, etc.):
1. Create Eloquent model + migration
2. Add to `database/seeders/TypesSeeder.php`  
3. Run: `php artisan migrate && php artisan db:seed --class=TypesSeeder && php artisan cache:clear`

**Add a new fragment-backed type** (note, bookmark, etc.):
1. Add to `database/seeders/SystemTypesSeeder.php`
2. Define JSON schema
3. Run: `php artisan db:seed --class=SystemTypesSeeder && php artisan cache:clear`

**Change which component a command uses**:
1. Update `database/seeders/CommandsSeeder.php`
2. Run: `php artisan db:seed --class=CommandsSeeder && php artisan cache:clear`
3. Hard refresh browser

**Debug navigation not working**:
1. Check: `php artisan cache:clear` first
2. Check: Browser console for errors
3. Check: Database has correct ui_modal_container value
4. Check: Component exists in COMPONENT_MAP
5. Check: Build succeeded (`npm run build`)

---

## Table Relationships

```
commands table
├─ type_slug FK → types_registry.slug (for model types)
└─ type_slug FK → fragment_type_registry.slug (for fragment types)

types_registry
├─ model_class → Eloquent model (App\Models\Sprint)
└─ Used for: sprints, tasks, agents, projects, vaults

fragment_type_registry
├─ schema → JSON Schema for validation
└─ Used for: notes, bookmarks, todos, logs, user/assistant/system (chat)
```

---

## File Locations

**Backend**:
```
app/Commands/                      - Command handlers
app/Services/CommandRegistry.php  - Command loading + caching
app/Models/Command.php             - commands table model
app/Models/Type.php                - types_registry table model  
app/Models/FragmentTypeRegistry.php - fragment_type_registry table model
database/seeders/CommandsSeeder.php - Seed commands table
database/seeders/TypesSeeder.php    - Seed types_registry table
database/seeders/SystemTypesSeeder.php - Seed fragment_type_registry table
```

**Frontend**:
```
resources/js/islands/chat/CommandResultModal.tsx - Component resolution + rendering
resources/js/components/orchestration/           - Sprint, Task, Agent modals
resources/js/components/projects/                - Project modals
resources/js/components/bookmarks/               - Bookmark modals
resources/js/components/unified/UnifiedListModal.tsx - Generic fallback
```

**Documentation**:
```
docs/CURRENT_SYSTEM_ARCHITECTURE.md              - This file
docs/POST_MORTEM_SPRINT_NAVIGATION_CACHE_BUG.md  - Oct 11 cache bug analysis
docs/TYPE_COMMAND_UNIFICATION_COMPLETE.md        - Unification project summary
docs/type-command-ui/                             - Config-driven UI planning docs
```

---

## Hash Tag System

All related files and changes use: **#PM-CACHE-2025-10-11**

Use this hash to find:
- Documentation about this issue
- Code comments explaining the system
- Related fixes and improvements

Search: `rg "#PM-CACHE-2025-10-11" app/ docs/ resources/`

---

## For AI Agents

### When Working With Commands

1. **Check which table** the command uses:
   - Is it a slash command? → `commands` table
   - Is it a type definition? → Check storage_type (model → `types_registry`, fragment → `fragment_type_registry`)

2. **Always clear cache** after database changes:
   ```php
   \App\Services\CommandRegistry::clearCache();
   ```

3. **Don't assume "legacy" means "unused"**:
   - Verify in code before calling something legacy
   - `fragment_type_registry` is NOT legacy despite older creation date

4. **Check cache first** when behavior doesn't match database:
   - CommandRegistry caches for 1 hour
   - Stale cache = wrong behavior
   - Clear cache, test again

5. **Read this file first** before making assumptions about system architecture

### When Creating Documentation

- Use hash tags (#PM-CACHE-2025-10-11) to link related content
- State upfront which tables are current vs deprecated
- Give examples of database values and their effects
- Explain cache implications

---

## Summary

**Three active tables**: `commands`, `types_registry`, `fragment_type_registry`  
**All are current**: None are legacy  
**Cache is critical**: Must clear after database changes  
**System is config-driven**: Component names come from database, not code

**Hash**: #PM-CACHE-2025-10-11
