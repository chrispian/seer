# Component Schema Issues

## Issue #1: Tabs Component Structure Mismatch

**Date:** October 28, 2025  
**Component:** `tabs`  
**Severity:** High - Causes infinite render loop

### Problem

The tabs component expects a specific structure that differs from standard component patterns.

**Expected (from schema):**
```json
{
  "type": "tabs",
  "props": {
    "defaultValue": "tab1",
    "tabs": [
      {
        "value": "tab1",
        "label": "Tab 1", 
        "content": [ ...ComponentConfig[] ],
        "disabled": false
      }
    ]
  }
}
```

**What was used (incorrect):**
```json
{
  "type": "tabs",
  "props": {
    "defaultValue": "tab1"
  },
  "children": [
    {
      "type": "tab-panel",
      "props": { "value": "tab1", "label": "Tab 1" },
      "children": [ ... ]
    }
  ]
}
```

### Root Cause

Component schema defines:
```json
{
  "props": ["defaultValue", "tabs", "className", "listClassName"],
  "tabs": ["value", "label", "content", "disabled"],
  "children": false  // <-- Key indicator
}
```

The `"children": false` indicates this component doesn't use the standard children pattern.

### Impact

- Tabs component tries to map over `props.tabs` which is undefined
- Causes `Cannot read properties of undefined (reading 'map')` error
- Results in infinite render loop
- Page becomes unusable

### Solution

**Short-term:**
- Don't use tabs component until pattern is understood
- Use simpler layout components (card, rows)

**Long-term:**
- Create component schema validator
- Auto-generate correct structure from schema
- Document all non-standard component patterns

### Prevention

Before using any component, check its schema:
```php
$component = Component::where('type', 'tabs')->first();
$schema = $component->schema_json;

// Check if it uses children
if (isset($schema['children']) && $schema['children'] === false) {
    // Use alternative structure defined in schema
}
```

---

## Pattern: Components Without Children

Some components don't follow the standard `children` pattern:

### Standard Pattern (Most Components)
```json
{
  "type": "card",
  "props": { ... },
  "children": [ ...ComponentConfig[] ]
}
```

### Alternative Pattern (tabs, maybe others)
```json
{
  "type": "tabs",
  "props": {
    "tabs": [ ...TabConfig[] ]
  }
}
```

### How to Detect

Check component schema:
- `"children": true` or undefined → Use children array
- `"children": false` → Check schema for alternative (like `tabs` array)

---

## Recommended Schema Documentation

Each component should document:

1. **Structure pattern** (children vs alternative)
2. **Required props**
3. **Optional props**
4. **Examples** (working configurations)

### Example Documentation

```typescript
/**
 * Tabs Component
 * 
 * Structure: Alternative (uses props.tabs, not children)
 * 
 * Required Props:
 * - tabs: Array of tab configurations
 * 
 * Optional Props:
 * - defaultValue: string (default: first tab value)
 * - className: string
 * - listClassName: string
 * 
 * Tab Configuration:
 * - value: string (unique identifier)
 * - label: string (display text)
 * - content: ComponentConfig[] (tab content)
 * - disabled: boolean (optional)
 * 
 * Example:
 * {
 *   "type": "tabs",
 *   "props": {
 *     "defaultValue": "tab1",
 *     "tabs": [
 *       {
 *         "value": "tab1",
 *         "label": "First Tab",
 *         "content": [
 *           { "type": "typography", "props": { "text": "Content" } }
 *         ]
 *       }
 *     ]
 *   }
 * }
 */
```

---

## Action Items

- [ ] Audit all components for non-standard patterns
- [ ] Document each component's structure requirements
- [ ] Create schema validator for builder
- [ ] Add component examples to database
- [ ] Build schema-aware form generator
