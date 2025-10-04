# Session Command (YAML DSL)

**Version**: 2.0.0  
**Trigger**: `/session`

## Description

Manages chat sessions including viewing current session status, listing sessions, starting new sessions, and ending sessions.

## Usage

### Show Current Session
```
/session show
```
Displays current session information.

### List Sessions
```
/session list
```
Shows recent chat sessions.

### Start New Session
```
/session start
```
Begins a new chat session.

### End Session
```
/session end
```
Ends the current session.

## Migration Notes

- **Status**: 🚧 Partially migrated from hardcoded SessionCommand
- **Complexity**: High (multiple actions, session management)
- **Current functionality**: Shows session information and available commands
- **TODO**: Implement full session management when session APIs are available in DSL

## Changes from Original

1. **Simplified for now**: Shows information instead of actual session operations
2. **Maintains interface**: Same trigger and response format
3. **Future enhancement**: Will add full session lifecycle management

## Testing

```bash
php artisan frag:command:test session fragments/commands/session/samples/basic.json
```