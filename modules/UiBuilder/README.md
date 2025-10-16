# UI Builder Module

This module contains all UI builder functionality for the v2 system.

## Structure

```
ui-builder/
├── Models/           # Eloquent models (FeUiPage, FeUiComponent, etc.)
├── Controllers/      # HTTP controllers for UI endpoints
├── Services/         # Business logic (DataSourceResolvers, etc.)
├── Seeders/          # Database seeders for UI components
├── migrations/       # Database migrations
├── routes/           # Module-specific routes
└── config/           # Module configuration
```

## Models

- `FeUiPage` - Page configurations
- `FeUiComponent` - Component definitions
- `FeUiDatasource` - Data source configurations
- `FeUiAction` - Action definitions
- `FeUiRegistry` - Component registry
- `FeUiModule` - Module definitions
- `FeUiTheme` - Theme configurations
- `FeUiFeatureFlag` - Feature flags

## Services

- `GenericDataSourceResolver` - Main data source resolver
- Legacy resolvers (to be removed):
  - `AgentDataSourceResolver`
  - `ModelDataSourceResolver`