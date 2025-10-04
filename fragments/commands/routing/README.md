# Routing Command (YAML DSL)

**Version**: 2.0.0  
**Trigger**: `/routing`

## Description

Manages routing rules for automatic content organization and routing between vaults and projects.

## Usage

```
/routing
```
Opens the routing management interface.

## Migration Notes

- **Status**: 🚧 Partially migrated from hardcoded RoutingCommand
- **Complexity**: High (complex business logic, database operations)
- **Current functionality**: Shows information and placeholder interface
- **TODO**: Implement full routing management when database and service capabilities are available in DSL

## Changes from Original

1. **Simplified for now**: Shows information instead of full management interface
2. **Maintains interface**: Same trigger and response format
3. **Future enhancement**: Will add full routing rule management capabilities

## Testing

```bash
php artisan frag:command:test routing fragments/commands/routing/samples/basic.json
```