# Command System Migration Plan

**Date**: 2025-10-09  
**Status**: DRAFT - Awaiting strategic decisions  
**Goal**: Unify command systems, eliminate confusion, reduce maintenance burden

---

## Migration Strategy Overview

This plan consolidates two separate command systems into one unified, well-documented system that serves both users and agents effectively.

### Core Principles

1. **Single Source of Truth** - One command implementation, multiple interfaces
2. **Clear Boundaries** - Explicit user vs agent concerns
3. **MCP First** - Agent access via clean MCP interface, not CLI hacks
4. **Backwards Compatible** - Phased migration, no breaking changes
5. **Documentation Driven** - Docs and type hints prevent regressions

---

## Proposed Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Command Layer                         │
│                  App\Commands\*                          │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ Orchestration│  │   Content    │  │  Navigation  │ │
│  │              │  │              │  │              │ │
│  │ • Sprints    │  │ • Search     │  │ • Inbox      │ │
│  │ • Tasks      │  │ • Notes      │  │ • Recall     │ │
│  │ • Agents     │  │ • Todo       │  │ • Channels   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
                          │
         ┌────────────────┼────────────────┐
         │                │                │
         ▼                ▼                ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   Web UI    │  │  MCP Server │  │ Artisan CLI │
│             │  │             │  │             │
│ TipTap      │  │ Agents via  │  │ Direct CLI  │
│ /commands   │  │ tools       │  │ invocation  │
│             │  │             │  │             │
│ Returns:    │  │ Returns:    │  │ Returns:    │
│ Component   │  │ JSON        │  │ Table/JSON  │
│ + Data      │  │ + Schema    │  │             │
└─────────────┘  └─────────────┘  └─────────────┘
```

### Key Changes

1. **Unified Command Classes** - All in `App\Commands\` with subnamespaces
2. **Smart Response Handling** - Commands detect context (web/MCP/CLI) and return appropriate format
3. **MCP Server Enhancement** - Expose all commands via MCP with clean syntax
4. **YAML Cleanup** - Remove deprecated system
5. **Documentation First** - Prevent future regressions

---

## Phase 1: Foundation (Week 1)

### Goal: Establish unified namespace and patterns

#### 1.1 Create Subnamespace Structure

**Action**: Organize commands into logical groups

```
app/Commands/
├── BaseCommand.php (existing)
├── BaseListCommand.php (existing)
├── Orchestration/
│   ├── Sprint/
│   │   ├── ListCommand.php
│   │   ├── DetailCommand.php
│   │   ├── SaveCommand.php
│   │   └── UpdateStatusCommand.php
│   ├── Task/
│   │   ├── ListCommand.php
│   │   ├── DetailCommand.php
│   │   ├── AssignCommand.php
│   │   └── UpdateStatusCommand.php
│   └── Agent/
│       ├── ListCommand.php
│       ├── DetailCommand.php
│       ├── SaveCommand.php
│       └── UpdateStatusCommand.php
├── Content/
│   ├── SearchCommand.php
│   ├── RecallCommand.php
│   ├── NoteCommand.php
│   └── TodoCommand.php
├── Navigation/
│   ├── InboxCommand.php
│   ├── ChannelsCommand.php
│   └── SessionsCommand.php
└── Utility/
    ├── HelpCommand.php
    ├── ClearCommand.php
    ├── RoutingCommand.php
    └── TypeManagementCommand.php
```

**Files to Create/Move**: ~50 files

**Effort**: 4 hours

#### 1.2 Update CommandRegistry

**Action**: Update registry to use new namespaces

```php
<?php

namespace App\Services;

class CommandRegistry
{
    protected static array $phpCommands = [
        // Orchestration - Sprints
        'sprints' => \App\Commands\Orchestration\Sprint\ListCommand::class,
        'sprint-list' => \App\Commands\Orchestration\Sprint\ListCommand::class,
        'sl' => \App\Commands\Orchestration\Sprint\ListCommand::class,
        'sprint-detail' => \App\Commands\Orchestration\Sprint\DetailCommand::class,
        'sd' => \App\Commands\Orchestration\Sprint\DetailCommand::class,
        
        // Orchestration - Tasks
        'tasks' => \App\Commands\Orchestration\Task\ListCommand::class,
        'task-list' => \App\Commands\Orchestration\Task\ListCommand::class,
        'tl' => \App\Commands\Orchestration\Task\ListCommand::class,
        'task-detail' => \App\Commands\Orchestration\Task\DetailCommand::class,
        'td' => \App\Commands\Orchestration\Task\DetailCommand::class,
        
        // ... etc
    ];
    
    // Add new method for MCP-friendly slugs
    protected static array $mcpAliases = [
        'sprint.list' => 'sprints',
        'sprint.detail' => 'sprint-detail',
        'task.list' => 'tasks',
        'task.detail' => 'task-detail',
        // ... etc
    ];
    
    public static function findByMcpAlias(string $alias): string
    {
        $slug = self::$mcpAliases[$alias] ?? $alias;
        return self::find($slug);
    }
}
```

**Files to Update**: 1 file

**Effort**: 2 hours

#### 1.3 Enhance BaseCommand

**Action**: Add context detection and smart response handling

```php
<?php

namespace App\Commands;

abstract class BaseCommand
{
    protected ?string $context = null; // 'web', 'mcp', 'cli'
    
    abstract public function handle(): array;
    
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }
    
    /**
     * Format response based on context
     */
    protected function respond(array $data): array
    {
        return match($this->context) {
            'web' => $this->webResponse($data),
            'mcp' => $this->mcpResponse($data),
            'cli' => $this->cliResponse($data),
            default => $data,
        };
    }
    
    /**
     * Web UI response (component + data)
     */
    protected function webResponse(array $data): array
    {
        return [
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'data' => $data,
        ];
    }
    
    /**
     * MCP response (structured JSON with schema)
     */
    protected function mcpResponse(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'command' => static::class,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
    
    /**
     * CLI response (same as MCP but can add table formatting hints)
     */
    protected function cliResponse(array $data): array
    {
        return $this->mcpResponse($data);
    }
    
    // Override in subclasses as needed
    protected function getType(): string
    {
        return 'generic';
    }
    
    protected function getComponent(): string
    {
        return 'UnifiedListModal';
    }
    
    // Existing methods
    abstract public static function getName(): string;
    abstract public static function getDescription(): string;
    abstract public static function getUsage(): string;
    abstract public static function getCategory(): string;
}
```

**Files to Update**: 1 file

**Effort**: 3 hours

#### 1.4 Testing

**Action**: Ensure existing commands still work

- [ ] Web UI commands work as before
- [ ] CLI commands work as before
- [ ] No breaking changes
- [ ] Run test suite: `composer test`

**Effort**: 2 hours

**Total Phase 1 Effort**: ~11 hours (1.5 days)

---

## Phase 2: Consolidation (Week 1-2)

### Goal: Merge duplicate commands

#### 2.1 Identify Merge Candidates

**Duplicates to Merge**:

| User Command | Agent Command | New Unified Command |
|--------------|---------------|---------------------|
| `SprintListCommand` | `OrchestrationSprintsCommand` | `Orchestration\Sprint\ListCommand` |
| `SprintDetailCommand` | `OrchestrationSprintDetailCommand` | `Orchestration\Sprint\DetailCommand` |
| `TaskListCommand` | `OrchestrationTasksCommand` | `Orchestration\Task\ListCommand` |
| `TaskDetailCommand` | `OrchestrationTaskDetailCommand` | `Orchestration\Task\DetailCommand` |
| `AgentListCommand` | `OrchestrationAgentsCommand` | `Orchestration\Agent\ListCommand` |

**Total**: 5 merges

#### 2.2 Merge Pattern

**Strategy**: Keep best features from both, use context-aware response

**Example: Sprint List**

```php
<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\Sprint;
use App\Models\WorkItem;

class ListCommand extends BaseCommand
{
    // Filters (from agent command)
    protected ?array $codes = null;
    protected int $limit = 20;
    protected bool $includeDetails = false;
    protected int $tasksLimit = 5;
    
    public function __construct(array $options = [])
    {
        $this->codes = $options['codes'] ?? null;
        $this->limit = $options['limit'] ?? 20;
        $this->includeDetails = $options['details'] ?? false;
        $this->tasksLimit = $options['tasks_limit'] ?? 5;
    }
    
    public function handle(): array
    {
        $sprints = $this->fetchSprints();
        $data = $sprints->map(fn($sprint) => $this->formatSprint($sprint));
        
        return $this->respond($data->toArray());
    }
    
    private function fetchSprints()
    {
        return Sprint::query()
            ->when($this->codes, fn($q) => $q->whereIn('code', $this->codes))
            ->orderByDesc('created_at')
            ->limit($this->limit)
            ->get();
    }
    
    private function formatSprint(Sprint $sprint): array
    {
        $stats = $this->calculateStats($sprint);
        
        $formatted = [
            'id' => $sprint->id,
            'code' => $sprint->code,
            'title' => $sprint->meta['title'] ?? $sprint->code,
            'priority' => $sprint->meta['priority'] ?? null,
            'estimate' => $sprint->meta['estimate'] ?? null,
            'stats' => $stats,
        ];
        
        // Include tasks if requested (agent use case)
        if ($this->includeDetails) {
            $formatted['tasks'] = $this->fetchTasks($sprint);
        }
        
        return $formatted;
    }
    
    private function calculateStats(Sprint $sprint): array
    {
        $query = WorkItem::where('metadata->sprint_code', $sprint->code);
        
        return [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('delegation_status', 'completed')->count(),
            'in_progress' => (clone $query)->whereIn('delegation_status', ['assigned', 'in_progress'])->count(),
            'blocked' => (clone $query)->where('delegation_status', 'blocked')->count(),
            'unassigned' => (clone $query)->where('delegation_status', 'unassigned')->count(),
        ];
    }
    
    private function fetchTasks(Sprint $sprint): array
    {
        return WorkItem::where('metadata->sprint_code', $sprint->code)
            ->orderByDesc('created_at')
            ->limit($this->tasksLimit)
            ->get()
            ->map(fn($task) => [
                'task_code' => $task->metadata['task_code'],
                'delegation_status' => $task->delegation_status,
                'estimate_text' => $task->metadata['estimate_text'] ?? null,
            ])
            ->toArray();
    }
    
    // BaseCommand overrides
    protected function getType(): string
    {
        return 'sprint';
    }
    
    protected function getComponent(): string
    {
        return 'SprintListModal';
    }
    
    public static function getName(): string
    {
        return 'Sprint List';
    }
    
    public static function getDescription(): string
    {
        return 'List sprints with progress stats and optional task details';
    }
    
    public static function getUsage(): string
    {
        return '/sprints [--codes=...] [--limit=20] [--details]';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
```

**Files to Create**: 5 new unified commands

**Files to Update**: CommandRegistry (5 entries)

**Files to Deprecate**: 10 old commands (mark as deprecated, keep for backwards compat)

**Effort**: 12 hours (2-3 hours per merge)

#### 2.3 Update Artisan Console Commands

**Action**: Make artisan commands thin wrappers around unified commands

```php
<?php

namespace App\Console\Commands;

use App\Commands\Orchestration\Sprint\ListCommand as SprintList;
use Illuminate\Console\Command;

class OrchestrationSprintsCommand extends Command
{
    protected $signature = 'orchestration:sprints
        {--code=* : Limit to specific sprint codes}
        {--limit=10 : Maximum number of sprints}
        {--details : Include task details}
        {--tasks-limit=5 : Number of tasks per sprint}
        {--json : Output JSON}';

    protected $description = 'List orchestration sprints';

    public function handle(): int
    {
        $command = new SprintList([
            'codes' => $this->option('code') ?: null,
            'limit' => (int) $this->option('limit'),
            'details' => $this->option('details'),
            'tasks_limit' => (int) $this->option('tasks-limit'),
        ]);
        
        $command->setContext('cli');
        $result = $command->handle();
        
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->outputTable($result['data']);
        }
        
        return self::SUCCESS;
    }
    
    private function outputTable(array $sprints): void
    {
        $this->table(
            ['Code', 'Title', 'Total', 'Completed', 'In Progress', 'Blocked'],
            array_map(fn($s) => [
                $s['code'],
                \Str::limit($s['title'], 40),
                $s['stats']['total'],
                $s['stats']['completed'],
                $s['stats']['in_progress'],
                $s['stats']['blocked'],
            ], $sprints)
        );
    }
}
```

**Files to Update**: 5 console commands

**Effort**: 6 hours

#### 2.4 Testing

**Action**: Comprehensive testing of merged commands

- [ ] Web UI: `/sprints`, `/tasks`, `/agents` work
- [ ] CLI: `php artisan orchestration:sprints` works
- [ ] CLI: `php artisan orchestration:tasks --sprint=SPRINT-67` works
- [ ] Data parity: Same data from web/cli
- [ ] Filters work correctly
- [ ] Run test suite

**Effort**: 4 hours

**Total Phase 2 Effort**: ~22 hours (3 days)

---

## Phase 3: MCP Enhancement (Week 2)

### Goal: Expose unified commands via MCP with clean syntax

#### 3.1 Create MCP Command Bridge

**Action**: Build MCP server that exposes commands as tools

```php
<?php

namespace App\Mcp;

use App\Services\CommandRegistry;

class OrchestrationMcpServer
{
    /**
     * Map MCP tool names to command classes
     */
    private array $toolMap = [
        // Sprints
        'sprint_list' => ['command' => 'sprints', 'name' => 'List Sprints'],
        'sprint_detail' => ['command' => 'sprint-detail', 'name' => 'Sprint Detail'],
        'sprint_save' => ['command' => 'sprint-save', 'name' => 'Save Sprint'],
        'sprint_update_status' => ['command' => 'sprint-status', 'name' => 'Update Sprint Status'],
        
        // Tasks
        'task_list' => ['command' => 'tasks', 'name' => 'List Tasks'],
        'task_detail' => ['command' => 'task-detail', 'name' => 'Task Detail'],
        'task_assign' => ['command' => 'task-assign', 'name' => 'Assign Task'],
        'task_update_status' => ['command' => 'task-status', 'name' => 'Update Task Status'],
        
        // Agents
        'agent_list' => ['command' => 'agents', 'name' => 'List Agents'],
        'agent_detail' => ['command' => 'agent-detail', 'name' => 'Agent Detail'],
        'agent_save' => ['command' => 'agent-save', 'name' => 'Save Agent'],
        
        // Content
        'search' => ['command' => 'search', 'name' => 'Search Fragments'],
        'recall' => ['command' => 'recall', 'name' => 'Recall Memory'],
        'inbox' => ['command' => 'inbox', 'name' => 'View Inbox'],
        'todo' => ['command' => 'todo', 'name' => 'Manage Todos'],
        
        // Navigation
        'channels' => ['command' => 'channels', 'name' => 'List Channels'],
        'sessions' => ['command' => 'session', 'name' => 'List Sessions'],
    ];
    
    public function listTools(): array
    {
        return array_map(fn($slug, $info) => [
            'name' => $slug,
            'description' => $this->getCommandDescription($info['command']),
            'inputSchema' => $this->getCommandSchema($info['command']),
        ], array_keys($this->toolMap), $this->toolMap);
    }
    
    public function callTool(string $toolName, array $arguments): array
    {
        if (!isset($this->toolMap[$toolName])) {
            throw new \Exception("Unknown tool: {$toolName}");
        }
        
        $commandSlug = $this->toolMap[$toolName]['command'];
        $commandClass = CommandRegistry::find($commandSlug);
        
        $command = new $commandClass($arguments);
        $command->setContext('mcp');
        
        return $command->handle();
    }
    
    private function getCommandDescription(string $slug): string
    {
        $class = CommandRegistry::find($slug);
        return $class::getDescription();
    }
    
    private function getCommandSchema(string $slug): array
    {
        $class = CommandRegistry::find($slug);
        
        // Use PHP 8 attributes or method to define schema
        // For now, return basic schema
        return [
            'type' => 'object',
            'properties' => $class::getInputSchema(),
        ];
    }
}
```

**Files to Create**: 1 new MCP server class

**Effort**: 8 hours

#### 3.2 Add Schema Definitions to Commands

**Action**: Add input schema method to BaseCommand

```php
<?php

namespace App\Commands;

abstract class BaseCommand
{
    // ... existing code ...
    
    /**
     * Define MCP input schema
     * Override in subclasses to specify parameters
     */
    public static function getInputSchema(): array
    {
        return [];
    }
}
```

**Action**: Add schemas to unified commands

```php
<?php

namespace App\Commands\Orchestration\Sprint;

class ListCommand extends BaseCommand
{
    public static function getInputSchema(): array
    {
        return [
            'codes' => [
                'type' => 'array',
                'description' => 'Filter by sprint codes (e.g., ["SPRINT-67"])',
                'items' => ['type' => 'string'],
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of sprints to return',
                'default' => 20,
                'required' => false,
            ],
            'details' => [
                'type' => 'boolean',
                'description' => 'Include task details for each sprint',
                'default' => false,
                'required' => false,
            ],
        ];
    }
}
```

**Files to Update**: 20+ command classes

**Effort**: 8 hours

#### 3.3 Update MCP Configuration

**Action**: Update `.mcp.json` to expose new server

```json
{
  "mcpServers": {
    "orch": {
      "command": "php",
      "args": ["./artisan", "orchestration:mcp"],
      "description": "Fragments Engine Orchestration - Sprints, Tasks, Agents, and Content"
    }
  }
}
```

**Files to Update**: 1 file

**Effort**: 1 hour

#### 3.4 Testing

**Action**: Test MCP tool exposure

- [ ] MCP server starts: `php artisan orchestration:mcp`
- [ ] Tools listed correctly
- [ ] Tools callable: `sprint_list`, `task_list`, etc.
- [ ] Schemas validate correctly
- [ ] Responses formatted correctly
- [ ] Test from Claude Desktop/agent

**Effort**: 4 hours

**Total Phase 3 Effort**: ~21 hours (2.5 days)

---

## Phase 4: CLI Enhancement (Week 2-3)

### Goal: Add missing write operations and filters

#### 4.1 Add Write Operations to User Commands

**New Commands to Create**:

- `Orchestration\Sprint\SaveCommand` - Create/update sprint
- `Orchestration\Task\SaveCommand` - Create/update task
- `Orchestration\Task\AssignCommand` - Assign task to agent
- `Orchestration\Task\UpdateStatusCommand` - Update task status
- `Orchestration\Agent\SaveCommand` - Create/update agent

**Pattern**: Same as read commands, but with validation and write logic

**Files to Create**: 5 new write commands

**Effort**: 15 hours (3 hours each)

#### 4.2 Add Filters to User Commands

**Action**: Enhance user commands with agent command filters

**Example: Task List with Filters**

```php
<?php

namespace App\Commands\Orchestration\Task;

class ListCommand extends BaseCommand
{
    protected ?array $sprints = null;
    protected ?array $delegationStatuses = null;
    protected ?array $statuses = null;
    protected ?string $agent = null;
    protected ?string $search = null;
    protected int $limit = 50;
    
    public function __construct(array $options = [])
    {
        $this->sprints = $options['sprints'] ?? null;
        $this->delegationStatuses = $options['delegation_statuses'] ?? null;
        $this->statuses = $options['statuses'] ?? null;
        $this->agent = $options['agent'] ?? null;
        $this->search = $options['search'] ?? null;
        $this->limit = $options['limit'] ?? 50;
    }
    
    public function handle(): array
    {
        $tasks = $this->fetchTasks();
        return $this->respond($tasks->toArray());
    }
    
    private function fetchTasks()
    {
        $query = WorkItem::query()->whereNotNull('metadata->task_code');
        
        if ($this->sprints) {
            $query->whereIn('metadata->sprint_code', $this->sprints);
        }
        
        if ($this->delegationStatuses) {
            $query->whereIn('delegation_status', $this->delegationStatuses);
        }
        
        if ($this->statuses) {
            $query->whereIn('status', $this->statuses);
        }
        
        if ($this->agent) {
            $query->where('delegation_context->agent_recommendation', $this->agent);
        }
        
        if ($this->search) {
            $query->where(function($q) {
                $like = "%{$this->search}%";
                $q->where('metadata->task_code', 'like', $like)
                  ->orWhere('metadata->task_name', 'like', $like)
                  ->orWhere('metadata->description', 'like', $like);
            });
        }
        
        return $query->orderByDesc('created_at')->limit($this->limit)->get();
    }
}
```

**Files to Update**: 3 list commands (Sprint, Task, Agent)

**Effort**: 6 hours

#### 4.3 Update Frontend for Filters

**Action**: Add filter UI to modals (optional, can defer)

- Add filter dropdowns/inputs to `TaskListModal`, `SprintListModal`, etc.
- Wire up filters to command invocation

**Files to Update**: 3 modal components

**Effort**: 12 hours (4 hours per modal) - **OPTIONAL, can defer to later**

#### 4.4 Testing

**Action**: Test new write operations and filters

- [ ] Create sprint via UI/MCP/CLI
- [ ] Update sprint status
- [ ] Create/assign tasks
- [ ] Filters work in UI/MCP/CLI
- [ ] Run test suite

**Effort**: 6 hours

**Total Phase 4 Effort**: ~27 hours (3.5 days, or 2 days without UI work)

---

## Phase 5: YAML Cleanup (Week 3)

### Goal: Remove deprecated YAML system

#### 5.1 Audit YAML Commands

**Action**: Verify all YAML commands have PHP equivalents

```bash
cd fragments/commands
for dir in */; do
  echo "Checking $dir..."
  # Check if PHP equivalent exists
done
```

**Create Audit Report**:

```markdown
# YAML Command Audit

| YAML Command | PHP Equivalent | Status | Action |
|--------------|----------------|--------|--------|
| accept | AcceptCommand | ✅ Migrated | Delete |
| agent-profiles | AgentListCommand | ✅ Migrated | Delete |
| backlog-list | BacklogListCommand | ✅ Migrated | Delete |
| ... | ... | ... | ... |
```

**Effort**: 2 hours

#### 5.2 Archive YAML Files

**Action**: Move YAML to archive folder

```bash
mkdir -p fragments/commands-legacy
mv fragments/commands/* fragments/commands-legacy/
```

**Create README**:

```markdown
# Legacy YAML Commands

These YAML-based commands have been migrated to PHP classes in `app/Commands/`.

**Do not use these files for new development.**

They are archived for reference only.

Migration date: 2025-10-09
```

**Effort**: 1 hour

#### 5.3 Remove YAML Fallback Logic

**Action**: Remove any code that references YAML commands

**Files to Check**:
- `CommandController.php`
- `CommandRegistry.php`
- Any DSL runner classes

**Effort**: 2 hours

#### 5.4 Update Documentation

**Action**: Update all docs to remove YAML references

**Files to Update**:
- `COMMAND_QUICK_REFERENCE.md`
- `COMMAND_SYSTEM_FIX_SUMMARY.md`
- Any orchestration docs

**Effort**: 2 hours

#### 5.5 Testing

**Action**: Verify no regressions

- [ ] All commands work without YAML
- [ ] No 404s or missing command errors
- [ ] Run full test suite

**Effort**: 2 hours

**Total Phase 5 Effort**: ~9 hours (1 day)

---

## Phase 6: Documentation & Hardening (Week 3)

### Goal: Prevent future regressions with comprehensive docs

#### 6.1 Create Command Development Guide

**Action**: Write comprehensive guide for adding new commands

**File**: `docs/command systems/COMMAND_DEVELOPMENT_GUIDE.md`

**Contents**:
- Architecture overview
- Command class structure
- How to add a new command
- How to expose via MCP
- How to add UI modal
- Testing checklist
- Common pitfalls
- Examples

**Effort**: 4 hours

#### 6.2 Create Agent Guidelines

**Action**: Clear instructions for AI agents

**File**: `docs/command systems/AGENT_COMMAND_GUIDELINES.md`

**Contents**:
- ⛔ What NEVER to do
- ✅ What ALWAYS to do
- Command invocation patterns
- MCP tool usage
- Troubleshooting
- Examples

**Effort**: 2 hours

#### 6.3 Add Type Hints & Validation

**Action**: Add strict typing to prevent errors

```php
<?php

namespace App\Commands;

abstract class BaseCommand
{
    /**
     * @return array{type: string, component: string, data: array}
     */
    abstract public function handle(): array;
    
    /**
     * @param 'web'|'mcp'|'cli' $context
     */
    public function setContext(string $context): self
    {
        if (!in_array($context, ['web', 'mcp', 'cli'])) {
            throw new \InvalidArgumentException("Invalid context: {$context}");
        }
        $this->context = $context;
        return $this;
    }
}
```

**Files to Update**: All command classes

**Effort**: 6 hours

#### 6.4 Add Command Tests

**Action**: Create test suite for commands

```php
<?php

namespace Tests\Feature\Commands;

use App\Commands\Orchestration\Sprint\ListCommand;
use Tests\TestCase;

class SprintListCommandTest extends TestCase
{
    /** @test */
    public function it_lists_sprints_for_web_context()
    {
        $command = new ListCommand();
        $command->setContext('web');
        
        $result = $command->handle();
        
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('component', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('sprint', $result['type']);
        $this->assertEquals('SprintListModal', $result['component']);
    }
    
    /** @test */
    public function it_lists_sprints_for_mcp_context()
    {
        $command = new ListCommand();
        $command->setContext('mcp');
        
        $result = $command->handle();
        
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertTrue($result['success']);
    }
    
    /** @test */
    public function it_filters_sprints_by_code()
    {
        $command = new ListCommand(['codes' => ['SPRINT-67']]);
        $result = $command->handle();
        
        // Assert filtered correctly
    }
}
```

**Files to Create**: ~20 test files

**Effort**: 12 hours

#### 6.5 Update All Documentation

**Action**: Comprehensive doc updates

**Files to Update**:
- `COMMAND_QUICK_REFERENCE.md` - Add new commands, update patterns
- `COMMAND_SYSTEM_FIX_SUMMARY.md` - Mark as superseded
- `docs/orchestration/README.md` - Update command invocations
- `README.md` - Update command system description
- `.claude/CLAUDE.md` (if exists) - Add command system guidelines

**Effort**: 4 hours

**Total Phase 6 Effort**: ~28 hours (3.5 days)

---

## Migration Timeline

### Conservative Estimate (Full Implementation)

| Phase | Description | Duration | Dependencies |
|-------|-------------|----------|--------------|
| **Phase 1** | Foundation | 1.5 days | None |
| **Phase 2** | Consolidation | 3 days | Phase 1 |
| **Phase 3** | MCP Enhancement | 2.5 days | Phase 2 |
| **Phase 4** | CLI Enhancement | 2 days | Phase 3 |
| **Phase 5** | YAML Cleanup | 1 day | Phase 4 |
| **Phase 6** | Documentation | 3.5 days | Phase 5 |
| **TOTAL** | | **13.5 days** | |

### Aggressive Estimate (MVP)

If we skip optional UI work and defer some documentation:

| Phase | Description | Duration | Notes |
|-------|-------------|----------|-------|
| **Phase 1** | Foundation | 1 day | Core only |
| **Phase 2** | Consolidation | 2 days | Skip some tests |
| **Phase 3** | MCP Enhancement | 2 days | Basic schemas |
| **Phase 4** | CLI Enhancement | 1.5 days | Skip UI filters |
| **Phase 5** | YAML Cleanup | 0.5 days | Quick archive |
| **Phase 6** | Documentation | 2 days | Essential only |
| **TOTAL** | | **9 days** | |

### Recommended Approach

**Week 1**: Phases 1-2 (Foundation + Consolidation)
- Focus on namespace consolidation and merging duplicates
- Get core architecture right
- Ensure no breaking changes

**Week 2**: Phase 3-4 (MCP + CLI Enhancement)
- MCP exposure with clean syntax
- Add write operations
- Skip UI filters (defer to later)

**Week 3**: Phase 5-6 (Cleanup + Documentation)
- YAML cleanup
- Comprehensive documentation
- Testing and hardening

**Buffer**: Add 2-3 days for unexpected issues, reviews, iterations

**Total Realistic Timeline**: **3 weeks (15 days)**

---

## Risk Mitigation

### Risk 1: Breaking Changes

**Mitigation**:
- Keep old commands alongside new ones during transition
- Mark old commands as `@deprecated`
- Add compatibility layer
- Phased rollout

### Risk 2: Agent Confusion During Migration

**Mitigation**:
- Clear documentation BEFORE starting migration
- Update CLAUDE.md with new patterns
- Add warnings to old command files
- Test with agents frequently

### Risk 3: Missing Use Cases

**Mitigation**:
- Audit all current command usage
- Review agent logs for command invocations
- User feedback session before Phase 4
- Beta testing with select users

### Risk 4: Performance Regression

**Mitigation**:
- Benchmark current command performance
- Optimize query patterns in unified commands
- Add caching where appropriate
- Load testing before production

### Risk 5: Incomplete YAML Migration

**Mitigation**:
- Thorough audit in Phase 5.1
- Don't delete YAML, archive instead
- Keep fallback logic initially
- Remove fallback after 2-week stability period

---

## Success Criteria

### Phase 1-2: Foundation & Consolidation
- [ ] All commands in unified namespace
- [ ] No breaking changes to existing functionality
- [ ] CommandRegistry updated and working
- [ ] All tests passing

### Phase 3: MCP Enhancement
- [ ] All orchestration commands exposed via MCP
- [ ] Clean syntax working (`sprint_list`, not `orchestration:sprints`)
- [ ] Agents can successfully invoke commands
- [ ] Response schemas validated

### Phase 4: CLI Enhancement
- [ ] Write operations (save, assign, update status) working
- [ ] Filters working in all contexts
- [ ] CLI commands are thin wrappers
- [ ] No duplicate logic

### Phase 5: YAML Cleanup
- [ ] All YAML commands archived
- [ ] No fallback to YAML system
- [ ] Documentation updated
- [ ] No references to YAML in codebase

### Phase 6: Documentation
- [ ] Comprehensive development guide published
- [ ] Agent guidelines clear and explicit
- [ ] All commands have tests
- [ ] Zero ambiguity about which system to use

### Overall Success
- [ ] Single source of truth for command logic
- [ ] Clear separation of concerns (command logic vs interface)
- [ ] Agents can easily invoke commands via MCP
- [ ] Users have access to advanced features
- [ ] Maintenance burden reduced (no duplication)
- [ ] Zero confusion about command systems

---

## Rollback Plan

If migration goes wrong:

### Emergency Rollback (< 1 hour)

```bash
# Revert to previous commit
git revert HEAD --no-edit
composer dump-autoload
npm run build
php artisan config:clear
php artisan cache:clear
```

### Partial Rollback

- Keep new namespace structure
- Revert CommandRegistry changes
- Revert MCP changes
- Keep documentation updates

### Phase-by-Phase Rollback

Each phase is self-contained, can rollback individual phases without affecting others.

---

## Post-Migration Monitoring

### Week 1 After Migration

- [ ] Monitor error logs for command failures
- [ ] Track agent command invocations
- [ ] Gather user feedback
- [ ] Fix critical issues immediately

### Week 2-4 After Migration

- [ ] Analyze command usage patterns
- [ ] Optimize slow commands
- [ ] Gather feature requests
- [ ] Plan Phase 2 enhancements (UI filters, etc.)

### Metrics to Track

- Command invocation count (by command, by context)
- Command error rate
- Command execution time
- Agent success rate (MCP invocations)
- User feedback scores

---

## Open Questions for Clarification

Before starting migration, please clarify:

### 1. Namespace Preference

Which namespace structure do you prefer?

- **Option A**: Subnamespaces (`App\Commands\Orchestration\Sprint\ListCommand`)
- **Option B**: Flat with prefixes (`App\Commands\OrchestrationSprintListCommand`)
- **Option C**: Domain-driven (`App\Orchestration\Commands\SprintListCommand`)

### 2. MCP Syntax Preference

Which MCP tool naming do you prefer?

- **Option A**: Snake case (`sprint_list`, `task_detail`)
- **Option B**: Dot notation (`sprint.list`, `task.detail`)
- **Option C**: Slash (`sprint/list`, `task/detail`)

### 3. Priority Trade-offs

If timeline needs to be compressed, which can be deferred?

- UI filters (Phase 4.3)
- Comprehensive tests (Phase 6.4)
- Some write operations (Phase 4.1)
- Advanced MCP schemas (Phase 3.2)

### 4. Breaking Change Tolerance

Can we make breaking changes to agent interfaces?

- **Yes**: Faster migration, cleaner result
- **No**: Slower migration, maintain backwards compat

### 5. UI Enhancement Scope

Should we enhance user command UI in this migration?

- **Yes**: Add filters, better modals (more time)
- **No**: Just backend unification (less time)
- **Later**: Do backend now, UI separately

---

## Next Steps

1. **Review this plan** - Provide feedback, ask questions
2. **Answer open questions** - Strategic decisions needed
3. **Approve scope** - Full migration or phased approach?
4. **Set timeline** - When to start, what's the deadline?
5. **Begin Phase 1** - Start with foundation once approved

---

**Status**: DRAFT - Awaiting approval to proceed
