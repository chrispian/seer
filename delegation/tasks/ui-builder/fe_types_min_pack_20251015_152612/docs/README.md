# FE Types — Minimal Starter Pack

This pack adds a **config-first, strongly-typed** system with optional materialization later.

## Includes
- Migrations: `fe_types`, `fe_type_fields`, `fe_type_relations`
- Models: `FeType`, `FeTypeField`, `FeTypeRelation`
- Services: `TypeRegistry`, `TypeResolver`
- Controller: `TypesController` with `/api/v2/types/{alias}/query` and `/api/v2/types/{alias}/{id}`
- DTOs: `TypeSchema`, `TypeField`, `TypeRelation`
- Seeder: `TypesDemoSeeder`
- Routes stub: `routes/types.php`
- Docs: quick usage

## Install
1. Copy files into your Laravel app.
2. Register routes in `RouteServiceProvider` or include in `routes/api.php`:
   ```php
   require base_path('routes/types.php');
   ```
3. Run migrations & seed:
   ```bash
   php artisan migrate
   php artisan db:seed --class=TypesDemoSeeder
   ```

## Quick Start
- Visit: `GET /api/v2/types/Invoice/query` (after seeding) to see demo results.  
- The resolver uses demo in-memory data; replace with real sources (DB, Sushi, API).

## Notes
- This pack focuses on schema config & an adapter-friendly resolver.
- For materialization/codegen, add a separate artisan command (future).
