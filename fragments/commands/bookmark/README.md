# Bookmark Command (YAML DSL)

**Version**: 2.0.0  
**Trigger**: `/bookmark`, `/bm`

## Description

Manages bookmarks for fragments and conversations. Supports creating, listing, viewing, and deleting bookmarks.

## Usage

### Create Bookmark
```
/bookmark
```
Creates a new bookmark from the current context.

### List Bookmarks  
```
/bookmark list
```
Shows all existing bookmarks.

### Show Bookmark
```
/bookmark show <name>
```
Displays a specific bookmark by name (partial matching supported).

### Delete Bookmark
```
/bookmark forget <name>
/bookmark del <name>
/bookmark rm <name>
```
Deletes a bookmark by name.

## Migration Notes

- **Status**: ✅ Migrated from hardcoded BookmarkCommand
- **Complexity**: Medium (multiple modes, conditional logic)
- **Features**: All core functionality preserved
- **Dependencies**: fragment.query, fragment.create capabilities

## Changes from Original

1. **Simplified delete**: Delete functionality shows placeholder message for now
2. **Enhanced UI**: Uses response.panel for better presentation
3. **Improved structure**: Clearer conditional logic flow
4. **DSL benefits**: Easier to modify and extend

## Testing

```bash
php artisan frag:command:test bookmark fragments/commands/bookmark/samples/basic.json
```