# Command Architecture Refactor - Final Recommendation

**Date**: 2025-10-07  
**Status**: Recommendation Ready for Approval

---

## Current System Analysis

### What Exists Today

✅ **Type System is YAML-based** (Working)
- `fragments/types/{slug}/type.yaml` - Type manifest
- `FragmentTypeRegistry` model - DB cache of types
- `TypePackLoader` service - Loads YAML with caching
- Hot fields, capabilities, schema all in YAML
- Works well for fragment classification

❌ **Commands are Hard-Coded** (Brittle)
- Each command defines component manually
- No connection to type system
- 10+ duplicate modals
- No clear User/Agent separation

---

## Recommendation: Hybrid Approach

**Use what works, fix what's broken:**

1. **Keep YAML for type definitions** ✅ (proven, working)
2. **Add PHP classes for command behavior** ✅ (type-safe, IDE-friendly)
3. **Store UI configs in DB** ✅ (runtime editable, fast)
4. **Namespace commands by audience** ✅ (clear separation)

---

## Part 1: Type System Enhancement

### Keep YAML for Type Manifests

**Why**: Already working, version-controlled, easy to edit

**Extend** `fragments/types/{slug}/type.yaml`:

```yaml
name: "Bookmark"
version: "2.0.0"

# Existing (keep as-is)
capabilities:
  - "state_validation"
  - "hot_fields"

# NEW: List view defaults
ui:
  list:
    layout: "table"        # Default layout
    per_page: 50          # Default pagination
    sortable: true
    filterable: true
    searchable: true
    
  # Reference to PHP config class (optional override)
  config_class: "App\\Types\\Bookmark\\BookmarkTypeConfig"

behaviors:
  - "linkable"           # Has external URLs
  - "categorizable"      # Has categories
```

### Add PHP Config Classes (Optional per Type)

**Why**: Type-safe, IDE autocomplete, complex logic support

**Location**: `app/Types/{Type}/{Type}TypeConfig.php`

```php
<?php

namespace App\Types\Bookmark;

use App\Types\Contracts\TypeConfigInterface;
use App\Types\BaseTypeConfig;

/**
 * Bookmark Type Configuration
 * 
 * Defines display and behavior for bookmark fragments.
 * Used by User commands (bookmark list, search, etc.)
 * 
 * @see fragments/types/bookmark/type.yaml
 */
class BookmarkTypeConfig extends BaseTypeConfig implements TypeConfigInterface
{
    /**
     * Get table columns for list view
     */
    public function getListColumns(): array
    {
        return [
            [
                'key' => 'title',
                'label' => 'Bookmark',
                'sortable' => true,
                'width' => 'flex-1',
                'render' => 'link',  // Special renderer
            ],
            [
                'key' => 'category',
                'label' => 'Category',
                'sortable' => true,
                'width' => 'w-24',
                'render' => 'badge',
            ],
            [
                'key' => 'last_viewed_at',
                'label' => 'Last Viewed',
                'sortable' => true,
                'width' => 'w-32',
                'render' => 'date_human',
            ],
        ];
    }
    
    /**
     * Get filter definitions
     */
    public function getFilters(): array
    {
        return [
            [
                'key' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'options' => 'dynamic', // Loaded from data
            ],
        ];
    }
    
    /**
     * Get row actions
     */
    public function getActions(): array
    {
        return [
            [
                'key' => 'open_link',
                'label' => 'Open',
                'icon' => 'external-link',
                'handler' => fn($item) => $item->metadata['url'] ?? null,
            ],
            [
                'key' => 'edit',
                'label' => 'Edit',
                'icon' => 'pencil',
            ],
        ];
    }
    
    /**
     * Get default sort
     */
    public function getDefaultSort(): array
    {
        return ['created_at', 'desc'];
    }
}
```

### Cache Configs in Database

**Why**: Fast lookup, runtime editable via admin UI

**Table**: `fragment_type_configs`

```sql
CREATE TABLE fragment_type_configs (
    id BIGINT PRIMARY KEY,
    type_slug VARCHAR(255) NOT NULL UNIQUE,
    config_class VARCHAR(255),        -- PHP class name
    list_layout VARCHAR(50),          -- table|grid|bento|card
    list_columns JSON,                -- Column definitions
    filters JSON,                     -- Filter definitions
    actions JSON,                     -- Action definitions
    default_sort JSON,                -- Default sort
    pagination_default INT DEFAULT 50,
    cached_at TIMESTAMP,
    INDEX(type_slug)
);
```

**Loading Priority**:
1. Database (fastest, runtime editable)
2. PHP class (type-safe, if exists)
3. YAML defaults (fallback)

---

## Part 2: Command Namespace Structure

### Problem: No Clear Separation

Current: All commands in `app/Commands/`
- Hard to distinguish User vs Agent commands
- No subsystem organization
- Confusing for AI agents

### Solution: Namespace by Audience + Subsystem

```
app/
  Commands/
    User/
      Fragments/
        SearchCommand.php
        InboxCommand.php
        RecallCommand.php
      Library/
        BookmarkListCommand.php
        VaultListCommand.php
        ProjectListCommand.php
      Session/
        SessionListCommand.php
        ChannelsCommand.php
      Productivity/
        TodoCommand.php
      System/
        HelpCommand.php
        ContextCommand.php
        
    Agent/
      Orchestration/
        TaskListCommand.php
        BacklogListCommand.php
        SprintListCommand.php
        SprintDetailCommand.php
        TaskDetailCommand.php
      Registry/
        AgentListCommand.php
        
    Shared/
      BaseCommand.php
      BaseListCommand.php
      BaseDetailCommand.php
      
    Concerns/
      ProvidesListData.php
      ProvidesPagination.php
```

### Naming Convention

**For User Commands**: `{Feature}Command`
- ✅ `BookmarkListCommand` - Clear what it does
- ✅ `SearchCommand` - Simple, descriptive
- ❌ `BookmarkListUserCommand` - Redundant (namespace already says User)

**For Agent Commands**: `{Feature}Command`
- ✅ `TaskListCommand` - Clear what it does
- ✅ `SprintDetailCommand` - Simple, descriptive
- ❌ `TaskListAgentCommand` - Redundant (namespace already says Agent)

**Namespace is the indicator, not suffix!**

### DocBlocks (Critical for AI)

```php
<?php

namespace App\Commands\User\Library;

use App\Commands\Shared\BaseListCommand;

/**
 * Bookmark List Command
 * 
 * **Audience**: User-facing
 * **Purpose**: Display user's saved bookmarks with search/filter
 * **Type**: bookmark (see fragments/types/bookmark/type.yaml)
 * **Modal**: UnifiedListModal (table layout)
 * **Subsystem**: Library Management
 * 
 * Returns paginated list of bookmark fragments with metadata
 * including URL, category, last viewed, and view count.
 * 
 * @usage /bookmarks
 * @usage /bookmark-list
 * @example /bookmarks --sort=last_viewed_at --filter[category]=work
 */
class BookmarkListCommand extends BaseListCommand
{
    protected string $type = 'bookmark';
    
    protected function buildQuery($builder)
    {
        return $builder->where('type', 'bookmark');
    }
}
```

```php
<?php

namespace App\Commands\Agent\Orchestration;

use App\Commands\Shared\BaseListCommand;

/**
 * Task List Command
 * 
 * **Audience**: Agent-facing (Orchestration System)
 * **Purpose**: List sprint tasks for agent assignment/tracking
 * **Type**: task (see fragments/types/task/type.yaml)
 * **Modal**: UnifiedListModal (table layout with orchestration template)
 * **Subsystem**: Sprint/Task Orchestration
 * 
 * Returns paginated work items with delegation metadata.
 * Used by agents to view sprint tasks, backlog, assignments.
 * 
 * @usage /tasks
 * @usage /tasks [sprint_code]
 * @example /tasks SPRINT-67
 */
class TaskListCommand extends BaseListCommand
{
    protected string $type = 'task';
    protected string $modelClass = \App\Models\WorkItem::class;
    
    protected function buildQuery($builder)
    {
        $sprintFilter = $this->getSprintFilter();
        
        if ($sprintFilter) {
            $builder->whereJsonContains('metadata->sprint_code', $sprintFilter);
        } else {
            $builder->whereNotNull('metadata->sprint_code');
        }
        
        return $builder->orderByRaw(
            "CASE WHEN status = 'todo' THEN 1 
                  WHEN status = 'backlog' THEN 2 
                  ELSE 3 END"
        );
    }
}
```

---

## Part 3: Base Command Classes

### BaseListCommand (Generic List Handler)

```php
<?php

namespace App\Commands\Shared;

use App\Services\TypeSystem\TypeConfigService;

/**
 * Base List Command
 * 
 * Provides standard list command behavior:
 * - Pagination
 * - Sorting  
 * - Filtering
 * - Type-driven UI config
 * 
 * Subclasses only need to define:
 * - $type (fragment type slug)
 * - buildQuery() (query customization)
 */
abstract class BaseListCommand extends BaseCommand
{
    protected string $type;  // Required: fragment type
    protected string $modelClass = \App\Models\Fragment::class;
    protected array $with = [];
    protected ?int $defaultPerPage = null;
    protected ?string $defaultSort = null;
    protected ?string $defaultSortDir = null;
    
    public function handle(): array
    {
        // Load type config (DB → PHP → YAML)
        $typeConfig = app(TypeConfigService::class)->get($this->type);
        
        // Build query
        $query = $this->modelClass::query();
        
        if (!empty($this->with)) {
            $query->with($this->with);
        }
        
        $query = $this->buildQuery($query);
        
        // Apply pagination
        $page = (int) request()->get('page', 1);
        $perPage = (int) request()->get('per_page', 
            $this->defaultPerPage ?? $typeConfig->pagination_default ?? 50
        );
        
        // Apply sorting
        $sortBy = request()->get('sort_by', 
            $this->defaultSort ?? $typeConfig->default_sort[0] ?? 'created_at'
        );
        $sortDir = request()->get('sort_dir',
            $this->defaultSortDir ?? $typeConfig->default_sort[1] ?? 'desc'
        );
        
        $query->orderBy($sortBy, $sortDir);
        
        // Get total
        $total = $query->count();
        
        // Apply pagination
        $items = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        // Transform
        $data = $this->transformItems($items);
        
        // Return standardized response
        return [
            'type' => $this->type,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'has_more' => $page < ceil($total / $perPage),
            ],
            'config' => $typeConfig->toArray(), // UI config for frontend
        ];
    }
    
    /**
     * Subclasses customize query
     */
    protected function buildQuery($builder)
    {
        return $builder;
    }
    
    /**
     * Transform items for response
     */
    protected function transformItems($items): array
    {
        return $items->toArray();
    }
}
```

---

## Part 4: Type Config Service

```php
<?php

namespace App\Services\TypeSystem;

use App\Models\FragmentTypeConfig;
use Illuminate\Support\Facades\Cache;

/**
 * Type Configuration Service
 * 
 * Loads type configurations with priority:
 * 1. Database (cached, runtime editable)
 * 2. PHP Config Class (type-safe)
 * 3. YAML Manifest (default fallback)
 */
class TypeConfigService
{
    public function get(string $typeSlug): TypeConfig
    {
        return Cache::remember("type_config.{$typeSlug}", 3600, function () use ($typeSlug) {
            // Try DB first
            $dbConfig = FragmentTypeConfig::where('type_slug', $typeSlug)->first();
            if ($dbConfig) {
                return $this->loadFromDatabase($dbConfig);
            }
            
            // Try PHP class
            $typeConfig = $this->loadFromClass($typeSlug);
            if ($typeConfig) {
                return $typeConfig;
            }
            
            // Fallback to YAML
            return $this->loadFromYaml($typeSlug);
        });
    }
    
    protected function loadFromDatabase(FragmentTypeConfig $config): TypeConfig
    {
        return new TypeConfig([
            'type' => $config->type_slug,
            'list_layout' => $config->list_layout,
            'list_columns' => $config->list_columns,
            'filters' => $config->filters,
            'actions' => $config->actions,
            'default_sort' => $config->default_sort,
            'pagination_default' => $config->pagination_default,
        ]);
    }
    
    protected function loadFromClass(string $typeSlug): ?TypeConfig
    {
        $className = "App\\Types\\" . studly_case($typeSlug) . "\\" . studly_case($typeSlug) . "TypeConfig";
        
        if (!class_exists($className)) {
            return null;
        }
        
        $instance = new $className();
        return new TypeConfig([
            'type' => $typeSlug,
            'list_columns' => $instance->getListColumns(),
            'filters' => $instance->getFilters(),
            'actions' => $instance->getActions(),
            'default_sort' => $instance->getDefaultSort(),
        ]);
    }
    
    protected function loadFromYaml(string $typeSlug): TypeConfig
    {
        $typePack = app(TypePackLoader::class)->loadTypePack($typeSlug);
        
        return new TypeConfig([
            'type' => $typeSlug,
            'list_layout' => $typePack['manifest']['ui']['list']['layout'] ?? 'table',
            'pagination_default' => $typePack['manifest']['ui']['list']['per_page'] ?? 50,
            // Minimal defaults
        ]);
    }
}
```

---

## Migration Path

### Phase 1: Infrastructure (Week 1)
1. Create namespace directories
2. Create `BaseListCommand`
3. Create `TypeConfigService`
4. Create `FragmentTypeConfig` migration
5. Create `UnifiedListModal` component

### Phase 2: Migrate 1 Command (Proof of Concept)
1. Move `BookmarkListCommand` → `App\Commands\User\Library\`
2. Convert to extend `BaseListCommand`
3. Create `BookmarkTypeConfig` (optional)
4. Test thoroughly
5. Document pattern

### Phase 3: Batch Migration (Week 2)
1. **User/Fragments**: SearchCommand, InboxCommand, RecallCommand
2. **User/Library**: VaultListCommand, ProjectListCommand
3. **User/Productivity**: TodoCommand
4. **Agent/Orchestration**: TaskListCommand, BacklogListCommand, SprintListCommand

### Phase 4: Cleanup (Week 3)
1. Remove old modal files
2. Update CommandResultModal routing
3. Update command registry
4. Update docs
5. Update scaffold command

---

## Why This Approach Wins

### 1. **Best of All Worlds**
✅ YAML for simple defaults (proven, working)  
✅ PHP classes for complex logic (type-safe, testable)  
✅ DB for runtime editing (fast, admin-friendly)

### 2. **Clear for AI Agents**
✅ Namespace tells audience immediately  
✅ DocBlocks explain purpose/usage  
✅ Base class handles boilerplate  
✅ Type config drives UI  

### 3. **Easy to Extend**
```bash
php artisan frag:command:make bookmark-list \
  --type=bookmark \
  --namespace=User/Library \
  --model=Fragment
  
# Generates:
# app/Commands/User/Library/BookmarkListCommand.php (10 lines)
# Tests
# Registers in CommandRegistry
```

### 4. **Can't Break It**
- Type config in YAML/DB/Class (redundant safety)
- Base class handles all boilerplate
- AI agents can't accidentally break pagination/sorting
- Clear namespace prevents confusion

---

## Recommendation Summary

**DO THIS:**

1. ✅ **Namespace commands** by `User/Agent` + subsystem
2. ✅ **Use PHP config classes** for type behavior (optional, per-type)
3. ✅ **Cache configs in DB** for performance + runtime editing
4. ✅ **Keep YAML** for type manifests (already working)
5. ✅ **Add clear DocBlocks** for AI agents
6. ✅ **Create BaseListCommand** to eliminate boilerplate
7. ✅ **UnifiedListModal** reads type config, renders accordingly

**DON'T DO THIS:**
- ❌ Suffix commands with `UserCommand`/`AgentCommand` (namespace handles it)
- ❌ Pure YAML config (too rigid for complex behavior)
- ❌ Pure PHP config (harder to edit, version control noise)
- ❌ Keep 10+ duplicate modals

---

## Next Steps

1. **Approval**: Review and approve this plan
2. **Create**: BaseListCommand, TypeConfigService, namespaces
3. **Migrate**: One command as proof of concept
4. **Test**: Ensure no regressions
5. **Scale**: Batch migrate remaining commands
6. **Document**: Update CLAUDE.md, developer docs

**Ready to proceed with this approach?**
