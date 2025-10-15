# FE Types — Codegen, Sushi Adapters, Validation & Schema Versioning

This pack adds:
- `types:codegen` artisan command to generate Models/Migrations/Requests/Policies/Resources from a Type schema.
- Sushi-based adapters (API/static) examples.
- Validation layer via generated FormRequests.
- Schema diff + dry-run migration command `types:migrate`.

## Install
1. Copy into your Laravel app.
2. Register commands in `app/Console/Kernel.php`:
   ```php
   protected $commands = [
       \App\Console\Commands\TypesCodegen::class,
       \App\Console\Commands\TypesMigrate::class,
   ];
   ```
3. Run (demo only):
   ```bash
   php artisan types:codegen Invoice --force
   php artisan types:migrate Invoice --dry-run
   ```

## Notes
- Codegen reads from `fe_types` tables (from the previous pack).
- Stubs live under `resources/stubs/types/*.stub` for easy customization.
- Sushi adapters show how to wrap API/static data with Laravel models.
