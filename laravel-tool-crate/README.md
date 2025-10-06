# Laravel Tool Crate

**hollis-labs/laravel-tool-crate** — an opinionated, local-first MCP server for Laravel (built on **official `laravel/mcp`**), with:
- a context-lean **help** layer (`help.index`, `help.tool`) to reduce agent prompt bloat,
- a minimal set of **developer tools** (jq JSON query, grep-like search, safe file read, text replace preview),
- **orchestration summaries** for agents/sprints/tasks (powered by your Eloquent models),
- **CLI commands** mirroring the MCP tools.

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

This registers a **local** MCP server in `routes/ai.php` automatically (via package routes):
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
- `orchestration.agents.list` → summarize agents with filters (status/type/mode/search)
- `orchestration.agents.detail` → agent profile snapshot with stats and assignments
- `orchestration.agents.save` → create/update agent metadata and capabilities
- `orchestration.agents.status` → toggle agent state (active/inactive/archived)
- `orchestration.tasks.list` → list work items with delegation metadata filters
- `orchestration.tasks.detail` → detailed view (history + assignments)
- `orchestration.tasks.assign` → create assignment + update delegation status
- `orchestration.tasks.status` → change delegation status & sync current assignment
- `orchestration.sprints.list` → sprint progress stats with optional recent tasks
- `orchestration.sprints.detail` → full sprint snapshot with stats and tasks
- `orchestration.sprints.save` → create/update sprint metadata and cadence
- `orchestration.sprints.status` → set sprint status and append notes
- `orchestration.sprints.attach_tasks` → associate work items with a sprint

All names/descriptions/schemas are intentionally terse to keep discovery/context lean.

## CLI examples
```bash
php artisan tool:jq '.packages[] | {name,version}' --file=composer.lock
php artisan tool:search 'Route::' --paths=app --paths=routes --ignore
```

### Orchestration (v0.2.x+)
The orchestration tools default to the Fragments Engine schema. Override the models via config:

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

## Config
See `config/tool-crate.php` to enable tools, tune priorities, and group categories.

## Notes
- Requires PHP 8.2+, Laravel 10/11/12, `jq` for `json.query`.
- Local-only stdio via `Mcp::local` works well with Fragments and other MCP clients.
