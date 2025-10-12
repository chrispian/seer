# ADR-004: Standardize Click Handler Props (Future)

## Status
Proposed

## Date
2025-10-12

## Context
Currently, we set both generic (`onItemSelect`) and component-specific (`onTaskSelect`, `onSprintSelect`) click handlers for navigation. This creates duplication but ensures compatibility with all components.

Components currently expect different prop names:
- TaskListModal: `onTaskSelect`
- SprintListModal: `onSprintSelect` OR `onItemSelect` 
- Future components: Unknown expectations

## Decision (Proposed)
Standardize all list components to use a single prop name: `onItemSelect`

## Implementation Plan
```typescript
// Phase 1: Add onItemSelect support to all components (DONE - via duplication)
// Phase 2: Update components to prefer onItemSelect
// Phase 3: Remove component-specific props
// Phase 4: Clean up CommandResultModal

// Future simplified code:
if (navConfig.detail_command && navConfig.item_key) {
  const itemKey = navConfig.item_key
  props.onItemSelect = (item: any) => 
    handlers.executeDetailCommand!(`${navConfig.detail_command} ${item[itemKey]}`)
  // No more component-specific handlers needed!
}
```

## Consequences

### Positive
- Simpler code in CommandResultModal
- Consistent API across all components
- Easier to add new list components
- Less confusion about which prop to use

### Negative  
- Breaking change for existing components
- Need to update all list components
- Testing required for each component

## Migration Strategy
1. **Current State**: Set both (safe duplication)
2. **Next Sprint**: Update components to use onItemSelect primarily
3. **Following Sprint**: Remove component-specific props
4. **Final**: Clean up CommandResultModal

## Decision
**KEEP BOTH FOR NOW** - The duplication is harmless and ensures stability. Schedule refactor for next technical debt sprint.

## Notes
- No immediate action needed
- Current duplication is intentional for safety
- Refactor when we have full test coverage