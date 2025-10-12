# Task: Command Generator System

## Overview
Create an artisan command that generates all necessary files for new slash commands, modals, and their integrations. This will standardize command creation and make migrating existing commands easier by regenerating them from configuration.

## Current State Analysis

### Gold Standard: Sprints → Tasks → Task Detail

The best example of the complete pattern:
- **List Command**: `/sprints` → `SprintListModal` (custom wrapper with progress bars)
- **Detail Command**: `/sprint-detail {code}` → `SprintDetailModal` (shows tasks)
- **Child Navigation**: Tasks within sprints → `/task-detail {task_code}`
- **Form Commands**: `/sprint-create`, `/sprint-edit` → `SprintFormModal`

**Why This Is Gold Standard**:
- Complete CRUD operations
- Parent-child navigation (sprints contain tasks)
- Custom modal with rich UI (progress bars, badges, stats)
- Config-driven click navigation
- All three modal patterns demonstrated

### Three Verified Modal Patterns

**Pattern A: Direct DataManagementModal** (Simplest)
- Examples: `/projects`, `/vaults` (after fixes)
- Zero custom code
- Auto-generated columns from data
- Config: `ui_modal_container: 'DataManagementModal'`
- Use when: Generic table display is sufficient

**Pattern B: Custom Wrapper Modal** (Moderate)
- Examples: `/sprints`, `/backlog`, `/bookmarks`
- Wraps `DataManagementModal` with custom columns
- Custom rendering (icons, badges, progress bars)
- Still uses base modal for table/search/filters
- Use when: Need custom column rendering or specialized UI

**Pattern C: Fully Custom Modal** (Complex)
- Examples: `/agents` (grid), `/todos` (self-fetching), `/security` (approval dashboard)
- Does NOT use `DataManagementModal`
- Completely custom layout and behavior
- Use when: Grid layout, special data fetching, or unique interactions needed

### Architecture Understanding (Updated)

**Data Flow**:
```
1. User types: /sprints
2. Backend: SprintListCommand->handle() returns $this->respond(['sprints' => $data])
3. BaseCommand wraps with config: { type, data, config: { ui, navigation } }
4. Frontend: CommandResultModal determines component from config.ui.modal_container
5. Props built from navigation_config.data_prop extraction
6. Component rendered with data
7. Click: navigation_config.detail_command executed
```

**Configuration Hierarchy**:
```
commands.ui_modal_container (HIGHEST PRIORITY)
  ↓ overrides
types_registry.default_card_component (transformed to modal)
  ↓ fallback
'UnifiedListModal' (DEFAULT)
```

**Navigation Config Structure**:
```json
{
  "data_prop": "sprints",           // Key in response data
  "item_key": "code",                // Unique identifier field
  "detail_command": "/sprint-detail", // Click navigation
  "parent_command": "/sprints",      // Back button
  "children": [                      // Nested navigation
    {
      "type": "Task",
      "command": "/task-detail",
      "item_key": "task_code"
    }
  ]
}
```

## Goal

Create artisan command:
```bash
php artisan make:command-module {name} --config={path/to/config.json}
```

Should generate:
- Handler class with proper `respond()` pattern
- Modal component (based on pattern type)
- Database seeder entry
- Frontend registration (COMPONENT_MAP import/entry)
- Navigation config
- Detail modal (if specified)
- Form modal (if CRUD enabled)

## Task Breakdown

### Phase 1: Config Schema & Planning

**Create**: `config/command-generator.php`

Define configuration schema:
```php
return [
    'name' => 'sprint',              // Singular name
    'plural' => 'sprints',           // Plural for commands
    'category' => 'Orchestration',   // Command category
    'model' => 'App\\Models\\Sprint', // Model class (optional)
    
    'pattern' => 'wrapper',          // 'direct', 'wrapper', 'custom'
    
    'commands' => [
        'list' => [
            'enabled' => true,
            'command' => '/sprints',
            'handler' => 'App\\Commands\\Orchestration\\Sprint\\ListCommand',
        ],
        'detail' => [
            'enabled' => true,
            'command' => '/sprint-detail',
            'handler' => 'App\\Commands\\Orchestration\\Sprint\\DetailCommand',
            'parameter' => 'code',  // URL parameter
        ],
        'create' => [
            'enabled' => true,
            'command' => '/sprint-create',
            'form_modal' => 'SprintFormModal',
        ],
        'edit' => [
            'enabled' => true,
            'command' => '/sprint-edit',
            'form_modal' => 'SprintFormModal',
        ],
        'delete' => [
            'enabled' => false,
        ],
    ],
    
    'modal' => [
        'list' => [
            'name' => 'SprintListModal',
            'columns' => [
                ['key' => 'code', 'label' => 'Sprint', 'sortable' => true],
                ['key' => 'title', 'label' => 'Title', 'sortable' => true],
                [
                    'key' => 'progress',
                    'label' => 'Progress',
                    'render' => 'progress-bar',
                    'params' => ['numerator' => 'completed_tasks', 'denominator' => 'total_tasks']
                ],
            ],
            'filters' => [
                ['key' => 'status', 'type' => 'select', 'options' => ['all', 'active', 'completed']],
            ],
            'icon' => 'Calendar',
            'icon_color' => 'blue',
        ],
        'detail' => [
            'name' => 'SprintDetailModal',
            'sections' => ['overview', 'tasks', 'stats'],
        ],
    ],
    
    'navigation' => [
        'data_prop' => 'sprints',
        'item_key' => 'code',
        'detail_command' => '/sprint-detail',
        'children' => [
            ['type' => 'Task', 'command' => '/task-detail', 'item_key' => 'task_code'],
        ],
    ],
    
    'data' => [
        'fields' => [
            'code' => ['type' => 'string', 'required' => true],
            'title' => ['type' => 'string', 'required' => true],
            'status' => ['type' => 'enum', 'values' => ['planning', 'active', 'completed']],
            'start_date' => ['type' => 'date', 'nullable' => true],
            'end_date' => ['type' => 'date', 'nullable' => true],
            'total_tasks' => ['type' => 'integer', 'computed' => true],
            'completed_tasks' => ['type' => 'integer', 'computed' => true],
        ],
    ],
];
```

**Output**: JSON schema specification document

---

### Phase 2: Generator Command Structure

**Create**: `app/Console/Commands/MakeCommandModule.php`

```php
class MakeCommandModule extends Command
{
    protected $signature = 'make:command-module 
                            {name : The module name} 
                            {--config= : Path to config file}
                            {--pattern=wrapper : Modal pattern (direct|wrapper|custom)}
                            {--dry-run : Show what would be generated}';
    
    public function handle()
    {
        // 1. Load and validate config
        // 2. Generate handler classes
        // 3. Generate modal components
        // 4. Generate seeder entry
        // 5. Update COMPONENT_MAP
        // 6. Output manual steps (if any)
    }
}
```

**Sub-generators needed**:
- `GenerateHandler` - Creates command handler classes
- `GenerateModal` - Creates modal components based on pattern
- `GenerateSeederEntry` - Creates seeder array entry
- `UpdateComponentMap` - Adds import + registration

---

### Phase 3: Handler Generator

**Create**: `app/Console/Commands/Generators/HandlerGenerator.php`

**Generates**:
```php
// app/Commands/Orchestration/Sprint/ListCommand.php
namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\Sprint;

class ListCommand extends BaseCommand
{
    public function handle(): array
    {
        $sprints = Sprint::query()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($sprint) => [
                'code' => $sprint->code,
                'title' => $sprint->title,
                // ... auto-generated from config.data.fields
            ]);

        return $this->respond([
            'sprints' => $sprints->toArray(),
        ]);
    }

    public static function getName(): string
    {
        return 'List Sprints';
    }

    public static function getDescription(): string
    {
        return 'Display all sprints with status and progress';
    }

    public static function getUsage(): string
    {
        return '/sprints';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
```

**Stub Location**: `resources/stubs/command/list-handler.stub`

**Variables**:
- `{{namespace}}`
- `{{className}}`
- `{{modelClass}}`
- `{{dataProp}}`
- `{{fields}}` (generated map function)

---

### Phase 4: Modal Generator (Pattern-Based)

**Create**: `app/Console/Commands/Generators/ModalGenerator.php`

#### Pattern A: Direct (No Generation Needed)
Just seeder entry pointing to `DataManagementModal`

#### Pattern B: Wrapper Modal

**Generates**: `resources/js/components/{module}/{Name}ListModal.tsx`

```typescript
import { DataManagementModal, ColumnDefinition } from '@/components/ui/DataManagementModal'
import { Badge } from '@/components/ui/badge'
import { Calendar } from 'lucide-react'

interface Sprint {
  code: string
  title: string
  status: string
  total_tasks: number
  completed_tasks: number
}

interface SprintListModalProps {
  isOpen: boolean
  onClose: () => void
  sprints: Sprint[]
  onRefresh?: () => void
  onSprintSelect?: (sprint: Sprint) => void
}

export function SprintListModal({ 
  isOpen, 
  onClose, 
  sprints = [],  // Auto-add default
  onRefresh,
  onSprintSelect
}: SprintListModalProps) {
  const columns: ColumnDefinition<Sprint>[] = [
    // Auto-generated from config.modal.columns
    {
      key: 'code',
      label: 'Sprint',
      sortable: true,
      render: (sprint) => (
        <div className="flex items-center gap-2">
          <Calendar className="h-4 w-4 text-blue-500" />
          <span className="font-medium">{sprint.code}</span>
        </div>
      )
    },
    // ... more columns
  ]

  return (
    <DataManagementModal
      isOpen={isOpen}
      onClose={onClose}
      title="Sprints"
      data={sprints}
      columns={columns}
      searchPlaceholder="Search sprints..."
      searchFields={['code', 'title']}
      onRefresh={onRefresh}
      onRowClick={onSprintSelect}
      clickableRows={true}
    />
  )
}
```

**Stub Location**: `resources/stubs/modal/wrapper-list.stub`

**Variables**:
- `{{componentName}}`
- `{{interfaceName}}`
- `{{dataProp}}`
- `{{iconImport}}`
- `{{columnDefinitions}}` (generated from config)

#### Pattern C: Custom Modal

**Output**: Manual instructions + stub template

```
⚠️  Custom modal pattern selected - manual implementation required

Stub created: resources/js/components/{module}/{Name}Modal.tsx
You must implement:
1. Custom layout structure
2. Data fetching (if self-fetching like TodoManagementModal)
3. Special interactions (grid, approval buttons, etc.)

Reference examples:
- Grid layout: AgentProfileGridModal
- Self-fetching: TodoManagementModal  
- Dashboard: SecurityDashboardModal
```

---

### Phase 5: Seeder Entry Generator

**Create**: `app/Console/Commands/Generators/SeederGenerator.php`

**Generates**: Seeder array entry (to stdout for manual paste)

```php
[
    'command' => '/sprints',
    'name' => 'List Sprints',
    'description' => 'Display all sprints with status and progress',
    'category' => 'Orchestration',
    'type_slug' => 'sprint',
    'handler_class' => 'App\\Commands\\Orchestration\\Sprint\\ListCommand',
    'available_in_slash' => true,
    'available_in_cli' => false,
    'available_in_mcp' => true,
    'ui_modal_container' => 'SprintListModal',
    'ui_layout_mode' => 'table',
    'navigation_config' => [
        'data_prop' => 'sprints',
        'item_key' => 'code',
        'detail_command' => '/sprint-detail',
        'children' => [
            ['type' => 'Task', 'command' => '/task-detail', 'item_key' => 'task_code'],
        ],
    ],
    'ui_card_component' => null,
    'ui_detail_component' => null,
    'filters' => null,
    'default_sort' => ['field' => 'created_at', 'direction' => 'desc'],
    'pagination_default' => 50,
    'is_active' => true,
],
```

---

### Phase 6: COMPONENT_MAP Updater

**Create**: `app/Console/Commands/Generators/ComponentMapUpdater.php`

**Updates**: `resources/js/islands/chat/CommandResultModal.tsx`

**Adds**:
```typescript
// Import at top
import { SprintListModal } from '@/components/orchestration/SprintListModal'

// In COMPONENT_MAP
const COMPONENT_MAP: Record<string, React.ComponentType<any>> = {
  // ... existing
  'SprintListModal': SprintListModal,
}
```

**Strategy**: Parse file, insert in alphabetical order, preserve formatting

---

### Phase 7: Detail Modal Generator (Optional)

**Generates**: `resources/js/components/{module}/{Name}DetailModal.tsx`

Template for detail view with sections:
- Overview (main fields)
- Related items (children)
- Stats/metadata
- Actions

---

### Phase 8: Form Modal Generator (Optional)

**Generates**: `resources/js/components/{module}/{Name}FormModal.tsx`

Template for create/edit forms with:
- Form fields from config.data.fields
- Validation
- Submit handler
- Cancel/Save buttons

---

## File Structure

```
app/Console/Commands/
  MakeCommandModule.php
  Generators/
    HandlerGenerator.php
    ModalGenerator.php
    SeederGenerator.php
    ComponentMapUpdater.php
    DetailModalGenerator.php
    FormModalGenerator.php

resources/stubs/
  command/
    list-handler.stub
    detail-handler.stub
    form-handler.stub
  modal/
    wrapper-list.stub
    detail.stub
    form.stub
  config/
    command-module-example.json

config/
  command-generator.php
```

## Example Usage

### Generate Complete Sprint System

**1. Create config file**: `config/sprint-module.json`

```json
{
  "name": "sprint",
  "plural": "sprints",
  "category": "Orchestration",
  "model": "App\\Models\\Sprint",
  "pattern": "wrapper",
  "commands": {
    "list": { "enabled": true },
    "detail": { "enabled": true, "parameter": "code" },
    "create": { "enabled": true },
    "edit": { "enabled": true }
  },
  "modal": {
    "list": {
      "columns": [
        {"key": "code", "label": "Sprint", "icon": "Calendar"},
        {"key": "title", "label": "Title"},
        {"key": "progress", "label": "Progress", "render": "progress-bar"}
      ]
    }
  },
  "navigation": {
    "data_prop": "sprints",
    "item_key": "code",
    "detail_command": "/sprint-detail",
    "children": [
      {"type": "Task", "command": "/task-detail", "item_key": "task_code"}
    ]
  }
}
```

**2. Run generator**:
```bash
php artisan make:command-module sprint --config=config/sprint-module.json
```

**3. Output**:
```
✅ Generated: app/Commands/Orchestration/Sprint/ListCommand.php
✅ Generated: app/Commands/Orchestration/Sprint/DetailCommand.php
✅ Generated: resources/js/components/orchestration/SprintListModal.tsx
✅ Generated: resources/js/components/orchestration/SprintDetailModal.tsx
✅ Updated: resources/js/islands/chat/CommandResultModal.tsx

📋 Manual Steps Required:

1. Add seeder entry to database/seeders/CommandsSeeder.php:
   (See generated-seeder-entry.php)

2. Run:
   php artisan db:seed --class=CommandsSeeder
   php artisan cache:clear
   npm run build

3. Test:
   /sprints
   /sprint-detail SPRINT-001

✅ Module generation complete!
```

---

## Manual Steps (Always Required)

Even with full generation, these steps remain manual:

1. **Add to seeder** - Paste generated array into CommandsSeeder.php
2. **Run seeder** - `php artisan db:seed --class=CommandsSeeder`
3. **Clear cache** - `php artisan cache:clear`
4. **Build frontend** - `npm run build` (hot reload doesn't work)
5. **Test** - Verify command works in chat

**Why not automate?**:
- Seeder: Risk of corrupting existing entries, better to paste manually
- Build: Already part of deployment workflow
- Testing: Requires human verification

---

## Success Criteria

- [ ] Can regenerate existing Sprint system identically
- [ ] Generates all three patterns (direct/wrapper/custom)
- [ ] Creates proper navigation_config
- [ ] Handlers use correct respond() pattern
- [ ] Modals have default prop values
- [ ] COMPONENT_MAP automatically updated
- [ ] Clear manual instructions for remaining steps
- [ ] Dry-run mode shows what would be generated
- [ ] Generated code passes linting

---

## Reference Documents

**Architecture**:
- `docs/NAVIGATION_SYSTEM_COMPLETE_GUIDE.md` - How the system works
- `docs/COMMAND_UI_ARCHITECTURE.md` - Configuration layers
- `docs/MODULE_CONFIGURATION_AUDIT.md` - All patterns analyzed

**Wiring**:
- `docs/WIRING_NEW_COMMAND_CHECKLIST.md` - Manual steps (base for automation)

**Code Examples**:
- Backend: `app/Commands/Orchestration/Sprint/ListCommand.php`
- Frontend: `resources/js/components/orchestration/SprintListModal.tsx`
- Detail: `resources/js/components/orchestration/SprintDetailModal.tsx`
- Seeder: `database/seeders/CommandsSeeder.php` (lines 35-66)

**Working Examples**:
- Pattern A: Projects, Vaults (direct DataManagementModal)
- Pattern B: Sprints, Backlog, Bookmarks (wrappers)
- Pattern C: Agents (grid), Todos (self-fetching), Security (dashboard)

---

## Notes for Implementation

### Pattern Detection
Config `pattern` field determines generation:
- `direct`: No modal generation, use DataManagementModal
- `wrapper`: Generate wrapper modal with custom columns
- `custom`: Stub template + manual instructions

### Column Rendering
Support these render types:
- `text` (default)
- `badge` (status colors)
- `progress-bar` (with numerator/denominator)
- `icon-text` (icon + text)
- `date` (formatted date)
- `custom` (requires manual implementation)

### Validation
Before generation:
- Config schema valid
- Handler class doesn't exist (or --force flag)
- Model class exists (if specified)
- COMPONENT_MAP can be parsed

### Rollback
If generation fails midway:
- Keep generated files (don't delete)
- Log what was created
- User can manually clean up or re-run with --force

---

## Future Enhancements

**Phase 2** (after initial implementation):
- Generate tests for handlers
- Generate Storybook stories for modals
- API endpoint generation for CRUD
- Migration generator for types_registry
- Validation rules from config
- OpenAPI/MCP schema generation

**Phase 3** (advanced):
- Interactive mode (prompts for config)
- Template marketplace (community patterns)
- Migration assistant (converts existing commands)
- Visual config builder (GUI)

---

## Estimated Effort

**Phase 1-3** (Core generator + handlers + basic modals): 12-16 hours
**Phase 4-5** (Pattern-based modals + seeder): 8-10 hours  
**Phase 6-8** (COMPONENT_MAP + detail/form): 8-10 hours
**Testing + Polish**: 4-6 hours

**Total**: 32-42 hours (4-5 days)

---

## Success Story

After implementation, creating a new module should take:
- **Manual (current)**: 2-3 hours
- **Generated (future)**: 10 minutes + testing

Example:
```bash
# 1 minute: Write config
vim config/project-module.json

# 1 minute: Generate
php artisan make:command-module project --config=config/project-module.json

# 3 minutes: Manual steps
# - Paste seeder entry
# - Run seeder + cache clear
# - npm run build

# 5 minutes: Test + iterate
/projects
/project-detail 123

Total: ~10 minutes vs 2-3 hours manually! 🎉
```
