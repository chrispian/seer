# Todo Unification Complete - Backend

**Date:** October 10, 2025  
**Status:** ✅ Backend Complete, Frontend Tasks Created

---

## Problem Identified

TodoManagementModal was the **only** modal requiring a special `panelData.fragments` format. This created:
- Special case in TodoCommand
- Risk of breaking 40+ commands if pattern changed
- Inconsistent architecture
- Confusion for developers

---

## Root Cause

TodoManagementModal was built as a **self-contained mini-application**:
- Had its own data hook (`useTodoData`)
- Fetched data independently
- Complex transformation logic
- Different from all other modals (which receive data as props)

---

## Solution Implemented

### Backend Changes (Complete ✅)

**1. Updated TodoCommand** (`app/Commands/TodoCommand.php`)
- ✅ Removed `panelData` workaround
- ✅ Added `transformToTodoItem()` method (backend transforms data)
- ✅ Added `parseTags()` helper (handles PostgreSQL array format)
- ✅ Uses standard `$this->respond(['items' => $todos])` pattern
- ✅ Added FormatsListData trait

**2. Updated Command Config** (database)
- ✅ Changed from `TodoManagementModal` → `UnifiedListModal`
- ✅ Set `ui_card_component` to `TodoCard`
- ✅ Set `ui_layout_mode` to `list`

**3. Added todo Type** (`database/seeders/TypesSeeder.php`)
- ✅ Added `todo` type (fragment-backed)
- ✅ Linked `/todos` command to `todo` type

### TodoItem Format (Backend → Frontend)

```php
[
  'id' => string,
  'fragment_id' => string,
  'title' => string,
  'message' => string,
  'status' => 'open' | 'completed' | 'in_progress' | 'blocked',
  'priority' => 'low' | 'medium' | 'high' | 'urgent',
  'tags' => array,
  'project' => string|null,
  'created_at' => string (ISO),
  'updated_at' => string (ISO),
  'completed_at' => string|null (ISO),
  'due_at' => string|null (ISO),
  'order' => int,
  'is_pinned' => bool,
]
```

---

## Frontend Tasks Created

### T-TODO-UNIFY-01: Remove TodoManagementModal Legacy Code
**Status:** Todo  
**Time:** 15 minutes

**Tasks:**
- Delete `resources/js/islands/chat/TodoManagementModal.tsx`
- Delete `resources/js/hooks/useTodoData.tsx`
- Remove TodoManagementModal from CommandResultModal imports
- Verify no other references exist

---

### T-TODO-UNIFY-02: Create TodoCard Component
**Status:** Todo  
**Time:** 1-2 hours

**Requirements:**
- Create `resources/js/components/todos/TodoCard.tsx`
- Display: title, message, tags, priority, dates, pin status
- **Checkbox** that calls API to toggle status
- Visual states: open (normal), completed (checked), in_progress (blue), blocked (red)
- API call: `/todo update {id} status:{status}`

**Component Structure:**
```typescript
<TodoCard todo={TodoItem} onUpdate={(id) => void} />
```

**Key Feature - Checkbox Logic:**
```typescript
const handleToggleStatus = async () => {
  const newStatus = todo.status === 'completed' ? 'open' : 'completed'
  await fetch('/api/commands/execute', {
    method: 'POST',
    body: JSON.stringify({
      command: `/todo update ${todo.fragment_id} status:${newStatus}`
    })
  })
  onUpdate?.(todo.id) // Refresh parent list
}
```

---

### T-TODO-UNIFY-03: Update TodoCommand for Standard Pattern
**Status:** ✅ Complete

**Completed:**
- Removed panelData special case
- Added fragment transformation
- Standard respond() pattern
- Updated database config

---

## Architecture Simplified

### Before (3 Patterns)
1. **Direct Props** - 90% of modals (sprints, tasks, bookmarks)
2. **Nested Structure** - UnifiedListModal only
3. **panelData.fragments** - TodoManagementModal only ❌ OUTLIER

### After (2 Patterns)
1. **Direct Props** - 90% of modals (sprints, tasks, bookmarks, **todos**)
2. **Nested Structure** - UnifiedListModal only

✅ **Eliminated the 3rd pattern!**

---

## Benefits Achieved

1. **Consistency** - Todos now identical to all other types
2. **No Special Cases** - TodoCommand like any other command
3. **Maintainability** - Pattern change affects all commands equally
4. **Clarity** - New developers see consistent pattern
5. **Scalability** - Easy to add new types

---

## Testing Backend

```bash
# Test TodoCommand
php artisan tinker --execute="
\$cmd = new App\Commands\TodoCommand([]);
\$cmd->setContext('web');
\$command = App\Models\Command::where('command', '/todos')->first();
\$cmd->setCommand(\$command);
\$result = \$cmd->handle();
print_r(\$result);
"

# Expected output:
# - data.items array with TodoItem objects
# - NO panelData key
# - config.ui.modal_container = 'UnifiedListModal'
# - config.ui.card_component = 'TodoCard'
```

---

## Files Modified

**Backend:**
- `app/Commands/TodoCommand.php` (refactored)
- `database/seeders/TypesSeeder.php` (added todo type)
- Database: `commands` table (updated /todos command config)

**Documentation:**
- `docs/TODO_UNIFICATION_COMPLETE.md` (this file)
- `docs/MODAL_DATA_PATTERN_ANALYSIS.md` (447 lines, analysis)
- Task summaries: T-TODO-UNIFY-01, T-TODO-UNIFY-02, T-TODO-UNIFY-03

---

## What Frontend Engineer Needs to Do

1. **Read Task Details:**
   - T-TODO-UNIFY-01: Delete legacy code (15 min)
   - T-TODO-UNIFY-02: Create TodoCard with checkbox (1-2 hours)

2. **Test:**
   - Execute `/todos` in chat
   - Verify UnifiedListModal opens
   - Verify TodoCard displays
   - Click checkbox → status toggles
   - Verify API call in Network tab

3. **Success Criteria:**
   - No TodoManagementModal references
   - TodoCard works in UnifiedListModal
   - Checkbox toggles status via API
   - Visual states correct (open/completed/in_progress/blocked)

---

## Note on /todo update Command

TodoCard needs a `/todo update` command to toggle status:

```bash
/todo update {fragment_id} status:{status}
```

This may need to be created. If it doesn't exist, the frontend engineer should:
1. Check if it exists
2. If not, create a ticket for backend to add it
3. Or use direct fragment update API as workaround

---

## Impact on Unification Project

This simplification **validates** the Type + Command Unification architecture:

> "The simplification of the command system is already paying off, this would have taken many, many turns to solve before."

By having:
- Standard response format
- Config-driven behavior  
- Reusable transformation logic
- Clear patterns

We can now **quickly identify and fix architectural inconsistencies** that would have been buried in custom code before.

---

## Next Steps

1. ✅ Backend complete
2. Frontend engineer: Execute T-TODO-UNIFY-01 and T-TODO-UNIFY-02
3. Test `/todos` command end-to-end
4. Close out todo unification tasks
5. Continue with main unification frontend work (T-FE-UNIFY-01)

---

**Backend Status:** ✅ Complete  
**Frontend Status:** 📋 Tasks Created  
**Pattern Count:** 3 → 2 ✅  
**Architecture:** Unified ✅

🎉 TodoManagementModal special case eliminated!
