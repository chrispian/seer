# Join Command (YAML DSL)

**Version**: 2.0.0  
**Trigger**: `/join`, `/j`

## Description

Handles channel navigation and joining. Supports direct channel access, search, and autocomplete functionality.

## Usage

### Direct Channel Join
```
/join #c5
```
Switches directly to channel #c5.

### Search Channels
```
/join project
```
Searches for channels containing "project".

### List All Channels
```
/join #
```
Shows all available channels.

## Migration Notes

- **Status**: 🚧 Partially migrated from hardcoded JoinCommand
- **Complexity**: High (channel switching, search, autocomplete)
- **Current functionality**: Shows help and usage information
- **TODO**: Implement channel switching logic in DSL

## Changes from Original

1. **Simplified for now**: Currently shows help/usage instead of actual switching
2. **Maintains interface**: Same triggers and input patterns
3. **Future enhancement**: Will add channel switching once navigation APIs are available in DSL

## Testing

```bash
php artisan frag:command:test join fragments/commands/join/samples/basic.json
```