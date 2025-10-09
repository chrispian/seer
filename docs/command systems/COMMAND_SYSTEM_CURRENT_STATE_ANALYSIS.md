# Command System Current State Analysis

**Date**: 2025-10-09  
**Status**: Mixed - Two separate command systems causing confusion  
**Priority**: HIGH - Needs consolidation to prevent further regressions

---

## Executive Summary

Fragments Engine currently has **two separate command systems** that serve different purposes but have overlapping functionality, causing confusion for developers and AI agents:

1. **User Commands** (`/slash-commands` in web UI via TipTap composer)
2. **Agent Commands** (CLI `artisan orchestration:*` commands for AI agents/MCP)

Both systems have been migrated from YAML to PHP, but they exist in different namespaces with different invocation patterns. Many commands are duplicated across both systems. The separation is artificial and creates maintenance burden.

### Key Problems

- **Confusion**: Agents and developers unsure which command system to use
- **Duplication**: Same functionality exists in both systems (sprints, tasks, agents)
- **Inconsistent Syntax**: User commands use `/sprints`, agent commands use `orchestration:sprints`
- **Limited Exposure**: Many useful CLI commands would benefit users but aren't exposed in UI
- **Maintenance Burden**: Changes must be duplicated across both systems

---

## System 1: User Commands (`/slash-commands`)

**Purpose**: Interactive commands in chat composer (web UI)  
**Location**: `app/Commands/`  
**Registry**: `app/Services/CommandRegistry.php`  
**Invocation**: `/command-name [args]` via TipTap composer  
**Migration Status**: ✅ Fully migrated from YAML to PHP

### Architecture

```
User types /search foo
    ↓
CommandController::execute() 
    ↓
CommandRegistry::find('search')
    ↓
SearchCommand::handle()
    ↓
Returns: ['type' => 'fragment', 'component' => 'FragmentListModal', 'data' => [...]]
    ↓
Frontend CommandResultModal routes to React component
```

### Current Commands (32 unique)

#### Orchestration (5 commands)
- `/sprints` → `SprintListCommand`
- `/sprint-detail <code>` → `SprintDetailCommand`
- `/tasks` → `TaskListCommand`
- `/task-detail <code>` → `TaskDetailCommand`
- `/backlog` → `BacklogListCommand`
- `/agents` → `AgentListCommand`

#### Navigation (4 commands)
- `/search <query>` → `SearchCommand`
- `/recall <query>` → `RecallCommand`
- `/inbox` → `InboxCommand`
- `/frag <id>` → `FragCommand`

#### Utility (8 commands)
- `/help` → `HelpCommand`
- `/clear` → `ClearCommand`
- `/channels` → `ChannelsCommand`
- `/routing` → `RoutingCommand`
- `/types` → `TypeManagementCommand`
- `/context` → `ContextCommand`
- `/compose` → `ComposeCommand`
- `/setup` → `SetupCommand`

#### Content (6 commands)
- `/todo` → `TodoCommand`
- `/note` → `NoteCommand`
- `/notes` → `NoteListCommand`
- `/bookmark` → `BookmarkListCommand`
- `/join` → `JoinCommand`
- `/link` → `LinkCommand`

#### Other (9 commands)
- `/vault` → `VaultListCommand`
- `/project` → `ProjectListCommand`
- `/session` → `SessionListCommand`
- `/schedule-list` → `ScheduleListCommand`
- `/accept` → `AcceptCommand`
- `/remind` → `RemindCommand`
- `/news-digest` → `NewsDigestCommand`
- `/name` → `NameCommand`
- `/frag-simple` → `FragSimpleCommand`

### Characteristics

- ✅ Returns data + React component for rendering
- ✅ Optimized for interactive UI usage
- ✅ Human-friendly output (modals, lists, detail views)
- ✅ Alias support (`/s` → `/search`, `/t` → `/todo`)
- ✅ Clean namespace: `App\Commands\*`
- ❌ Not exposed to agents/MCP (only web UI)

### Example Command Structure

```php
<?php

namespace App\Commands;

class SearchCommand extends BaseCommand
{
    protected ?string $query = null;

    public function __construct(?string $argument = null)
    {
        $this->query = $argument;
    }

    public function handle(): array
    {
        $results = $this->getSearchResults();
        
        return [
            'type' => 'fragment',
            'component' => 'FragmentListModal',
            'data' => $results,
        ];
    }

    public static function getName(): string
    {
        return 'Search';
    }

    public static function getDescription(): string
    {
        return 'Search through fragments and content';
    }

    public static function getUsage(): string
    {
        return '/search [query]';
    }

    public static function getCategory(): string
    {
        return 'Navigation';
    }
}
```

---

## System 2: Agent Commands (Orchestration CLI)

**Purpose**: CLI commands for AI agents and automation  
**Location**: `app/Console/Commands/Orchestration*.php`  
**Invocation**: `php artisan orchestration:command-name`  
**Exposed via**: MCP server (`orchestration:mcp`)  
**Migration Status**: ✅ PHP-based (never YAML)

### Architecture

```
Agent/CLI invokes
    ↓
php artisan orchestration:tasks --sprint SPRINT-67
    ↓
OrchestrationTasksCommand::handle()
    ↓
Query WorkItem model with filters
    ↓
Output table/JSON to stdout
```

### Current Commands (14)

#### Core Operations
- `orchestration:sprints` - List sprints with stats
- `orchestration:sprint:detail` - Sprint details + tasks
- `orchestration:sprint:save` - Create/update sprint
- `orchestration:sprint:status` - Update sprint status
- `orchestration:sprint:tasks:attach` - Attach tasks to sprint

- `orchestration:tasks` - List work items with filters
- `orchestration:task:detail` - Task detail + activity
- `orchestration:task:status` - Update task status
- `orchestration:task:assign` - Assign task to agent

- `orchestration:agents` - List agent profiles
- `orchestration:agent:detail` - Agent details + assignments
- `orchestration:agent:save` - Create/update agent
- `orchestration:agent:status` - Update agent status

#### Import/Migration
- `orchestration:import-delegation` - Import delegation markdown
- `delegation:import` - Import delegation sprint/task metadata

### Characteristics

- ✅ Sophisticated filtering (--sprint, --status, --delegation-status, etc.)
- ✅ Multiple output formats (table, JSON via --json)
- ✅ Optimized for scripting/automation
- ✅ Direct database queries (WorkItem, Sprint, AgentProfile models)
- ✅ Exposed via MCP server for agent access
- ❌ Not user-friendly for UI (CLI-only)
- ❌ Verbose invocation (`php artisan orchestration:task:detail TASK-CODE`)
- ❌ Inconsistent with user command syntax

### MCP Exposure

Agents access these commands via MCP server:
- **MCP Server**: `orchestration:mcp` command
- **Exposed as**: MCP tools/resources
- **Configuration**: `.mcp.json` → `orch` server
- **Tools**: Limited (only 3 in `app/Mcp/Tools/`)
  - `TaskContentUpdateTool`
  - `TaskActivitiesListTool`
  - `TaskActivitiesLogTool`

### Example Command Structure

```php
<?php

namespace App\Console\Commands;

class OrchestrationTasksCommand extends Command
{
    protected $signature = 'orchestration:tasks
        {--sprint=* : Filter by sprint codes or numbers}
        {--delegation-status=* : Filter by delegation status}
        {--status=* : Filter by work item status}
        {--agent= : Filter by recommended agent}
        {--search= : Match task code or description}
        {--limit=20 : Maximum number of tasks}
        {--json : Output JSON}';

    protected $description = 'List orchestration work items with delegation metadata.';

    public function handle(): int
    {
        // Complex query building with filters
        $tasks = WorkItem::query()
            ->whereNotNull('metadata->task_code')
            ->when($sprints, fn($q) => $q->whereIn('metadata->sprint_code', $sprints))
            ->limit($limit)
            ->get();

        // Output table or JSON
        if ($this->option('json')) {
            $this->line(json_encode(['data' => $tasks], JSON_PRETTY_PRINT));
        } else {
            $this->table(['Task', 'Sprint', 'Status', ...], $tasks);
        }

        return self::SUCCESS;
    }
}
```

---

## System 3: Legacy YAML Commands (Deprecated)

**Status**: ⚠️ Deprecated but still present  
**Location**: `fragments/commands/*/command.yaml`  
**Purpose**: Original DSL-based command system (replaced by PHP)

### Overview

The YAML command system was the original implementation using a DSL (Domain Specific Language) approach. It proved fragile and painful, leading to the PHP migration.

### Current State

- ❌ No longer actively used
- ⚠️ Files still exist in `fragments/commands/` (52 command directories)
- ⚠️ Some YAML files may be referenced as fallback
- ⚠️ Causes confusion during development
- ✅ User commands have been migrated to PHP
- ❓ Unclear if safe to delete

### Example Commands Still Present

```
fragments/commands/
├── accept/command.yaml
├── agent-profiles/command.yaml
├── backlog-list/command.yaml
├── bookmark/command.yaml
├── channels/command.yaml
├── sprints/command.yaml
├── tasks/command.yaml
├── search/command.yaml
└── ... (52 total)
```

### Risks

- **Agent Confusion**: Agents may reference YAML files during refactoring
- **Maintenance Debt**: Outdated documentation in YAML READMEs
- **Version Skew**: PHP and YAML versions may have different behavior

---

## Duplication Analysis

### Commands That Exist in BOTH Systems

| Functionality | User Command | Agent Command | Notes |
|--------------|--------------|---------------|-------|
| List sprints | `/sprints` | `orchestration:sprints` | Different output formats |
| Sprint detail | `/sprint-detail <code>` | `orchestration:sprint:detail <code>` | UI modal vs CLI table |
| List tasks | `/tasks` | `orchestration:tasks` | No filters in UI version |
| Task detail | `/task-detail <code>` | `orchestration:task:detail <code>` | UI modal vs CLI JSON |
| List agents | `/agents` | `orchestration:agents` | Different data structure |
| Backlog | `/backlog` | N/A | Only in user system |

### Commands That Should Be in BOTH Systems

| Functionality | Currently Only In | Should Be Available To |
|--------------|-------------------|------------------------|
| Search fragments | User (`/search`) | Agents (would be useful!) |
| Inbox | User (`/inbox`) | Agents (for task management) |
| Todo management | User (`/todo`) | Agents (for task breakdown) |
| Create sprint | Agent (`orchestration:sprint:save`) | Users (via form/modal) |
| Update task status | Agent (`orchestration:task:status`) | Users (via UI) |
| Assign task | Agent (`orchestration:task:assign`) | Users (via dropdown) |

---

## Problems & Pain Points

### 1. Agent Confusion

**Problem**: AI agents don't know which command system to use

**Evidence**:
- Docs mention "agents are using / commands in the terminal which is overly complex"
- Agents have caused regressions by mixing command systems
- MCP exposure is limited (only 3 tools)

**Impact**: High - leads to broken implementations and rollbacks

### 2. Limited MCP Exposure

**Problem**: Only 3 orchestration tools exposed via MCP

**Current MCP Tools**:
- `TaskContentUpdateTool`
- `TaskActivitiesListTool`
- `TaskActivitiesLogTool`

**Missing from MCP**:
- Sprint listing/detail
- Task listing with filters
- Agent listing
- All user commands (/search, /inbox, /todo, etc.)

**Impact**: Medium - agents have to use complex CLI invocations

### 3. Duplicate Maintenance

**Problem**: Same functionality in two places

**Examples**:
- `SprintListCommand` (User) vs `OrchestrationSprintsCommand` (Agent)
- `TaskListCommand` (User) vs `OrchestrationTasksCommand` (Agent)
- `AgentListCommand` (User) vs `OrchestrationAgentsCommand` (Agent)

**Impact**: High - changes must be made twice, introduces drift

### 4. Inconsistent Capabilities

**Problem**: User commands are simplified, agent commands have more features

**Examples**:
- User `/tasks` has no filters
- Agent `orchestration:tasks` has --sprint, --status, --delegation-status, --agent, --search, --limit
- User commands return UI components
- Agent commands return JSON/tables

**Impact**: Medium - users can't access full power of system

### 5. YAML Legacy Files

**Problem**: Old YAML files still exist, causing confusion

**Evidence**:
- 52 command directories in `fragments/commands/`
- Each has `command.yaml`, README.md, samples/
- May be referenced as fallback

**Impact**: Low-Medium - confuses agents during refactoring

### 6. Complex CLI Invocation

**Problem**: Agent commands require verbose syntax

**Examples**:
- `php artisan orchestration:sprint:detail SPRINT-67`
- `php artisan orchestration:task:assign T-TASK-01 agent-slug`

**Desired**:
- `sprint.detail SPRINT-67` or `sprint_detail SPRINT-67`
- `task.assign T-TASK-01 agent-slug`

**Impact**: Medium - harder for agents to invoke correctly

---

## Namespace Analysis

### Current Namespaces

| System | Namespace | Classes | Pattern |
|--------|-----------|---------|---------|
| User Commands | `App\Commands\` | 32 | `*Command.php` |
| Agent Commands | `App\Console\Commands\` | 14 orchestration | `Orchestration*.php` |
| Legacy Console | `App\Console\Commands\` | 36 other | Various |
| MCP Tools | `App\Mcp\Tools\` | 3 | `*Tool.php` |

### Namespace Issues

1. **User commands** are in `App\Commands\` (clean)
2. **Agent commands** are in `App\Console\Commands\` (mixed with other console commands)
3. **MCP tools** are in `App\Mcp\Tools\` (separate, small)
4. **No clear distinction** between orchestration and other console commands

---

## Frontend Integration

### User Commands

**Integration Point**: `resources/js/islands/chat/CommandResultModal.tsx`

```typescript
switch (currentResult.component) {
  case 'SprintListModal':
    return <SprintListModal isOpen={isOpen} onClose={onClose} data={currentResult.data} />
  case 'TaskListModal':
    return <TaskListModal isOpen={isOpen} onClose={onClose} data={currentResult.data} />
  // ... 15+ component cases
}
```

**Available Components**:
- `SprintListModal`, `SprintDetailModal`
- `TaskListModal`, `TaskDetailModal`
- `AgentProfileListModal`
- `BacklogListModal`
- `FragmentListModal`
- `TodoManagementModal`
- `TypeManagementModal`
- `ChannelListModal`
- `RoutingInfoModal`

**Pattern**: Commands return `['component' => 'ModalName', 'data' => [...]]`

### Agent Commands

**Integration**: None - CLI only

**Output**:
- Table format (default)
- JSON format (with `--json` flag)
- Stdout/stderr

**Pattern**: Queries models, outputs text/JSON

---

## Questions to Resolve

Before creating the migration plan, need clarification on:

### 1. Target Namespace Strategy

**Option A: Unified `App\Commands\`**
- All commands in one place
- Subnamespaces for organization: `App\Commands\Orchestration\`, `App\Commands\Content\`, etc.
- Single registry

**Option B: Purpose-Based Namespaces**
- `App\Commands\User\` - User-facing commands
- `App\Commands\Agent\` - Agent-facing commands
- Shared base classes/interfaces

**Option C: Current + Bridge**
- Keep both namespaces
- Add adapter layer for MCP

**Your preference?**

### 2. MCP Exposure Strategy

**Option A: Expose All User Commands**
- Agents can use everything users can use
- Simple exposure via MCP

**Option B: Expose Subset**
- Only expose orchestration + selected commands
- Keep some commands user-only

**Option C: Expose Different Implementations**
- User commands return UI data
- Agent commands return structured data
- Different implementations, same functionality

**Your preference?**

### 3. CLI Invocation Syntax

**Option A: Keep Artisan Pattern**
- `php artisan orchestration:sprints`
- Familiar to Laravel developers

**Option B: Simplified MCP Pattern**
- `sprint.list` or `sprint_list` via MCP
- Shorter, easier for agents

**Option C: Slash Commands in CLI**
- `/sprints` works in CLI too
- Unified syntax

**Your preference?**

### 4. YAML Cleanup

**Option A: Delete All YAML**
- Remove `fragments/commands/` entirely
- Clean slate

**Option B: Archive YAML**
- Move to `fragments/commands-legacy/`
- Keep for reference

**Option C: Selective Cleanup**
- Keep YAML for unmigrated commands only
- Delete migrated ones

**Your preference?**

### 5. Feature Parity

**Option A: Full Parity**
- User commands get all agent command features (filters, etc.)
- Agent commands get all user command capabilities
- May require UI work

**Option B: Separate but Equal**
- User commands stay UI-focused
- Agent commands stay data-focused
- Less UI work, more duplication

**Option C: Asymmetric**
- Agent commands are superset (all features)
- User commands are simplified (UI-friendly subset)
- Users can still access via CLI if needed

**Your preference?**

---

## Migration Complexity Assessment

### Low Complexity (1-2 days)

- ✅ Namespace consolidation
- ✅ MCP exposure of existing commands
- ✅ YAML cleanup
- ✅ Documentation updates

### Medium Complexity (3-5 days)

- ⚠️ Unifying duplicate commands
- ⚠️ Adding filters to user commands
- ⚠️ Creating adapter layer for MCP
- ⚠️ Frontend updates for new features

### High Complexity (1-2 weeks)

- ❌ Full feature parity (UI + backend)
- ❌ Complete system redesign
- ❌ Migration of all YAML logic to PHP
- ❌ Comprehensive testing suite

---

## Recommendations (Preliminary)

Based on analysis, suggest:

1. **Unified Namespace**: Consolidate to `App\Commands\` with subnamespaces
2. **MCP Exposure**: Expose all commands via MCP with simplified syntax
3. **Feature Parity**: Agent commands as superset, gradual UI enhancement
4. **YAML Cleanup**: Delete all migrated YAML, archive unmigrated
5. **Syntax**: Simplified MCP syntax (`sprint.list`), keep artisan for CLI users

But waiting for your input before finalizing plan!

---

## Next Steps

1. **Review this analysis** - Correct any misunderstandings
2. **Answer questions** - Clarify strategic preferences
3. **Prioritize goals** - What's most important?
4. **Create migration plan** - Detailed steps with timeline
5. **Execute migration** - Phased implementation

---

## Additional Context Needed

Please provide any additional information about:

- **Agent usage patterns** - How do agents currently invoke commands?
- **User pain points** - What command features do users request?
- **Performance concerns** - Any issues with current command execution?
- **Future plans** - Any planned features that would impact this?
- **Breaking changes tolerance** - Can we break agent workflows during migration?

---

**Status**: Awaiting feedback to proceed with detailed migration plan
