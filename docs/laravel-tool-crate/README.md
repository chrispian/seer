# Laravel Tool Crate

> ⚠️ **Status: _Not Ready for Use_** — active development preview. Expect breaking changes, incomplete docs, and missing test coverage. Please do **not** depend on this package in production yet.

**hollis-labs/laravel-tool-crate** — an opinionated, local-first MCP server for Laravel (built on **official `laravel/mcp`**), with:
- a context-lean **help** layer (`help.index`, `help.tool`),
- **developer tools**: `json.query`, `text.search`, `file.read`, `text.replace`,
- **orchestration summaries** (v0.2+): `orchestration.agents.list`, `orchestration.tasks.list`, `orchestration.sprints.list`,
- **git tools** _(in-flight)_: `git.status`, `git.diff`, `git.apply_patch` (prefers `gh` when available),
- **data tool**: `table.query` (SQLite over CSV/TSV),
- **CLI commands** mirroring the jq/search tools.

## Install (path repo during development)
```json
{
  "repositories": [
    { "type": "path", "url": "packages/laravel-tool-crate", "options": { "symlink": true } }
  ]
}
```
```bash
composer require hollis-labs/laravel-tool-crate:* --dev
php artisan vendor:publish --tag=laravel-tool-crate-config
```

This registers a **local** MCP server in `routes/ai.php` automatically:
```php
use Laravel\Mcp\Facades\Mcp;
use HollisLabs\ToolCrate\Servers\ToolCrateServer;

Mcp::local('tool-crate', ToolCrateServer::class);
```

## Tools
- `help.index` → prioritized + categorized list with follow-up hints
- `help.tool` → details for a named tool (schema summary + hint)
- `json.query` → jq wrapper
- `text.search` → grep-like search (files or inline text)
- `file.read` → safe read with cap & slice
- `text.replace` → preview-only replacement with unified diff
- `orchestration.agents.list` → agent directory filters by status/type/mode/search
- `orchestration.agents.detail` → agent snapshot (stats + recent assignments)
- `orchestration.agents.save` → create/update agent metadata
- `orchestration.agents.status` → set agent status (active/inactive/archived)
- `orchestration.tasks.list` → work item feed with delegation metadata filters
- `orchestration.tasks.detail` → delegation history + assignments for a work item
- `orchestration.tasks.assign` → create assignment tying a work item to an agent
- `orchestration.tasks.status` → update delegation status and active assignment
- `orchestration.sprints.list` → sprint progress stats + optional recent tasks
- `orchestration.sprints.detail` → sprint snapshot with stats + tasks
- `orchestration.sprints.save` → create/update sprint metadata
- `orchestration.sprints.status` → set sprint status + append notes
- `orchestration.sprints.attach_tasks` → attach work items to a sprint
- `git.status` → porcelain v2 (`-z`); reports whether `gh` is present
- `git.diff` → `git diff` by range/staged/paths or `gh pr diff {#}` if available
- `git.apply_patch` → safe by default (`--check`); enable apply with `check_only=false`
- `table.query` → load CSV/TSV to **SQLite (in-memory)** and run a `SELECT`

All names/descriptions/schemas are intentionally terse to keep discovery/context lean.

## CLI examples
```bash
php artisan tool:jq '.packages[] | {name,version}' --file=composer.lock
php artisan tool:search 'Route::' --paths=app --paths=routes --ignore
```

## Config
See `config/tool-crate.php` to enable tools, set priorities, and group categories. Override orchestration bindings when your models live outside the Fragments defaults:

```php
return [
    'orchestration' => [
        'agent_model' => App\Models\AgentProfile::class,
        'sprint_model' => App\Models\Sprint::class,
        'work_item_model' => App\Models\WorkItem::class,
        'task_service' => App\Services\TaskOrchestrationService::class,
    ],
];
```

## Requirements
- PHP 8.2+, Laravel 10/11/12
- `jq` for `json.query`
- `git` (and optionally `gh`) for `git.*`
- SQLite PDO extension for `table.query`
