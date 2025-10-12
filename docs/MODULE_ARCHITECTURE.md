# Module Architecture - Configurable Business Modules

**Version**: 1.0  
**Status**: Planning / Architecture Design  
**Created**: October 12, 2025

---

## Vision

Create a fully configurable module system where business logic modules (Project Manager, CRM, Task Manager, etc.) can be defined through declarative configuration objects or database records, eliminating the need to manually wire components, routes, and commands.

**Inspiration**: Filament PHP's fluent builder pattern + Laravel's configuration philosophy

---

## Current State Analysis

### What We've Built: Project Manager Module

The Sprint/Task management system provides a complete reference implementation:

**Components Created:**
- `SprintListModal` - List view with filters, search, actions
- `SprintDetailModal` - Detail view with nested task list
- `SprintFormModal` - Create/edit form
- `TaskDetailModal` - Detail view with inline editing
- `TaskListModal` - Standalone task list

**Backend:**
- Slash commands: `/sprints`, `/sprint-detail`, `/sprint-create`, `/sprint-edit`, `/tasks`, `/task-detail`
- Command handlers in `app/Commands/Orchestration/`
- API endpoints in `app/Http/Controllers/Orchestration/`
- WorkItem model with polymorphic types

**Configuration:**
- Database: `commands` table with UI config JSON
- Database: `types_registry` for model-backed types
- Navigation stack pattern for modal flow
- Component resolution via `CommandResultModal`

### Hardcoded Elements to Abstract

1. **Component Imports** - Manual import in `COMPONENT_MAP`
2. **Route Definitions** - Manual PHP route registration
3. **Command Handlers** - Custom PHP classes per command
4. **Column Definitions** - Hardcoded in CommandResultModal
5. **Action Items** - Defined in component props
6. **Filters** - Hardcoded filter arrays
7. **Relationships** - Manual handler wiring (sprint → tasks)
8. **Validation Rules** - Scattered across controllers
9. **Permissions** - Not yet implemented systematically
10. **Audit Logging** - Manual TaskActivity creation

---

## Proposed Module System

### Module Definition API

```php
use App\Support\Module;

Module::make('project-manager')
    // Basic Info
    ->title('Project Manager')
    ->description('Manage sprints, tasks, and project workflows')
    ->icon('folder-kanban')
    ->version('1.0.0')
    
    // Data Model
    ->model(WorkItem::class)
    ->table('work_items')
    ->keyColumn('id')
    ->typeColumn('type') // polymorphic discriminator
    
    // Types/Entities within this module
    ->types([
        Type::make('sprint')
            ->label('Sprint')
            ->pluralLabel('Sprints')
            ->icon('calendar')
            ->schema([
                Field::text('code')->required()->unique(),
                Field::text('title')->required(),
                Field::select('status')->options(['planning', 'active', 'completed']),
                Field::textarea('description'),
                Field::json('metadata'),
            ])
            ->children('tasks') // Has many tasks
            ->container(DataManagementModal::class)
            ->detailComponent(SprintDetailModal::class)
            ->formComponent(SprintFormModal::class),
            
        Type::make('task')
            ->label('Task')
            ->pluralLabel('Tasks')
            ->icon('check-square')
            ->schema([
                Field::text('task_code')->required()->unique(),
                Field::text('task_name'),
                Field::textarea('description'),
                Field::select('status')->options(['backlog', 'todo', 'in_progress', 'review', 'done']),
                Field::select('priority')->options(['low', 'medium', 'high']),
                Field::belongsTo('sprint', 'sprint_code'),
                Field::tags('tags'),
            ])
            ->parent('sprint') // Belongs to sprint
            ->container(DataManagementModal::class)
            ->detailComponent(TaskDetailModal::class)
            ->editableFields(['task_name', 'description', 'status', 'priority', 'tags'])
            ->contentFields(['agent_content', 'plan_content', 'context_content', 'todo_content', 'summary_content']),
    ])
    
    // Commands
    ->commands([
        Command::make('/sprints')
            ->handler(SprintListCommand::class)
            ->description('List all sprints')
            ->permissions(['view-sprints'])
            ->navigation([
                'data_prop' => 'sprints',
                'item_key' => 'code',
                'detail_command' => '/sprint-detail',
            ]),
            
        Command::make('/sprint-detail {code}')
            ->handler(SprintDetailCommand::class)
            ->description('View sprint details')
            ->permissions(['view-sprints'])
            ->navigation([
                'parent_command' => '/sprints',
                'children' => [
                    ['type' => 'task', 'command' => '/task-detail', 'item_key' => 'task_code']
                ]
            ]),
    ])
    
    // API Endpoints (auto-generated)
    ->apiEndpoints([
        'list' => true,        // GET /api/sprints
        'show' => true,        // GET /api/sprints/{id}
        'create' => true,      // POST /api/sprints
        'update' => true,      // PATCH /api/sprints/{id}
        'delete' => true,      // DELETE /api/sprints/{id}
        'updateField' => true, // PATCH /api/sprints/{id}/field
        'updateTags' => true,  // PATCH /api/sprints/{id}/tags
    ])
    
    // UI Configuration
    ->container(function (Container $container) {
        $container
            ->modal(true)
            ->title(fn($data) => $data['title'] ?? 'Project Manager')
            ->layout('table') // table|grid|kanban|calendar
            ->searchable(['code', 'title', 'description'])
            ->sortable(['code', 'title', 'created_at', 'status'])
            ->filters([
                Filter::select('status')
                    ->options(['planning', 'active', 'completed', 'all'])
                    ->default('active'),
                Filter::select('priority')
                    ->options(['low', 'medium', 'high', 'all'])
                    ->default('all'),
            ])
            ->actions([
                Action::make('view')->label('View Details'),
                Action::make('edit')->label('Edit'),
                Action::make('delete')->label('Delete')->dangerous(),
                Action::make('assign-agent')->label('Assign Agent'),
                Action::make('move')->label('Move to Sprint'),
            ])
            ->bulkActions([
                Action::make('delete')->label('Delete Selected'),
                Action::make('export')->label('Export'),
            ]);
    })
    
    // Permissions
    ->abilities([
        'view-sprints',
        'create-sprints',
        'edit-sprints',
        'delete-sprints',
        'view-tasks',
        'create-tasks',
        'edit-tasks',
        'delete-tasks',
        'assign-agents',
    ])
    
    // Audit Logging
    ->auditable(true)
    ->auditEvents(['created', 'updated', 'deleted', 'field_changed', 'assigned'])
    
    // Relations to Other Modules
    ->dependsOn(['user-management', 'agent-system'])
    ->provides(['tasks', 'sprints'])
    
    // Hooks
    ->beforeCreate(function ($data) {
        $data['created_by'] = auth()->id();
        return $data;
    })
    ->afterCreate(function ($record) {
        event(new SprintCreated($record));
    })
    
    ->register();
```

---

## Module System Architecture

### Core Components

```
app/
  Support/
    Module.php              # Main module builder
    ModuleRegistry.php      # Central module registry
    Type.php                # Entity type builder
    Field.php               # Schema field builder
    Command.php             # Command builder
    Action.php              # UI action builder
    Filter.php              # Filter builder
    Container.php           # UI container config
    
  Modules/                  # Business modules
    ProjectManager/
      ProjectManagerModule.php     # Module definition
      Commands/
        SprintListCommand.php
        SprintDetailCommand.php
      Controllers/
        SprintController.php
      Models/
        Sprint.php (extends WorkItem)
        Task.php (extends WorkItem)
        
    CRM/
      CRMModule.php
      Commands/
        ContactListCommand.php
        DealPipelineCommand.php
      Controllers/
        ContactController.php
        DealController.php
        
    InventoryManager/
      InventoryModule.php
      # ... similar structure
      
  Providers/
    ModuleServiceProvider.php  # Auto-discovers and registers modules
```

### Database Schema

```sql
-- Modules table
CREATE TABLE modules (
    id UUID PRIMARY KEY,
    slug VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    description TEXT,
    version VARCHAR(50),
    enabled BOOLEAN DEFAULT true,
    config JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Module types (entities within modules)
CREATE TABLE module_types (
    id UUID PRIMARY KEY,
    module_id UUID REFERENCES modules(id),
    slug VARCHAR(255),
    label VARCHAR(255),
    plural_label VARCHAR(255),
    schema JSON,
    ui_config JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(module_id, slug)
);

-- Module commands
CREATE TABLE module_commands (
    id UUID PRIMARY KEY,
    module_id UUID REFERENCES modules(id),
    command VARCHAR(255) UNIQUE,
    handler_class VARCHAR(255),
    description TEXT,
    navigation_config JSON,
    permissions JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Module permissions
CREATE TABLE module_permissions (
    id UUID PRIMARY KEY,
    module_id UUID REFERENCES modules(id),
    ability VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(module_id, ability)
);
```

---

## Configuration-Driven Component Resolution

### Current System (Hardcoded)

```typescript
// CommandResultModal.tsx
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  'SprintListModal': SprintListModal,
  'SprintDetailModal': SprintDetailModal,
  'TaskListModal': TaskListModal,
  'TaskDetailModal': TaskDetailModal,
  // ... manually added
}
```

### Proposed System (Dynamic)

```typescript
// ModuleComponentRegistry.ts
class ModuleComponentRegistry {
  private components: Map<string, React.ComponentType<any>> = new Map()
  
  register(name: string, component: React.ComponentType<any>) {
    this.components.set(name, component)
  }
  
  resolve(name: string): React.ComponentType<any> | null {
    return this.components.get(name) ?? null
  }
  
  autoDiscover() {
    // Auto-import from modules directory
    const modules = import.meta.glob('../modules/**/components/*.tsx')
    // ... register discovered components
  }
}

// Usage in CommandResultModal
const component = moduleRegistry.resolve(result.config.ui.modal_container)
```

---

## Generic Components

### Universal List Modal

```typescript
interface UniversalListModalProps<T> {
  config: ModuleTypeConfig
  data: T[]
  columns: ColumnDefinition<T>[]
  filters?: FilterDefinition[]
  actions?: ActionDefinition[]
  onAction: (action: string, item: T) => void
  // ... auto-configured from module definition
}
```

### Universal Detail Modal

```typescript
interface UniversalDetailModalProps<T> {
  config: ModuleTypeConfig
  item: T
  fields: FieldDefinition[]
  editableFields?: string[]
  contentFields?: string[]
  relations?: RelationDefinition[]
  // ... auto-configured from module definition
}
```

### Universal Form Modal

```typescript
interface UniversalFormModalProps<T> {
  config: ModuleTypeConfig
  schema: SchemaDefinition
  initialData?: Partial<T>
  validation: ValidationRules
  onSubmit: (data: T) => Promise<void>
  // ... auto-configured from module definition
}
```

---

## Command Scaffolding CLI

```bash
php artisan module:make ProjectManager --path=app/Modules/ProjectManager

# Creates:
# - app/Modules/ProjectManager/ProjectManagerModule.php
# - app/Modules/ProjectManager/Commands/
# - app/Modules/ProjectManager/Controllers/
# - app/Modules/ProjectManager/Models/
# - database/migrations/..._create_project_manager_tables.php
# - resources/js/modules/project-manager/

php artisan module:type ProjectManager Sprint --with-commands

# Creates:
# - Sprint model
# - SprintController
# - SprintListCommand, SprintDetailCommand, SprintCreateCommand
# - Sprint UI components (if --with-ui flag)
# - Migration for sprint-specific fields

php artisan module:install crm

# Downloads from marketplace, runs migrations, registers module

php artisan module:publish ProjectManager

# Publishes config, views, components for customization
```

---

## Module Variations (3-5 Typical Patterns)

### 1. **List-Detail Pattern** (Current: Project Manager)
- List view with filters/search/actions
- Detail view with nested children
- Form for create/edit
- **Examples**: Tasks, Contacts, Products, Orders

### 2. **Kanban/Board Pattern**
- Column-based visual organization
- Drag-and-drop between states
- Quick actions on cards
- **Examples**: Deal Pipeline, Project Roadmap, Recruitment

### 3. **Calendar/Timeline Pattern**
- Date-based visualization
- Drag to reschedule
- Multiple views (day/week/month)
- **Examples**: Schedule, Events, Milestones

### 4. **Tree/Hierarchy Pattern**
- Nested expandable structure
- Parent-child relationships
- Bulk operations on branches
- **Examples**: Category Management, Org Chart, File Browser

### 5. **Dashboard/Analytics Pattern**
- Metrics and KPIs
- Charts and visualizations
- Filterable date ranges
- **Examples**: Sales Dashboard, System Health, Usage Stats

---

## Configuration Storage Strategy

### Hybrid Approach (Recommended)

**Code-First (Development):**
```php
// app/Modules/ProjectManager/ProjectManagerModule.php
class ProjectManagerModule extends Module
{
    public function boot()
    {
        Module::register('project-manager', function($module) {
            $module->title('Project Manager')
                   ->types([...])
                   ->commands([...]);
        });
    }
}
```

**Database-Driven (Runtime):**
```php
// Admin can customize via UI, stored in DB
// Overrides code-based config
$module = Module::find('project-manager');
$module->config(['title' => 'Custom PM', ...])->save();
```

**Precedence**: Database > Code > Defaults

---

## Migration Path

### Phase 1: Extract Current System (Done ✅)
- ✅ Build Sprint/Task CRUD as reference
- ✅ Document patterns and conventions
- ✅ Identify reusable components

### Phase 2: Create Core Abstractions (Next)
- [ ] Build Module, Type, Field, Command builders
- [ ] Create ModuleRegistry
- [ ] Build generic UniversalListModal
- [ ] Build generic UniversalDetailModal
- [ ] Implement component auto-discovery

### Phase 3: Refactor Project Manager (Week 1-2)
- [ ] Convert Sprint/Task to module definition
- [ ] Test feature parity
- [ ] Document migration guide

### Phase 4: Build Second Module (Week 3-4)
- [ ] Choose CRM or Inventory
- [ ] Implement using module system
- [ ] Validate abstractions work

### Phase 5: CLI Scaffolding (Week 5-6)
- [ ] Build artisan commands
- [ ] Create module templates
- [ ] Add hot-reload support

### Phase 6: Marketplace & Discovery (Future)
- [ ] Module marketplace
- [ ] Install/uninstall flow
- [ ] Version management
- [ ] Dependency resolution

---

## Benefits of Module System

1. **Rapid Development**: Scaffold full CRUD in minutes vs days
2. **Consistency**: Same patterns everywhere
3. **Maintainability**: Change once, apply everywhere
4. **Extensibility**: Third-party modules
5. **Type Safety**: Full TypeScript support
6. **Testing**: Test module definitions, not implementations
7. **Documentation**: Self-documenting via config
8. **Permissions**: Built-in ability system
9. **Audit**: Automatic change tracking
10. **Flexibility**: Override any part of the stack

---

## Example: CRM Module (Conceptual)

```php
Module::make('crm')
    ->title('CRM')
    ->types([
        Type::make('contact')
            ->schema([
                Field::text('name')->required(),
                Field::email('email')->unique(),
                Field::phone('phone'),
                Field::select('status')->options(['lead', 'customer', 'inactive']),
            ])
            ->actions(['email', 'call', 'meeting']),
            
        Type::make('deal')
            ->schema([
                Field::text('title'),
                Field::money('value'),
                Field::select('stage')->options(['prospecting', 'proposal', 'negotiation', 'closed']),
                Field::belongsTo('contact'),
            ])
            ->layout('kanban')
            ->groupBy('stage'),
    ])
    ->register();
```

---

## Next Steps

1. **Create Tickets** for remaining Sprint/Task enhancements
2. **Document Sprint Component** with architecture diagrams
3. **Design Module Builder API** with team input
4. **Prototype Generic Components** for list/detail patterns
5. **Build First Module Definition** (refactor Project Manager)

---

**Questions to Explore:**

- How do we handle complex computed fields?
- How do nested relationships work (3+ levels)?
- How do we support custom validation rules?
- How do we handle file uploads in forms?
- How do we support real-time updates?
- How do we version module configs?
- How do we handle module dependencies?
