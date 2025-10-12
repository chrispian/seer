# ADR-003: Config-Driven Navigation Handlers

## Status
Accepted

## Date
2025-10-12

## Context
The Fragments Engine uses a config-driven approach for command navigation, where navigation paths are stored in the database `navigation_config` field. However, component-specific click handlers (like `onTaskSelect`, `onSprintSelect`) were only being set in the legacy fallback path, not when using config-driven navigation. This caused clicking on rows in lists like `/tasks` to not navigate to detail views.

## Decision
Update the `buildComponentProps` function in `CommandResultModal.tsx` to set both generic and component-specific navigation handlers when using config-driven navigation.

## Implementation
```typescript
// When we have navigation config, set BOTH generic and component-specific handlers
if (navConfig.detail_command && navConfig.item_key) {
  const itemKey = navConfig.item_key
  
  // Generic handler
  props.onItemSelect = (item: any) => 
    handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
  
  // Component-specific handlers (required by some components)
  if (componentName.includes('Task')) {
    props.onTaskSelect = (item: any) => 
      handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
  } else if (componentName.includes('Sprint')) {
    props.onSprintSelect = (item: any) => 
      handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
  }
  // ... etc
}
```

## Consequences

### Positive
- Config-driven navigation now works for all list components
- Maintains backward compatibility with legacy components
- Single source of truth for navigation paths (database)
- Consistent behavior across all list/detail navigation

### Negative
- Slight duplication of handler props (both generic and specific)
- Components still have different prop names for the same action

### Future Improvements
- Standardize all components to use a single prop name (e.g., `onItemSelect`)
- Remove component-specific handlers once all components are updated
- Consider a more declarative navigation system

## Related
- `/resources/js/islands/chat/CommandResultModal.tsx:453-466`
- Database table: `commands.navigation_config`
- Issue: Task list navigation not working