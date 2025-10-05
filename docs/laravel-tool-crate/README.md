# Laravel Tool Crate

**hollis-labs/laravel-tool-crate** — an opinionated, local-first MCP server for Laravel (built on **official `laravel/mcp`**), with:
- a context-lean **help** layer (`help.index`, `help.tool`),
- **developer tools**: `json.query`, `text.search`, `file.read`, `text.replace`,
- **git tools**: `git.status`, `git.diff`, `git.apply_patch` (uses `gh` when possible),
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
See `config/tool-crate.php` to enable tools, set priorities, and group categories.

## Requirements
- PHP 8.2+, Laravel 10/11/12
- `jq` for `json.query`
- `git` (and optionally `gh`) for `git.*`
- SQLite PDO extension for `table.query`
