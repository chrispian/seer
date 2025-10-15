# FE UI Registry + Feature Flags Pack

This pack adds:
- **fe_ui_registry**: a code registry for Pages, Components (incl. Layouts), Modules, and Themes.
- **fe_ui_feature_flags**: targeted experiments/rollouts (e.g., `ui.halloween_haunt` @ 5%).
- **`kind` on fe_ui_components**: `primitive|composite|pattern|layout`.

Includes Laravel migrations, Eloquent models, DTOs, seeders, and a FeatureFlagService with % rollout + conditions.

## Install
1. Copy files into your Laravel app.
2. Run migrations:
   ```bash
   php artisan migrate
   ```
3. Seed sample data:
   ```bash
   php artisan db:seed --class=UiRegistrySeeder
   ```

## Tables
- `fe_ui_registry` — registry of publishable UI artifacts.
- `fe_ui_feature_flags` — feature flags with conditions.
- Alters `fe_ui_components` to add `kind` enum.

See `docs/ADR_v2_Layouts_as_Components.md` for rationale.
