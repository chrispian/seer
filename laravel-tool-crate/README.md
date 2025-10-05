# Laravel Tool Crate

**hollis-labs/laravel-tool-crate** — an opinionated, local-first MCP server for Laravel (built on **official `laravel/mcp`**), with:
- a context-lean **help** layer (`help.index`, `help.tool`) to reduce agent prompt bloat,
- a minimal set of **developer tools** (jq JSON query, grep-like search, safe file read, text replace preview),
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

All names/descriptions/schemas are intentionally terse to keep discovery/context lean.

## CLI examples
```bash
php artisan tool:jq '.packages[] | {name,version}' --file=composer.lock
php artisan tool:search 'Route::' --paths=app --paths=routes --ignore
```

## Config
See `config/tool-crate.php` to enable tools, set priorities, and group categories.

## Notes
- Requires PHP 8.2+, Laravel 10/11/12, `jq` for `json.query`.
- Local-only stdio via `Mcp::local` works well with Fragments and other MCP clients.
