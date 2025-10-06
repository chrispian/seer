# Fragments Engine – Laravel Code Pack (Tools, Memory, Context, PM)
**Date:** 2025_10_04

This pack provides Laravel stubs for:
- Tool contracts + registry + scopes
- Internal API endpoints (db.query, export, memory)
- Services for DB.Query + Prompt Orchestrator (stub)
- Agent Memory + Artifacts + Saved Queries + Prompt Registry
- Work Items + Sprints + Events (PM core)
- Safe shells/filesystem/repo placeholders (interfaces + config)

> Copy these files into your Laravel app at matching paths. Then run migrations.

## Install
1. Copy to your project root (respecting `app/`, `config/`, `routes/`, `database/`, `resources/`).
2. Register `App\Providers\ToolServiceProvider::class` in `config/app.php` (providers).
3. Ensure the internal routes are loaded. Add in `RouteServiceProvider@boot`:
   ```php
   Route::middleware('api')->prefix('api/internal')->group(base_path('routes/internal.php'));
   ```
4. Run migrations: `php artisan migrate`.
5. Optional: Tune `config/tools.php` scopes/allowlists.

## Notes
- Models use `$guarded = [];` and timestamps are placed last in migrations.
- Indexing is minimal; add FTS or GIN as desired per DB vendor.
