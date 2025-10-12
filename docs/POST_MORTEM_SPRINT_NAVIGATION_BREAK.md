# Post-Mortem: Sprint Navigation Break
## Hash: #PM-NAV-CONFUSION-2025-10-11

**Date**: October 11, 2025  
**Severity**: High (Core navigation broken)  
**Duration**: ~4 hours investigation  
**Resolution**: Database field correction

---

## Executive Summary

Sprint navigation (/sprints → /sprint-detail) stopped working after recent commits. Investigation revealed the issue was **bad database data**, NOT broken code. The config-driven navigation system worked perfectly, but `fragment_type_registry.container_component` had the wrong value (`DataManagementModal` instead of `SprintListModal`).

**Root Cause**: Migration added `container_component` column with default value `DataManagementModal`, overwriting correct values when re-run.

**Why Investigation Took 4 Hours**: Confusion between TWO separate type systems (legacy vs current) and TypeScript code that doesn't clearly document which system it uses.

---

## Current System Architecture (THE TRUTH)

### ✅ CURRENT ACTIVE SYSTEM

**Tables**:
1. `fragment_type_registry` - Fragment-backed types (bookmarks, notes, todos, etc.)
2. `types_registry` - Model-backed types (sprints, tasks, agents, etc.)  
3. `commands` - All slash commands with UI configuration

**Component Resolution Priority** (from `CommandResultModal.tsx`):
```
1. config.ui.modal_container          (from commands table)
2. config.ui.card_component           (from commands table, transformed)
3. config.type.default_card_component (from types_registry, transformed)
4. fragment_type_registry.container_component (for fragment types)
5. UnifiedListModal (fallback)
```

**For `/sprints` command**:
- Backed by `types_registry` (slug: "sprint")
- Configured in `commands` table (command: "/sprints")
- Component specified in `commands.ui_modal_container` = "DataManagementModal"
- Navigation config in `commands.navigation_config` JSON

**For fragment types (notes, bookmarks, etc.)**:
- Backed by `fragment_type_registry`  
- Component specified in `fragment_type_registry.container_component`
- NO entry in `commands` table

### ❌ LEGACY/DEPRECATED SYSTEMS

**YAML-based commands** (`fragments/commands/`)
- Status: Deprecated, being removed
- Table: `command_registry` (old name for fragment command storage)
- Note: Most YAML commands deleted in recent cleanup

**Old component resolution**:
- Hardcoded switch statement (removed in config-driven refactor)
- Type-specific props (sprints, tasks, agents) - being phased out for generic `data` prop

---

## What Went Wrong

### The Bug

`/sprints` loaded `DataManagementModal` instead of `SprintListModal`, breaking click navigation because `DataManagementModal` doesn't have the navigation handlers that `SprintListModal` has.

### Root Cause

Migration `2025_10_07_073618_add_ui_component_columns_to_fragment_type_registry_table.php` added:
```php
$table->string('container_component')->default('DataManagementModal')
```

When this migration ran (or re-ran), it set ALL fragment types to `DataManagementModal`. However, sprints/tasks/agents are NOT in `fragment_type_registry` - they're in `types_registry` and configured via the `commands` table.

**The Real Issue**: The `/sprints` command's component wasn't coming from `fragment_type_registry` at all. It was coming from `commands.ui_modal_container` which was correctly set to `DataManagementModal` in the seeder!

**Wait, that means...**

Let me re-check this:

<function_calls>
<invoke name="bash">
<parameter name="command">cd /Users/chrispian/Projects/seer && php artisan tinker --execute="
\$cmd = DB::table('commands')->where('command', '/sprints')->first();
echo 'Commands table for /sprints:' . PHP_EOL;
echo 'ui_modal_container: ' . (\$cmd->ui_modal_container ?? 'NULL') . PHP_EOL;
echo 'Updated at: ' . \$cmd->updated_at . PHP_EOL;
"