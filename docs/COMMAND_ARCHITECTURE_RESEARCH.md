# Command Architecture Research & Refactor Plan

**Date**: 2025-10-07  
**Status**: Research Complete - Awaiting Approval  
**Goal**: Eliminate confusion, standardize command structure, enable configuration-driven behavior

---

## Current State Analysis

### Command Inventory (24 PHP Commands)

| Command | Type | Component | Limit50 | User/Agent | Purpose |
|---------|------|-----------|---------|------------|---------|
| **User-Facing Commands** |
| SearchCommand | fragment | FragmentListModal | NO | User | Search all fragments |
| InboxCommand | fragment | FragmentListModal | YES | User | Inbox items needing review |
| RecallCommand | fragment | FragmentListModal | YES | User | Recall/memory search |
| FragCommand | fragment | FragmentListModal | NO | User | Fragment lookup |
| TodoCommand | todo | TodoManagementModal | YES | User | Todo list management |
| SessionListCommand | session | SessionListModal | YES | User | Chat sessions |
| BookmarkListCommand | bookmark | BookmarkListModal | YES | User | Saved bookmarks |
| VaultListCommand | vault | VaultListModal | YES | User | Vault list |
| ProjectListCommand | project | ProjectListModal | YES | User | Project list |
| ChannelsCommand | channel | ChannelListModal | NO | User | Channel list |
| HelpCommand | help | HelpModal | NO | User | Help/documentation |
| **Agent/Orchestration Commands** |
| AgentListCommand | agent | AgentListModal | YES | Agent | Agent registry |
| BacklogListCommand | backlog | BacklogListModal | YES | Agent | Backlog items |
| TaskListCommand | task | TaskListModal | YES | Agent | Sprint tasks |
| SprintListCommand | sprint | SprintListModal | NO | Agent | Sprint list |
| SprintDetailCommand | error | SprintDetailModal | NO | Agent | Sprint details |
| TaskDetailCommand | error | TaskDetailModal | NO | Agent | Task details |
| **Utility Commands** |
| ClearCommand | clear | NONE | NO | User | Clear chat |
| ComposeCommand | message | NONE | NO | User | Start composing |
| ContextCommand | context | NONE | NO | User | Set context |
| JoinCommand | message | NONE | NO | User | Join channel |
| NameCommand | message | NONE | NO | User | Set name |
| RoutingCommand | routing | RoutingInfoModal | NO | User | Routing info |

### Key Findings

**1. Fragment-Based Commands (Core Pattern)**
- SearchCommand, InboxCommand, RecallCommand all return `type: 'fragment'`
- All use `FragmentListModal` component
- All query Fragment model
- **All fragments share same base structure regardless of type**

**2. Type-Specific Modals**
- TodoManagementModal has checkbox behavior (unique)
- Most other modals are just styled lists/tables
- Same columns: ID, Title, Status, Date, Actions

**3. Confusion Sources**
- **Type vs Component mismatch**: Commands define both `type` and `component`, but relationship unclear
- **Pagination inconsistency**: Some have limit(50), some don't, no standard pattern
- **Modal duplication**: 10+ modals doing nearly identical things (list + detail)
- **Orchestration vs User**: No clear separation in codebase structure

**4. Type System Already Exists!**
- `fragments/types/*/type.yaml` defines UI, icons, colors, capabilities
- `frag:type:make` scaffold command exists
- Schema validation in place
- **BUT**: Not connected to command/modal rendering!

---

## Root Problem

**Commands hard-code components instead of using type-driven configuration.**

Current (Broken):
```php
// BookmarkListCommand.php
return [
    'type' => 'bookmark',           // Type metadata
    'component' => 'BookmarkListModal',  // Hard-coded component
    'data' => $bookmarks
];
```

Should Be (Configuration-Driven):
```php
// BookmarkListCommand.php
return [
    'type' => 'bookmark',  // Type drives everything
    'data' => $bookmarks,
    'view_mode' => 'list', // Optional override
    'pagination' => [...]   // Standard pagination
];
```

Then `CommandResultModal` looks up type configuration:
```typescript
// fragments/types/bookmark/type.yaml
ui:
  list_view:
    layout: "table"      // table, grid, bento, card
    template: "default"  // row template
    columns: ["title", "created_at", "category"]
    actions: ["view", "edit", "delete"]
```

---

## Proposed Refactor Plan

### Phase 1: Type System Enhancement

**Goal**: Make type configuration drive all UI rendering

**1. Extend Type Schema** (`fragments/types/*/type.yaml`)

```yaml
name: "Bookmark"
version: "2.0.0"

# UI Configuration (Extended)
ui:
  icon: "bookmark"
  color: "#F59E0B"
  display_name: "Bookmark"
  plural_name: "Bookmarks"
  
  # List View Configuration
  list_view:
    layout: "table"  # table | grid | bento | card | list
    template: "default"  # Component template to use
    row_component: "BookmarkRow"  # Optional custom row
    card_component: null  # For grid/bento layouts
    
    # Pagination
    pagination:
      enabled: true
      default_per_page: 50
      options: [25, 50, 100, 200]
      
    # Columns (for table layout)
    columns:
      - key: "title"
        label: "Title"
        sortable: true
        width: "flex-1"
      - key: "created_at"
        label: "Created"
        sortable: true
        width: "w-32"
        render: "date_human"
      - key: "category"
        label: "Category"
        sortable: true
        width: "w-24"
        render: "badge"
    
    # Search & Filters
    search:
      enabled: true
      fields: ["title", "message", "url"]
      placeholder: "Search bookmarks..."
    
    filters:
      - key: "category"
        label: "Category"
        type: "select"
      - key: "created_at"
        label: "Date"
        type: "date_range"
    
    # Sorting
    sort:
      default: "created_at"
      direction: "desc"
      options:
        - { field: "created_at", label: "Date" }
        - { field: "title", label: "Title" }
        - { field: "last_viewed_at", label: "Last Viewed" }
    
    # Actions
    actions:
      row_click: "detail"  # detail | edit | none
      buttons:
        - key: "view"
          label: "View"
          icon: "eye"
        - key: "edit"
          label: "Edit"
          icon: "pencil"
  
  # Detail View Configuration
  detail_view:
    component: "FragmentDetail"  # Generic or custom
    template: "default"
    
# Type Behaviors (Special Handling)
behaviors:
  - "linkable"        # Has URL field
  - "taggable"        # Has tags
  - "categorizable"   # Has category

# Fragment State Schema
state_schema:
  last_viewed_at: "datetime|nullable"
  view_count: "integer|default:0"
```

**2. Todo Type (Checkbox Example)**

```yaml
name: "Todo"

ui:
  list_view:
    layout: "table"
    template: "checkbox_list"  # Special template!
    
    columns:
      - key: "checkbox"
        label: ""
        width: "w-12"
        render: "checkbox_interactive"  # Custom renderer
      - key: "title"
        label: "Task"
        sortable: true
      - key: "status"
        label: "Status"
        render: "status_badge"
      - key: "due_at"
        label: "Due"
        render: "date_human"
    
behaviors:
  - "completable"  # Has checkbox toggle
  - "due_date_tracking"
  - "priority_sorting"
```

---

### Phase 2: Unified Modal System

**Goal**: Replace 10+ modals with 1 configurable modal

**1. Create `UnifiedListModal` Component**

```typescript
interface UnifiedListModalProps {
  type: string;              // Fragment type (drives config lookup)
  data: Fragment[];          // Data to display
  pagination?: PaginationMeta;
  viewMode?: 'table' | 'grid' | 'bento' | 'card' | 'list';
  onAction?: (action: string, item: Fragment) => void;
  isOpen: boolean;
  onClose: () => void;
}

export function UnifiedListModal({ type, data, ... }: UnifiedListModalProps) {
  // Load type configuration
  const typeConfig = useTypeConfig(type);  // from fragments/types/{type}/type.yaml
  
  // Determine layout
  const layout = viewMode ?? typeConfig.ui.list_view.layout;
  
  // Render appropriate view
  switch (layout) {
    case 'table':
      return <TableView config={typeConfig} data={data} .../>;
    case 'grid':
      return <GridView config={typeConfig} data={data} .../>;
    case 'bento':
      return <BentoView config={typeConfig} data={data} .../>;
    case 'card':
      return <CardView config={typeConfig} data={data} .../>;
    case 'list':
      return <ListView config={typeConfig} data={data} .../>;
  }
}
```

**2. Layout Components**

Each layout reads from type config:
- **TableView**: Uses `config.ui.list_view.columns`
- **GridView**: Uses `config.ui.list_view.card_component`
- **BentoView**: Masonry grid of cards
- **CardView**: Card-based list
- **ListView**: Simple list (like iOS Settings)

**3. Special Behaviors**

```typescript
// In TableView.tsx
if (typeConfig.behaviors.includes('completable')) {
  // Add checkbox column with toggle handler
  columns.unshift({
    key: 'checkbox',
    render: (item) => <Checkbox checked={item.state?.completed} onChange={...} />
  });
}

if (typeConfig.behaviors.includes('linkable')) {
  // Add external link icon
  actions.push({
    key: 'open_link',
    icon: 'external-link',
    handler: (item) => window.open(item.metadata?.url)
  });
}
```

---

### Phase 3: Simplified Command Structure

**Goal**: Make ALL list commands follow same pattern

**1. Base List Command Pattern**

```php
<?php

namespace App\Commands;

use App\Commands\Concerns\ProvidesListData;

class BookmarkListCommand extends BaseListCommand
{
    use ProvidesListData;
    
    protected string $type = 'bookmark';
    protected string $modelClass = \App\Models\Fragment::class;
    
    protected function buildQuery($builder)
    {
        return $builder->where('type', 'bookmark');
    }
}
```

**That's it!** Everything else is handled by `BaseListCommand`:
- Pagination
- Sorting
- Filtering
- Response formatting

**2. BaseListCommand Implementation**

```php
<?php

namespace App\Commands;

abstract class BaseListCommand extends BaseCommand
{
    protected string $type;  // Required: fragment type
    protected string $modelClass = \App\Models\Fragment::class;
    protected array $with = [];  // Eager load relations
    protected ?string $defaultSort = 'created_at';
    protected string $defaultSortDir = 'desc';
    
    public function handle(): array
    {
        // Load type configuration
        $typeConfig = app(TypeConfigService::class)->get($this->type);
        
        // Build base query
        $query = $this->modelClass::query();
        
        // Apply eager loading
        if (!empty($this->with)) {
            $query->with($this->with);
        }
        
        // Let subclass modify query
        $query = $this->buildQuery($query);
        
        // Apply pagination (from type config or defaults)
        $pagination = $this->applyPagination($query, $typeConfig);
        
        // Apply sorting
        $this->applySorting($query, $typeConfig);
        
        // Get results
        $items = $query->get();
        
        // Transform data
        $data = $this->transformItems($items, $typeConfig);
        
        // Return standardized response
        return [
            'type' => $this->type,
            'data' => $data,
            'pagination' => $pagination,
            'config' => $typeConfig->ui->list_view,  // Pass config to frontend
        ];
    }
    
    // Subclasses can override these
    protected function buildQuery($builder) { return $builder; }
    protected function transformItems($items, $config) { return $items->toArray(); }
}
```

**3. Example Conversions**

```php
// SessionListCommand - BEFORE (50 lines)
class SessionListCommand extends BaseCommand {
    public function handle(): array {
        $sessions = $this->getChatSessions();
        return [
            'type' => 'session',
            'component' => 'SessionListModal',
            'data' => $sessions
        ];
    }
    
    private function getChatSessions(): array {
        // 40 lines of query logic...
    }
}

// SessionListCommand - AFTER (10 lines)
class SessionListCommand extends BaseListCommand {
    protected string $type = 'session';
    protected string $modelClass = \App\Models\ChatSession::class;
    
    protected function buildQuery($builder) {
        return $builder->where('is_active', true);
    }
}
```

---

### Phase 4: Command Scaffold Update

**Update `frag:command:make` to use new pattern**

```bash
php artisan frag:command:make bookmark-list --type=bookmark --model=Fragment

# Generates:
# - app/Commands/BookmarkListCommand.php (using BaseListCommand)
# - Tests
# - Registers in CommandRegistry
```

Template:
```php
<?php

namespace App\Commands;

class {{ CommandName }} extends BaseListCommand
{
    protected string $type = '{{ type }}';
    protected string $modelClass = {{ model }}::class;
    
    protected function buildQuery($builder)
    {
        return $builder->where('type', '{{ type }}');
    }
    
    public static function getName(): string
    {
        return '{{ readable_name }}';
    }
    
    public static function getUsage(): string
    {
        return '/{{ command_slug }}';
    }
}
```

---

### Phase 5: Migration Plan

**Goal**: Migrate existing commands without breaking anything

**Step 1: Create New Infrastructure**
1. ✅ Create `BaseListCommand`
2. ✅ Create `ProvidesListData` trait
3. ✅ Create `TypeConfigService` to load YAML configs
4. ✅ Create `UnifiedListModal` component
5. ✅ Create layout components (TableView, GridView, etc.)
6. ✅ Update type YAML schemas

**Step 2: Migrate One Command (Proof of Concept)**
1. Choose BookmarkListCommand (simple, low risk)
2. Convert to use BaseListCommand
3. Update type.yaml with full config
4. Test thoroughly
5. Document differences

**Step 3: Migrate Remaining Commands (By Category)**
1. **Fragment-based** (4): SearchCommand, InboxCommand, RecallCommand, FragCommand
2. **User lists** (5): SessionListCommand, VaultListCommand, ProjectListCommand, ChannelsCommand, TodoCommand
3. **Orchestration** (4): AgentListCommand, BacklogListCommand, TaskListCommand, SprintListCommand
4. **Keep custom** (3): HelpCommand, RoutingCommand, Detail commands (have custom logic)

**Step 4: Deprecate Old Modals**
1. Replace modal imports with UnifiedListModal
2. Remove old modal files (after verification)
3. Clean up CommandResultModal routing

**Step 5: Update Docs & Training**
1. Update developer docs
2. Create command creation guide
3. Add type configuration examples
4. Update CLAUDE.md with new patterns

---

## Configuration Examples

### Example 1: Simple List (Sessions)

```yaml
# fragments/types/session/type.yaml
name: "Session"

ui:
  list_view:
    layout: "table"
    pagination:
      enabled: true
      default_per_page: 50
    columns:
      - key: "title"
        label: "Session"
        width: "flex-1"
      - key: "message_count"
        label: "Messages"
        width: "w-20"
      - key: "last_activity_at"
        label: "Last Activity"
        width: "w-32"
        render: "date_human"
    sort:
      default: "last_activity_at"
      direction: "desc"
```

### Example 2: Grid View (Projects)

```yaml
# fragments/types/project/type.yaml
name: "Project"

ui:
  list_view:
    layout: "grid"
    card_component: "ProjectCard"
    pagination:
      enabled: true
      default_per_page: 24
    search:
      enabled: true
      fields: ["name", "description"]
```

### Example 3: Bento Layout (Bookmarks)

```yaml
# fragments/types/bookmark/type.yaml
name: "Bookmark"

ui:
  list_view:
    layout: "bento"  # Masonry grid
    card_component: "BookmarkCard"
    pagination:
      enabled: true
      default_per_page: 50
```

### Example 4: Orchestration (Tasks)

```yaml
# fragments/types/task/type.yaml
name: "Task"

ui:
  list_view:
    layout: "table"
    template: "orchestration"  # Agent-friendly view
    columns:
      - key: "task_code"
        label: "Code"
        width: "w-32"
      - key: "task_name"
        label: "Task"
      - key: "status"
        label: "Status"
        render: "status_badge"
      - key: "agent"
        label: "Assigned"
        render: "agent_badge"
    actions:
      - key: "assign"
        label: "Assign"
        icon: "user-plus"
```

---

## Benefits

### For Developers
✅ Write 10 lines of code instead of 50  
✅ No more modal duplication  
✅ Configuration-driven (change YAML, not code)  
✅ Consistent patterns everywhere  
✅ Easy to add new types  

### For Users
✅ Consistent UI across all list views  
✅ All lists have same features (search, filter, sort, pagination)  
✅ Type-specific behaviors (checkboxes for todos, etc.)  
✅ Better performance (unified pagination)  

### For Agents
✅ Clear separation: User vs Orchestration commands  
✅ No confusion about what returns what  
✅ Type system documents behavior  
✅ Scaffolding generates correct code  

---

## Breaking Change Analysis

**Will Break:**
- Custom modals that don't follow standard pattern
- Commands that return non-standard response shapes
- Frontend code that relies on specific modal names

**Won't Break:**
- API contracts (response format stays same)
- Existing data
- YAML commands (separate system)
- Type definitions

**Migration Strategy:**
- Parallel implementation (keep old system while building new)
- Feature flag: `config('fragments.use_unified_modals', false)`
- Gradual migration, command by command
- Comprehensive testing before switching

---

## Recommended Next Steps

1. **Approval**: Review and approve this plan
2. **Prototype**: Build BaseListCommand + UnifiedListModal
3. **POC**: Convert one command (BookmarkListCommand) as proof
4. **Test**: Ensure no regressions
5. **Iterate**: Refine based on findings
6. **Scale**: Migrate remaining commands systematically
7. **Clean**: Remove old code, update docs

**Estimated Time**: 2-3 days for full migration

---

## Open Questions

1. Should we keep any specialized modals? (TodoManagementModal has unique UX)
2. How to handle hybrid commands (e.g., AgentListCommand returns 2 datasets)?
3. Backend pagination: Add to BaseListCommand or keep in commands?
4. Type inheritance: Should some types extend others?
5. Mobile views: Different layouts for responsive?

---

## Conclusion

**Current Problem**: Every agent breaks commands because structure is inconsistent and fragile.

**Root Cause**: Hard-coded components, no configuration, duplicated logic across 10+ modals.

**Solution**: Type-driven configuration, unified modal system, simplified command base class.

**Result**: Agents can't break what's driven by configuration. Commands become 10-line wrappers. UI stays consistent. New types are easy to add.

**This eliminates the confusion permanently.**
