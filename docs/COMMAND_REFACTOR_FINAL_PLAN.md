# Command Architecture Refactor - Final Implementation Plan

**Date**: 2025-10-07  
**Status**: Ready for Implementation  
**Approved By**: User

---

## Executive Summary

**Problem**: Commands constantly break because structure is inconsistent. AI agents get confused about User vs Agent commands.

**Solution**: 
1. **DB-first config** with optional PHP classes for complexity
2. **Namespace separation** (`User/` vs `Agent/`)
3. **Type management UI** for enable/disable, future editing
4. **BaseListCommand** to eliminate boilerplate

**Result**: Configuration-driven, can't accidentally break, clear separation.

---

## Part 1: Type System - DB First Approach

### Why DB-First (Not YAML)

**User's Point**: "I'm not sure of the benefit of yaml if we cache in the db and have a ui for managing/editing"

✅ **Agreed!** Let's simplify:
- **DB**: Source of truth (editable via UI)
- **PHP Classes**: Optional, for complex behavior only
- **YAML**: Only for initial seeding (optional)

### Updated Type Registry Schema

**Migration**: Add columns to existing `fragment_type_registry`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fragment_type_registry', function (Blueprint $table) {
            // Management flags
            $table->boolean('is_enabled')->default(true)->after('capabilities');
            $table->boolean('is_system')->default(false)->after('is_enabled');
            $table->boolean('hide_from_admin')->default(false)->after('is_system');
            
            // UI Configuration (JSON)
            $table->json('ui_config')->nullable()->after('hide_from_admin');
            $table->json('list_columns')->nullable()->after('ui_config');
            $table->json('filters')->nullable()->after('list_columns');
            $table->json('actions')->nullable()->after('filters');
            $table->json('default_sort')->nullable()->after('actions');
            $table->integer('pagination_default')->default(50)->after('default_sort');
            
            // Type behavior
            $table->string('config_class')->nullable()->after('pagination_default');
            $table->json('behaviors')->nullable()->after('config_class');
            
            // Display metadata
            $table->string('display_name')->nullable()->after('slug');
            $table->string('plural_name')->nullable()->after('display_name');
            $table->string('description')->nullable()->after('plural_name');
            $table->string('icon')->default('file-text')->after('description');
            $table->string('color')->default('#6B7280')->after('icon');
            
            // Indexes
            $table->index('is_enabled');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('fragment_type_registry', function (Blueprint $table) {
            $table->dropColumn([
                'is_enabled', 'is_system', 'hide_from_admin',
                'ui_config', 'list_columns', 'filters', 'actions', 
                'default_sort', 'pagination_default', 'config_class', 'behaviors',
                'display_name', 'plural_name', 'description', 'icon', 'color'
            ]);
        });
    }
};
```

### Updated FragmentTypeRegistry Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fragment Type Registry
 * 
 * Central registry for all fragment types. Stores configuration,
 * UI settings, and management flags. Source of truth for type system.
 */
class FragmentTypeRegistry extends Model
{
    protected $table = 'fragment_type_registry';

    protected $fillable = [
        'slug',
        'display_name',
        'plural_name',
        'description',
        'icon',
        'color',
        'version',
        'source_path',
        'schema_hash',
        'hot_fields',
        'capabilities',
        'is_enabled',
        'is_system',
        'hide_from_admin',
        'ui_config',
        'list_columns',
        'filters',
        'actions',
        'default_sort',
        'pagination_default',
        'config_class',
        'behaviors',
    ];

    protected $casts = [
        'hot_fields' => 'array',
        'capabilities' => 'array',
        'is_enabled' => 'boolean',
        'is_system' => 'boolean',
        'hide_from_admin' => 'boolean',
        'ui_config' => 'array',
        'list_columns' => 'array',
        'filters' => 'array',
        'actions' => 'array',
        'default_sort' => 'array',
        'behaviors' => 'array',
    ];

    /**
     * Scope: Only enabled types
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: User-manageable types (exclude system types hidden from admin)
     */
    public function scopeUserManageable($query)
    {
        return $query->where('hide_from_admin', false);
    }

    /**
     * Check if type can be disabled
     */
    public function canBeDisabled(): bool
    {
        return !$this->is_system;
    }

    /**
     * Check if type can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system;
    }

    /**
     * Get PHP config class instance (if exists)
     */
    public function getConfigInstance(): ?object
    {
        if (!$this->config_class || !class_exists($this->config_class)) {
            return null;
        }

        return app($this->config_class);
    }
}
```

### System Types (Seeded, Cannot Disable/Delete)

```php
<?php

namespace Database\Seeders;

use App\Models\FragmentTypeRegistry;
use Illuminate\Database\Seeder;

class SystemTypesSeeder extends Seeder
{
    public function run(): void
    {
        $systemTypes = [
            [
                'slug' => 'user',
                'display_name' => 'User Message',
                'plural_name' => 'User Messages',
                'description' => 'Messages from users in chat',
                'icon' => 'user',
                'color' => '#3B82F6',
                'is_system' => true,
                'is_enabled' => true,
                'hide_from_admin' => true,  // Hide internal types
            ],
            [
                'slug' => 'assistant',
                'display_name' => 'Assistant Response',
                'plural_name' => 'Assistant Responses',
                'description' => 'AI assistant responses',
                'icon' => 'bot',
                'color' => '#8B5CF6',
                'is_system' => true,
                'is_enabled' => true,
                'hide_from_admin' => true,
            ],
            [
                'slug' => 'system',
                'display_name' => 'System Message',
                'plural_name' => 'System Messages',
                'description' => 'System-generated messages',
                'icon' => 'cpu',
                'color' => '#6B7280',
                'is_system' => true,
                'is_enabled' => true,
                'hide_from_admin' => true,
            ],
            [
                'slug' => 'bookmark',
                'display_name' => 'Bookmark',
                'plural_name' => 'Bookmarks',
                'description' => 'Saved bookmarks and links',
                'icon' => 'bookmark',
                'color' => '#F59E0B',
                'is_system' => false,
                'is_enabled' => true,
                'hide_from_admin' => false,  // User can manage
                'list_columns' => [
                    ['key' => 'title', 'label' => 'Title', 'sortable' => true],
                    ['key' => 'category', 'label' => 'Category', 'sortable' => true],
                    ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
                ],
                'default_sort' => ['created_at', 'desc'],
                'pagination_default' => 50,
            ],
            [
                'slug' => 'todo',
                'display_name' => 'Todo',
                'plural_name' => 'Todos',
                'description' => 'Task and todo items',
                'icon' => 'check-square',
                'color' => '#10B981',
                'is_system' => false,
                'is_enabled' => true,
                'hide_from_admin' => false,
                'behaviors' => ['completable'],  // Has checkbox
                'config_class' => 'App\\Types\\Todo\\TodoTypeConfig',  // Complex behavior
            ],
            [
                'slug' => 'note',
                'display_name' => 'Note',
                'plural_name' => 'Notes',
                'description' => 'General notes and memos',
                'icon' => 'sticky-note',
                'color' => '#EAB308',
                'is_system' => false,
                'is_enabled' => true,
                'hide_from_admin' => false,
            ],
        ];

        foreach ($systemTypes as $type) {
            FragmentTypeRegistry::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
```

---

## Part 2: Type Management UI (MVP)

### Phase 1: List Types (Working `/types` command)

**Update TypeController** to return admin-visible types:

```php
<?php

namespace App\Http\Controllers;

use App\Models\FragmentTypeRegistry;
use Illuminate\Http\JsonResponse;

class TypeController extends Controller
{
    /**
     * Get all types (admin view)
     */
    public function index(): JsonResponse
    {
        $types = FragmentTypeRegistry::userManageable()
            ->orderBy('display_name')
            ->get()
            ->map(fn($type) => [
                'slug' => $type->slug,
                'display_name' => $type->display_name,
                'plural_name' => $type->plural_name,
                'description' => $type->description,
                'icon' => $type->icon,
                'color' => $type->color,
                'is_enabled' => $type->is_enabled,
                'is_system' => $type->is_system,
                'can_disable' => $type->canBeDisabled(),
                'can_delete' => $type->canBeDeleted(),
                'fragment_count' => \App\Models\Fragment::where('type', $type->slug)->count(),
                'updated_at' => $type->updated_at,
            ]);

        return response()->json(['data' => $types]);
    }

    /**
     * Toggle type enabled status
     */
    public function toggleEnabled(string $slug): JsonResponse
    {
        $type = FragmentTypeRegistry::findBySlug($slug);

        if (!$type) {
            return response()->json(['error' => 'Type not found'], 404);
        }

        if (!$type->canBeDisabled()) {
            return response()->json([
                'error' => 'Cannot disable system type',
                'message' => 'System types are required and cannot be disabled'
            ], 403);
        }

        $type->is_enabled = !$type->is_enabled;
        $type->save();

        return response()->json([
            'message' => 'Type ' . ($type->is_enabled ? 'enabled' : 'disabled'),
            'type' => [
                'slug' => $type->slug,
                'is_enabled' => $type->is_enabled,
            ]
        ]);
    }
}
```

**Add Route**:

```php
// routes/api.php
Route::post('types/{slug}/toggle', [TypeController::class, 'toggleEnabled']);
```

### Phase 2: Type Management Modal (Frontend)

**Create** `resources/js/components/types/TypeManagementModal.tsx`:

```typescript
import { DataManagementModal } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Shield, Package } from 'lucide-react'

interface FragmentType {
  slug: string
  display_name: string
  plural_name: string
  description: string
  icon: string
  color: string
  is_enabled: boolean
  is_system: boolean
  can_disable: boolean
  can_delete: boolean
  fragment_count: number
  updated_at: string
}

interface TypeManagementModalProps {
  isOpen: boolean
  onClose: () => void
  types: FragmentType[]
  onToggleEnabled: (slug: string) => void
}

export function TypeManagementModal({ 
  isOpen, 
  onClose, 
  types,
  onToggleEnabled 
}: TypeManagementModalProps) {
  
  const columns = [
    {
      key: 'display_name',
      label: 'Type',
      render: (type: FragmentType) => (
        <div className="flex items-center gap-2">
          <div 
            className="w-8 h-8 rounded flex items-center justify-center"
            style={{ backgroundColor: type.color + '20', color: type.color }}
          >
            {type.is_system && <Shield className="h-4 w-4" />}
            {!type.is_system && <Package className="h-4 w-4" />}
          </div>
          <div>
            <div className="font-medium">{type.display_name}</div>
            <div className="text-xs text-muted-foreground">{type.description}</div>
          </div>
        </div>
      )
    },
    {
      key: 'fragment_count',
      label: 'Count',
      width: 'w-20',
      render: (type: FragmentType) => (
        <span className="text-sm">{type.fragment_count.toLocaleString()}</span>
      )
    },
    {
      key: 'status',
      label: 'Status',
      width: 'w-32',
      render: (type: FragmentType) => (
        <div className="flex items-center gap-2">
          {type.is_system && (
            <Badge variant="secondary" className="text-xs">
              System
            </Badge>
          )}
          <Badge 
            variant={type.is_enabled ? 'default' : 'outline'}
            className="text-xs"
          >
            {type.is_enabled ? 'Enabled' : 'Disabled'}
          </Badge>
        </div>
      )
    },
    {
      key: 'actions',
      label: 'Enable',
      width: 'w-20',
      render: (type: FragmentType) => (
        <Switch
          checked={type.is_enabled}
          disabled={!type.can_disable}
          onCheckedChange={() => onToggleEnabled(type.slug)}
        />
      )
    }
  ];

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Fragment Types"
      columns={columns}
      data={types}
      searchPlaceholder="Search types..."
      searchFields={['display_name', 'description', 'slug']}
      emptyStateMessage="No types found"
    />
  );
}
```

---

## Part 3: Command Namespace Structure

```
app/
  Commands/
    User/                           # User-facing commands
      Fragments/
        SearchCommand.php           # /search
        InboxCommand.php            # /inbox
        RecallCommand.php           # /recall
      Library/
        BookmarkListCommand.php     # /bookmarks
        VaultListCommand.php        # /vaults
        ProjectListCommand.php      # /projects
      Session/
        SessionListCommand.php      # /sessions
        ChannelsCommand.php         # /channels
      Productivity/
        TodoCommand.php             # /todos
      System/
        HelpCommand.php             # /help
        ContextCommand.php          # /context
        TypesCommand.php            # /types (Type management)
        
    Agent/                          # Agent-facing commands
      Orchestration/
        TaskListCommand.php         # /tasks
        BacklogListCommand.php      # /backlog
        SprintListCommand.php       # /sprints
        SprintDetailCommand.php     # /sprint-detail
        TaskDetailCommand.php       # /task-detail
      Registry/
        AgentListCommand.php        # /agents
        
    Shared/                         # Base classes
      BaseCommand.php
      BaseListCommand.php
      BaseDetailCommand.php
      
    Concerns/                       # Traits
      ProvidesListData.php
      ProvidesPagination.php
```

---

## Part 4: Implementation Tasks

### Sprint: COMMAND-REFACTOR

**Goal**: Implement type management + command refactor

**Estimated**: 2-3 days

#### Phase 1: Database & Models (Day 1 Morning)
- [x] Create migration to add columns to `fragment_type_registry`
- [x] Update `FragmentTypeRegistry` model with new fields/methods
- [x] Create `SystemTypesSeeder`
- [ ] Run migration + seeder
- [ ] Test: Query enabled types, toggle enabled status

#### Phase 2: Type Management UI (Day 1 Afternoon)
- [ ] Update `TypeController::index()` for admin view
- [ ] Add `TypeController::toggleEnabled()` method
- [ ] Add route: `POST /api/types/{slug}/toggle`
- [ ] Create `TypeManagementModal.tsx`
- [ ] Wire up `/types` command to open modal
- [ ] Test: View types, toggle enabled/disabled

#### Phase 3: Base Command Classes (Day 2 Morning)
- [ ] Create `app/Commands/Shared/BaseListCommand.php`
- [ ] Create `TypeConfigService` (loads from DB → PHP class fallback)
- [ ] Create example PHP config class: `app/Types/Todo/TodoTypeConfig.php`
- [ ] Test: Load config for 'bookmark' type

#### Phase 4: Migrate One Command (Day 2 Afternoon - POC)
- [ ] Create namespace: `app/Commands/User/Library/`
- [ ] Move `BookmarkListCommand` → new namespace
- [ ] Convert to extend `BaseListCommand`
- [ ] Update `CommandRegistry` (if needed)
- [ ] Test: `/bookmarks` command still works
- [ ] Verify pagination, sorting, filtering

#### Phase 5: Create UnifiedListModal (Day 3 Morning)
- [ ] Create `UnifiedListModal.tsx`
- [ ] Create `TableView.tsx` (reads config.list_columns)
- [ ] Update `CommandResultModal` routing
- [ ] Test: BookmarkListCommand uses UnifiedListModal

#### Phase 6: Batch Migration (Day 3 Afternoon)
- [ ] Migrate remaining User commands (SearchCommand, InboxCommand, etc.)
- [ ] Migrate Agent commands (TaskListCommand, BacklogListCommand, etc.)
- [ ] Update all command imports/references
- [ ] Test all commands still work

#### Phase 7: Documentation & Cleanup
- [ ] Update docs/CLAUDE.md with new patterns
- [ ] Update scaffold command to use new structure
- [ ] Remove old modal files
- [ ] Update developer guide

---

## Type Flags Explained

### `is_system` (Boolean)

**Purpose**: Mark types that are core to the system

**Examples**:
- `user`, `assistant`, `system` (chat fragments)
- Internal types that shouldn't be visible to users

**Behavior**:
- ✅ Cannot be disabled
- ✅ Cannot be deleted
- ✅ Can be overridden by custom commands (future)

### `is_enabled` (Boolean)

**Purpose**: Allow users to enable/disable types

**Examples**:
- User disables `bookmark` type → `/bookmarks` command returns empty
- User disables `todo` type → Todos hidden from UI

**Behavior**:
- ✅ User can toggle via UI (if `is_system = false`)
- ✅ Disabled types don't show in searches/lists
- ✅ Existing fragments remain in DB (just hidden)

### `hide_from_admin` (Boolean)

**Purpose**: Hide types from admin type management UI

**Examples**:
- Internal system types (`user`, `assistant`, `system`)
- Auto-generated types
- Technical types users shouldn't manage

**Behavior**:
- ✅ Excluded from type list in admin UI
- ✅ Still functions normally in code
- ✅ Can still be queried/used by commands

---

## Future: User-Created Types

**Phase 2 (Future Sprint)**:

1. "Create Type" button in Type Management UI
2. Form: Name, Description, Icon, Color
3. Saves to DB (`is_system = false`, `is_enabled = true`)
4. Optional: Generate YAML file for export
5. User can override command slugs (e.g., custom `/bookmarks` command)

**DB + UI = Source of Truth**

YAML becomes optional export format for sharing types between instances.

---

## Benefits of This Approach

✅ **DB-First**: Fast, editable, cached  
✅ **UI Management**: Users control types  
✅ **System Protection**: Core types cannot be broken  
✅ **Clean Namespaces**: Clear User vs Agent separation  
✅ **Minimal Boilerplate**: Commands = ~10 lines  
✅ **Configuration-Driven**: Can't accidentally break it  
✅ **Extensible**: PHP classes for complex cases  

---

## Decision: No YAML (Except Optional Export)

**Agreed**: DB + PHP classes are sufficient.

**YAML Optional Uses**:
1. Initial seeding (convenience)
2. Export/import types between instances
3. Version control of type definitions (optional)

**Primary Flow**: DB ← UI ← User

**Not**: YAML → DB ← UI (too complex)

---

## Next Steps

1. ✅ Run migration to add columns
2. ✅ Seed system types
3. ✅ Build type management UI (enable/disable)
4. ✅ Create BaseListCommand
5. ✅ Migrate one command as POC
6. ✅ Test thoroughly
7. ✅ Batch migrate remaining commands

**Ready to proceed?**
