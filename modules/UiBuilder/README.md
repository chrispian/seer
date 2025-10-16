# UI Builder Module

Database-driven UI configuration system for building dynamic interfaces without code changes.

## Structure

```
UiBuilder/
├── app/
│   ├── Http/Controllers/
│   │   └── DataSourceController.php    # Unified data source API
│   ├── Models/
│   │   ├── Page.php                    # Page configurations
│   │   ├── Component.php               # Component definitions
│   │   ├── Datasource.php              # Data source configurations
│   │   ├── Action.php                  # Action definitions
│   │   ├── Registry.php                # Component registry
│   │   ├── Module.php                  # Module grouping
│   │   ├── Theme.php                   # Theme configurations
│   │   └── FeatureFlag.php             # Feature flags
│   └── Services/
│       └── DataSourceResolver.php      # Generic data source resolver
├── database/
│   ├── migrations/                     # Consolidated migrations
│   └── seeders/                        # Database seeders
├── routes/
│   └── api.php                         # API routes
├── config/
│   └── ui-builder.php                  # Module configuration
└── UiBuilderServiceProvider.php        # Service provider

```

## Models

All models use clean names without prefixes:

- `Page` - UI page configurations (table: `ui_pages`)
- `Component` - Reusable component definitions (table: `ui_components`)
- `Datasource` - Model-to-API mappings (table: `ui_datasources`)
- `Action` - Action handlers (table: `ui_actions`)
- `Registry` - Component type registry (table: `ui_registry`)
- `Module` - Module grouping (table: `ui_modules`)
- `Theme` - Theme configurations (table: `ui_themes`)
- `FeatureFlag` - Feature toggles (table: `ui_feature_flags`)

## Services

- `DataSourceResolver` - Handles all data source queries with filtering, sorting, pagination

## Controllers

- `DataSourceController` - Unified API endpoint for all data sources

## Backward Compatibility

The service provider maintains backward compatibility by binding old `FeUi*` class names to new clean names.
