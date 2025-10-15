# Navigation Config Schema

## Overview

The `navigation_config` JSON column in the `commands` table defines how UI components should handle data mapping, item selection, and drill-down navigation. This config-driven approach eliminates hardcoded type checks in the frontend, allowing new entity types to be added via database configuration alone.

## Schema Definition

```typescript
interface NavigationConfig {
  data_prop?: string         // Property name for the data array (e.g., "sprints", "tasks")
  item_key?: string          // Field used as unique identifier (e.g., "code", "task_code", "id")
  detail_command?: string    // Command to execute when an item is selected (e.g., "/sprint-detail")
  parent_command?: string    // Command to navigate back to parent list view
  children?: Array<{
    type: string            // Entity type name, capitalized (e.g., "Task", "Agent")
    command: string         // Command to execute for child entity (e.g., "/task-detail")
    item_key: string        // Field used as identifier for child entity
  }>
}
```

## Field Descriptions

### `data_prop` (string, optional)
- **Purpose**: Specifies the property name under which the data array is stored
- **Frontend Usage**: `props[data_prop] = result.data[data_prop]`
- **Example**: `"sprints"` → creates `props.sprints = result.data.sprints`
- **Default Fallback**: If missing, falls back to legacy component name detection

### `item_key` (string, optional)
- **Purpose**: Identifies which field uniquely identifies each item in the data array
- **Frontend Usage**: Used to extract item ID when building detail command
- **Example**: `"code"` for sprints, `"task_code"` for tasks, `"id"` for generic entities
- **Command Construction**: `/sprint-detail ${item[item_key]}`

### `detail_command` (string, optional)
- **Purpose**: Command to execute when user clicks/selects an item
- **Frontend Usage**: `onItemSelect` handler
- **Example**: `"/sprint-detail"` → triggers `/sprint-detail SPRINT-01`
- **Note**: Must start with `/` and match an existing command in the database

### `parent_command` (string, optional)
- **Purpose**: Command to navigate back to parent list view
- **Frontend Usage**: Back button navigation in detail modals
- **Example**: `"/sprints"` on sprint-detail allows "Back to Sprints List"
- **Navigation Stack**: Used for breadcrumb-style navigation

### `children` (array, optional)
- **Purpose**: Defines drill-down navigation to child entities from detail views
- **Frontend Usage**: Creates dynamic `on{Type}Select` handlers
- **Example**: 
  ```json
  "children": [{
    "type": "Task",
    "command": "/task-detail",
    "item_key": "task_code"
  }]
  ```
  Creates: `onTaskSelect = (item) => executeDetailCommand('/task-detail ' + item.task_code)`

## Complete Examples

### List Command (with drill-down)
```json
{
  "data_prop": "sprints",
  "item_key": "code",
  "detail_command": "/sprint-detail",
  "children": [
    {
      "type": "Task",
      "command": "/task-detail",
      "item_key": "task_code"
    }
  ]
}
```
**Generates**:
- `props.sprints = result.data.sprints`
- `props.onItemSelect = (item) => executeDetailCommand('/sprint-detail ' + item.code)`
- `props.onTaskSelect = (item) => executeDetailCommand('/task-detail ' + item.task_code)`

### Detail Command (with parent and children)
```json
{
  "data_prop": "sprint",
  "item_key": "code",
  "parent_command": "/sprints",
  "children": [
    {
      "type": "Task",
      "command": "/task-detail",
      "item_key": "task_code"
    }
  ]
}
```
**Generates**:
- `props.sprint = result.data.sprint` (spread as individual props)
- `props.onBack = () => navigateToParent('/sprints')`
- `props.onTaskSelect = (item) => executeDetailCommand('/task-detail ' + item.task_code)`

### Simple List Command (no drill-down)
```json
{
  "data_prop": "agents",
  "item_key": "slug",
  "detail_command": "/agent-profile-detail"
}
```
**Generates**:
- `props.agents = result.data.agents`
- `props.onItemSelect = (item) => executeDetailCommand('/agent-profile-detail ' + item.slug)`

## How to Add Navigation for New Entity Type

**No Code Changes Required!** Just update the database:

```php
use App\Models\Command;

$command = Command::where('command', '/fragments')->first();
$command->navigation_config = [
    'data_prop' => 'fragments',
    'item_key' => 'id',
    'detail_command' => '/fragment-detail',
];
$command->save();
```

**Requirements**:
1. The component must accept props matching the pattern:
   - List modals: `{data_prop}` prop (e.g., `fragments` prop)
   - List modals: `onItemSelect` handler
   - Detail modals: Spread data props (e.g., `fragment`, `stats`, etc.)
   - Detail modals: `onBack` handler

2. The detail command must exist in the database

3. The component must be registered in `COMPONENT_MAP` in `CommandResultModal.tsx`

## Frontend Implementation

The `buildComponentProps()` function in `CommandResultModal.tsx` handles config-driven prop mapping:

```typescript
// CONFIG-DRIVEN: Use navigation config
if (navConfig?.data_prop) {
  props[navConfig.data_prop] = result.data[navConfig.data_prop]
  
  if (navConfig.detail_command && navConfig.item_key) {
    props.onItemSelect = (item) => 
      executeDetailCommand(`${navConfig.detail_command} ${item[navConfig.item_key]}`)
  }
  
  navConfig.children?.forEach(child => {
    const handlerName = `on${capitalize(child.type)}Select`
    props[handlerName] = (item) => 
      executeDetailCommand(`${child.command} ${item[child.item_key]}`)
  })
}
```

## Legacy Fallback

Commands without `navigation_config` use hardcoded type detection as fallback:

```typescript
// LEGACY FALLBACK: Maintain backward compatibility
if (componentName.includes('Sprint')) {
  props.sprints = result.data
  props.onItemSelect = (item) => executeDetailCommand(`/sprint-detail ${item.code}`)
}
```

This ensures existing functionality remains intact while new commands can use config-driven approach.

## Migration Strategy

1. **Phase 1 (Current)**: Config-driven for orchestration entities (sprints, tasks, agents)
2. **Phase 2**: Add configs for fragments, vaults, bookmarks, projects
3. **Phase 3**: Remove legacy fallback once all commands have configs
4. **Phase 4**: Build visual navigation config editor

## Edge Cases & Notes

### Multiple Data Properties
The config handles responses like `{sprints: [...], unassigned_tasks: [...]}` by:
1. Setting `props[data_prop]` to the primary array
2. Copying all other properties: `props.unassigned_tasks = result.data.unassigned_tasks`

### Detail Modal Data Spreading
Detail modals receive data spread as individual props:
```typescript
// Input: { sprint: {...}, tasks: [...], stats: {...} }
// Output: props.sprint = {...}, props.tasks = [...], props.stats = {...}
```

### Handler Name Capitalization
Child type names are capitalized for handler names:
- `"type": "Task"` → `onTaskSelect`
- `"type": "agent"` → `onAgentSelect`

### Command Format
- Commands must start with `/`
- Item key is appended with space: `/sprint-detail SPRINT-01`
- Ensure backend command parser handles this format

## Future Enhancements

### Visual Navigation Builder
A future UI tool could allow admins to:
1. Select entity type (Sprint, Task, Project, etc.)
2. Configure data prop and item key via dropdowns
3. Link to detail commands with autocomplete
4. Define child relationships via drag-and-drop
5. Preview generated navigation flow
6. Save directly to `navigation_config` column

### Advanced Features
- **Conditional navigation**: Show detail command only if user has permission
- **Query parameters**: Append filters/sort to detail commands
- **Multi-level nesting**: Support grandchild relationships (Sprint → Task → Subtask)
- **Custom handlers**: Override default behavior with custom JS functions
- **Breadcrumb trails**: Auto-generate breadcrumbs from navigation stack

## Testing Checklist

When adding navigation config for new entity:

- [ ] Config saved to `navigation_config` column
- [ ] `data_prop` matches component prop name
- [ ] `item_key` matches unique field in data array
- [ ] `detail_command` exists in commands table
- [ ] Component registered in `COMPONENT_MAP`
- [ ] List modal accepts `{data_prop}` prop
- [ ] List modal accepts `onItemSelect` handler
- [ ] Detail modal accepts spread data props
- [ ] Detail modal accepts `onBack` handler
- [ ] ESC key closes modal from root, goes back from stack
- [ ] Navigation stack properly manages view history
- [ ] Build succeeds: `npm run build`
- [ ] No console errors when navigating

## Related Files

- **Frontend**: `resources/js/islands/chat/CommandResultModal.tsx`
- **Backend**: `app/Commands/BaseCommand.php` (passes config to UI)
- **Migration**: `database/migrations/*_add_navigation_config_to_commands_table.php`
- **Model**: `app/Models/Command.php` (navigation_config field)
- **Seeders**: Command seeders that set navigation_config
