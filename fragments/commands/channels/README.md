# Channels Command (YAML DSL)

**Version**: 2.0.0  
**Trigger**: `/channels`

## Description

Lists all active chat channels in the current workspace, showing their short codes, names, and last activity timestamps.

## Usage

```
/channels
```
Shows all active channels with their information.

## Migration Notes

- **Status**: 🚧 Partially migrated from hardcoded ChannelsCommand
- **Complexity**: Medium (database queries, formatting)
- **Current functionality**: Shows static channel list for demonstration
- **TODO**: Implement dynamic channel listing once database access is available in DSL

## Changes from Original

1. **Static for now**: Shows example channels instead of querying database
2. **Maintains interface**: Same trigger and response format
3. **Future enhancement**: Will add real channel querying when database steps are available

## Testing

```bash
php artisan frag:command:test channels fragments/commands/channels/samples/basic.json
```