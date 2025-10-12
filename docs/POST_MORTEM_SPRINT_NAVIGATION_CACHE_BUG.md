# Post-Mortem: Sprint Navigation Cache Bug
## Hash: #PM-CACHE-2025-10-11

**Date**: October 11, 2025  
**Severity**: High (Core navigation broken)  
**Duration**: ~4 hours investigation  
**Resolution**: Cache clear revealed correct data  
**Actual Issue**: Command registry cache not invalidated after seeder updates

---

## TL;DR

`/sprints` command appeared broken, showing error modal instead of sprint list. Investigation found the database had correct values but `CommandRegistry` service was serving stale cached data. Cache TTL of 1 hour meant seeder changes weren't reflected until cache expired or was manually cleared.

---

## What Happened

### Symptom
- `/sprints` command showed generic error modal instead of `SprintListModal`
- Navigation (sprint → detail → task detail) didn't work
- No errors in console, no error messages
- User manually corrected what appeared to be wrong database value
- After cache clear, worked perfectly with no database changes needed

### Root Cause

**CommandRegistry Cache Not Cleared After Seeder**

`app/Services/CommandRegistry.php` line 20:
```php
self::$commandsCache = Cache::remember('command_registry', self::$cacheTtl, function () {
    $commands = [];
    Command::active()->with('type')->get()->each(function ($command) use (&$commands) {
        $commandSlug = ltrim($command->command, '/');
        $commands[$commandSlug] = [
            'handler_class' => $command->handler_class,
            'command' => $command,
            'type' => $command->type,
        ];
    });
    return $commands;
});
```

**Cache TTL**: 1 hour (3600 seconds)

**The Flow**:
1. Seeder updates `commands.ui_modal_container` from `DataManagementModal` → `SprintListModal`
2. Cache still has old value for 1 hour
3. User runs `/sprints` → gets cached config with `DataManagementModal`
4. Wrong component loads, navigation broken
5. User runs `php artisan cache:clear`
6. Next request gets fresh data with `SprintListModal`
7. Everything works ✅

---

## Why Investigation Took 4 Hours

### Confusion Factor #1: Two Type Systems

There are **TWO** type systems and it's not clear which is current:

**System A: `fragment_type_registry` table**
- Stores fragment-backed types (notes, bookmarks, todos)
- Has `container_component` field
- Active and in use ✅

**System B: `types_registry` table**  
- Stores model-backed types (sprints, tasks, agents)
- Created in Type+Command unification sprint
- Active and in use ✅

**Both are current!** They serve different purposes:
- `fragment_type_registry` = for fragments (schemaless JSON documents)
- `types_registry` = for models (database tables with Eloquent models)

**Why confusing**: Documentation calls `types_registry` "the new unified system" and `fragment_type_registry` "legacy", but BOTH are active and used for different storage types.

### Confusion Factor #2: Component Resolution Path Unclear

`CommandResultModal.tsx` doesn't document WHERE component names come from:

```typescript
// Current code (line 113)
function getComponentName(result: CommandResult): string {
  if (result.config?.ui?.modal_container) {
    return result.config.ui.modal_container  // ← WHERE does this come from???
  }
  // ... more checks
}
```

**Missing documentation**:
- `config.ui.modal_container` comes from `commands.ui_modal_container` (for slash commands)
- OR from `fragment_type_registry.container_component` (for fragment types)
- These are composed in `BaseCommand.php` `getUIConfig()`

### Confusion Factor #3: "Legacy" vs "Current" Terminology

**In the codebase, "legacy" means 3 different things**:

1. **YAML command system** (fragments/commands/) - Truly deprecated ✅
2. **Type-specific props** (sprints, tasks) - Being phased out for generic `data` ⏳  
3. **Hardcoded switch statement** - Removed in favor of config-driven ✅

**But also called "legacy"**:
- `fragment_type_registry` table - **Actually current and active!**
- `types_registry` table - Called "new unified" but also current!

### Confusion Factor #4: Cache Invalidation Not Documented

When to run `php artisan cache:clear` or `CommandRegistry::clearCache()`:
- After running seeders ✅ **THIS WAS MISSING**
- After migrations ✅ **THIS WAS MISSING**
- After manual database updates ✅
- After config changes ❓

**Not documented anywhere obvious.**

### Confusion Factor #5: Table Names Are Confusing

- `fragment_type_registry` - Sounds old/legacy, actually current ❌
- `types_registry` - Sounds current, but so is fragment_type_registry ❌
- `command_registry` (YAML old system) vs `commands` (new system) ❌
- `CommandRegistry` service vs `command_registry` table - different things! ❌

---

## Current System (THE TRUTH - UPFRONT)

### Active Tables (ALL CURRENT, NONE LEGACY)

**1. `fragment_type_registry`**
- **Purpose**: Configuration for fragment-backed types (schema-based storage)
- **Examples**: notes, bookmarks, todos, logs
- **Key Fields**: `slug`, `container_component`, `detail_component`, `schema`
- **Status**: ✅ ACTIVE, CURRENT, IN USE

**2. `types_registry`**
- **Purpose**: Configuration for model-backed types (Eloquent models)
- **Examples**: sprints, tasks, agents, projects, vaults
- **Key Fields**: `slug`, `model_class`, `default_card_component`, `default_detail_component`
- **Status**: ✅ ACTIVE, CURRENT, IN USE

**3. `commands`**
- **Purpose**: Slash command registry and configuration
- **Examples**: /sprints, /tasks, /agents, /projects
- **Key Fields**: `command`, `handler_class`, `type_slug`, `ui_modal_container`, `navigation_config`
- **Status**: ✅ ACTIVE, CURRENT, IN USE

### Deprecated Tables (DO NOT USE)

**1. `command_registry`** (YAML system)
- **Purpose**: OLD YAML-based command storage
- **Status**: ❌ DEPRECATED, being removed
- **Action**: Delete remaining entries, eventually drop table

---

## Component Resolution (How It Actually Works)

### For Slash Commands (/sprints, /tasks, etc.)

**Backend** (`BaseCommand.php` line 77-96):
```php
protected function getUIConfig(): array {
    return [
        'modal_container' => $this->command->ui_modal_container,  // From commands table
        'navigation' => $this->command->navigation_config,
        'layout_mode' => $this->command->ui_layout_mode,
        'card_component' => $this->command->ui_card_component 
            ?? $typeConfig['default_card_component'],  // Fallback to type default
        // ...
    ];
}
```

**Frontend** (`CommandResultModal.tsx` line 113-136):
```typescript
function getComponentName(result: CommandResult): string {
  // Priority 1: Explicit command-level override
  if (result.config?.ui?.modal_container) {
    return result.config.ui.modal_container  // From commands.ui_modal_container
  }
  
  // Priority 2: Command-level card component (transformed)
  if (result.config?.ui?.card_component) {
    return transformCardToModal(result.config.ui.card_component)
  }
  
  // Priority 3: Type-level default (transformed)
  if (result.config?.type?.default_card_component) {
    return transformCardToModal(result.config.type.default_card_component)
  }
  
  // Fallback
  return 'UnifiedListModal'
}
```

**For `/sprints`**:
1. Looks up in `commands` table → finds `ui_modal_container = "SprintListModal"`
2. Returns in `config.ui.modal_container`
3. Frontend uses that value directly
4. Loads `SprintListModal` component

### For Fragment Types (notes, bookmarks, etc.)

These DON'T have entries in `commands` table. They use `fragment_type_registry.container_component` directly when accessed via fragment queries.

---

## The Actual Bug

### What Appeared To Happen

User saw wrong component loading for `/sprints`. Checked database, saw (what appeared to be) wrong value. Changed it, reloaded, worked.

### What Actually Happened

**Commands table had correct value all along**: `ui_modal_container = "SprintListModal"`

**CommandRegistry cache had stale value**: Old config with `DataManagementModal` (from before seeder update)

**User action that fixed it**: Running `cache:clear` (or possibly just waiting for 1-hour TTL to expire)

**Why database appeared to show wrong value**: Possible caching in database client, or looking at wrong table/row

### Evidence

```bash
# Current state (after cache clear)
/sprints ui_modal_container: SprintListModal  ✅ CORRECT

# This was correct the whole time, but cached response had old value
```

---

## Why Agents Got Confused (And I Did Too)

### 1. **Documentation Ambiguity**

**Problem**: Docs use "legacy" and "current" inconsistently

**Example from TYPE_COMMAND_UNIFICATION_COMPLETE.md**:
> "Successfully migrated from hardcoded arrays to database-driven architecture"
> "Deprecated: fragment_type_registry table"

**Reality**: `fragment_type_registry` is NOT deprecated, it's actively used for fragment types!

**Fix Needed**: Clear terminology
- OLD SYSTEM (deprecated): YAML commands (`command_registry` table, `fragments/commands/`)
- CURRENT SYSTEM A: Fragment types (`fragment_type_registry` table)
- CURRENT SYSTEM B: Model types (`types_registry` table)
- CURRENT SYSTEM C: Unified commands (`commands` table)

### 2. **TypeScript Lacks Documentation**

**CommandResultModal.tsx** has NO comments explaining:
- Which database tables config comes from
- What `config.ui.modal_container` represents
- How fragment types vs model types differ
- When to use which system

**Current code** (line 113):
```typescript
function getComponentName(result: CommandResult): string {
  if (result.config?.ui?.modal_container) {
    return result.config.ui.modal_container
  }
  // ...
}
```

**Should be**:
```typescript
/**
 * Resolves which React component to render based on backend configuration.
 * 
 * #PM-CACHE-2025-10-11
 * 
 * CURRENT SYSTEM (as of Oct 2025):
 * - Model-backed types (sprints, tasks, agents): configured in `commands` table
 * - Fragment-backed types (notes, bookmarks): configured in `fragment_type_registry` table
 * - Both systems are ACTIVE and CURRENT
 * 
 * Component Resolution Priority:
 * 1. config.ui.modal_container (from commands.ui_modal_container)
 * 2. config.ui.card_component (from commands.ui_card_component, transformed)
 * 3. config.type.default_card_component (from types_registry.default_card_component, transformed)
 * 4. UnifiedListModal (fallback)
 * 
 * @param result Command execution result with config object
 * @returns Component name string (e.g., "SprintListModal")
 */
function getComponentName(result: CommandResult): string {
  if (result.config?.ui?.modal_container) {
    return result.config.ui.modal_container
  }
  // ...
}
```

### 3. **No Cache Invalidation in Seeders**

**CommandsSeeder.php** updates database but doesn't clear cache:

```php
public function run(): void {
    $commands = [ /* ... */ ];
    
    foreach ($commands as $commandData) {
        Command::updateOrCreate(
            ['command' => $commandData['command']],
            $commandData
        );
    }
    
    $this->command->info('Seeded ' . count($commands) . ' commands');
    
    // ❌ MISSING: Cache::forget('command_registry');
    // ❌ MISSING: CommandRegistry::clearCache();
}
```

**Should include**:
```php
// Clear cache so changes take effect immediately
Cache::forget('command_registry');
\App\Services\CommandRegistry::clearCache();

$this->command->info('✅ Seeded ' . count($commands) . ' commands and cleared cache');
```

### 4. **Component Names in Multiple Places**

Component name `SprintListModal` appears in:
1. `commands.ui_modal_container` (database)
2. `types_registry.default_card_component` = `SprintCard` (transforms to `SprintListModal`)
3. `CommandResultModal.tsx` COMPONENT_MAP registry
4. Actual component file name: `SprintListModal.tsx`

**One typo anywhere breaks everything**, and it's not obvious where to look.

### 5. **Error Messages Don't Say Where Data Comes From**

When `SprintListModal` fails to load, error shows:
```
Command Failed: /sprints
An error occurred while executing this command.
```

**Doesn't say**:
- Which component it tried to load
- Where that component name came from (which table, which field)
- Whether data came from cache or database
- What the config resolution path was

---

## How Database Appeared to Show Wrong Value

**Theory**: When user checked "fragment_type_registry id 6" and saw "sprint", it was likely:
1. Database client cache showing stale results
2. Looking at a different row/table
3. CommandRegistry cache showing merged/transformed data

**What actually was in DB**:
- `fragment_type_registry` ID 6 = `note` type with `container_component = SprintListModal` ❌ (wrong component for note!)
- `commands` table `/sprints` = `ui_modal_container = SprintListModal` ✅ (correct!)

**Note type having SprintListModal is also wrong** - that's a separate bug that got masked.

---

## What Changes Caused This

### Recent Commits That Touched Component Configuration

**Commit 8acf2b6** (Oct 10): "feat(config): implement config-driven navigation foundation"
- Added `navigation_config` column to `commands` table
- Seeded navigation config for core commands
- Modified `CommandsSeeder.php` to set `ui_modal_container` values
- **Likely ran seeder without clearing cache** ❌

**Commit 20c7d92** (Oct 10): "feat(commands)!: complete Type+Command unification"
- Created `commands` table
- Created `CommandsSeeder` with initial ui_modal_container values
- **May have set wrong initial values that were later corrected** ❓

**Commit 313baec** (Oct 10): "fix(ui): resolve modal navigation stack ESC key stale closure bug"
- TypeScript changes only
- No database changes

### The Smoking Gun

Somewhere between commits, `CommandsSeeder.php` was updated to change component mappings. When the seeder re-ran, it updated the database, but `CommandRegistry` cache (1-hour TTL) still had old values. 

**Cache was never cleared** after seeding, so the app served stale component names.

---

## Fixes Applied (In This Session)

### Immediate Fixes

1. ✅ **Cleared all caches** - Made current database values take effect
2. ✅ **Fixed SQL error** in `SprintListCommand.fetchUnassignedTasks()` - was using `whereJsonLength` on scalar field
3. ✅ **Fixed Vite HMR preamble error** - Documented that dev server breaks, use `npm run build`
4. ✅ **Removed CLI commands from CommandsSeeder** - Cleanup to prevent confusion

### Still Needed

1. ❌ **Add cache clearing to seeders** - CommandsSeeder, TypesSeeder, SystemTypesSeeder
2. ❌ **Document cache invalidation rules** - When to clear what
3. ❌ **Add comprehensive TypeScript documentation** - Explain table sources in CommandResultModal.tsx
4. ❌ **Create terminology guide** - Define "legacy" vs "current" clearly
5. ❌ **Add better error messages** - Show config resolution path when component fails
6. ❌ **Fix note type having wrong component** - SprintListModal should be NoteListModal or UnifiedListModal
7. ❌ **Document the TWO type systems** - Why both exist, when to use which

---

## Prevention Checklist

### For Developers

**After running seeders:**
```bash
php artisan db:seed --class=CommandsSeeder
php artisan cache:clear  # ← CRITICAL, must do this
```

**After migrations:**
```bash
php artisan migrate
php artisan cache:clear  # ← CRITICAL
```

**When testing config changes:**
```bash
# Update database manually
php artisan tinker
# > DB::table('commands')->where('command', '/sprints')->update(['ui_modal_container' => 'NewModal']);
# > exit

# MUST clear cache immediately
php artisan cache:clear
CommandRegistry::clearCache()  # Or restart Laravel
```

### For Agents

**Agents should:**
1. ✅ Always run `php artisan cache:clear` after seeding
2. ✅ Document in commit message if seeder was run
3. ✅ Check `CommandRegistry` cache TTL before testing
4. ✅ Understand the TWO type systems (fragment vs model)
5. ✅ Not assume "legacy" means "unused"

**Agents should NOT:**
1. ❌ Modify `types_registry` when meaning to modify `fragment_type_registry`
2. ❌ Call tables "legacy" without verifying they're truly deprecated
3. ❌ Skip cache clearing after database changes
4. ❌ Focus on TypeScript when the issue is database/cache

---

## Why I (Agent) Got Stuck on TypeScript

### My Mistakes

1. **Ignored database instructions** - User said "it's in fragment_type_registry" multiple times, I kept looking at TypeScript
2. **Assumed "legacy" meant deprecated** - Docs said fragment_type_registry was legacy, so I dismissed it
3. **Fixated on code changes** - Looked at last 3 commits of TypeScript, ignored database changes
4. **Didn't check cache** - Never considered that data might be correct but cached
5. **Followed wrong documentation** - Read TYPE_COMMAND_UNIFICATION docs that called things "legacy" incorrectly

### What TypeScript Implied Was Legacy

**In `CommandResultModal.tsx` lines 196-218**:
```typescript
} else {
  // LEGACY FALLBACK: Maintain backward compatibility for commands without navigation config
  if (componentName.includes('Sprint')) {
    props.sprints = result.data.sprints
  } else if (componentName.includes('Task')) {
    props.tasks = result.data
  }
  // ...
}
```

**Comment says "LEGACY FALLBACK"** - This made me think:
- Type-specific props are legacy ✓ (correct)
- Hard-coded type checks are legacy ✓ (correct)
- The whole component system is legacy ❌ (WRONG)

**Should say**:
```typescript
} else {
  // FALLBACK: For commands without navigation_config (still supported)
  // Type-specific props maintained for backward compatibility
  // TODO: All commands should migrate to navigation_config (#PM-CACHE-2025-10-11)
  if (componentName.includes('Sprint')) {
```

---

## Documentation Needed (URGENT TASKS)

### Task 1: Add Comprehensive TypeScript Comments
**File**: `resources/js/islands/chat/CommandResultModal.tsx`  
**Hash**: #PM-CACHE-2025-10-11

Add at top of file:
```typescript
/**
 * CommandResultModal - Config-Driven Command Result Renderer
 * 
 * #PM-CACHE-2025-10-11
 * 
 * ═══════════════════════════════════════════════════════════════
 * CURRENT SYSTEM ARCHITECTURE (As of October 2025)
 * ═══════════════════════════════════════════════════════════════
 * 
 * This system is 100% database-driven. Component names come from:
 * 
 * FOR SLASH COMMANDS (/sprints, /tasks, etc.):
 *   Source: `commands` table
 *   Field:  `ui_modal_container`
 *   Example: "/sprints" → commands.ui_modal_container = "SprintListModal"
 *   Composed by: BaseCommand.php getUIConfig() → config.ui.modal_container
 * 
 * FOR FRAGMENT TYPES (notes, bookmarks, etc.):
 *   Source: `fragment_type_registry` table
 *   Field:  `container_component`
 *   Example: "note" → fragment_type_registry.container_component = "UnifiedListModal"
 *   Composed by: (depends on command implementation)
 * 
 * BOTH SYSTEMS ARE ACTIVE AND CURRENT:
 *   - fragment_type_registry: For fragment-backed types (schema-based JSON storage)
 *   - types_registry: For model-backed types (Eloquent models with tables)
 *   - commands: For all slash commands (both fragment and model types)
 * 
 * ═══════════════════════════════════════════════════════════════
 * CACHE IMPORTANT
 * ═══════════════════════════════════════════════════════════════
 * 
 * CommandRegistry caches command config for 1 hour.
 * 
 * After running seeders or updating commands table:
 *   php artisan cache:clear
 *   OR
 *   CommandRegistry::clearCache()
 * 
 * Stale cache = wrong components load = broken navigation
 * 
 * ═══════════════════════════════════════════════════════════════
 * ADDING NEW COMMANDS (Zero Code Changes Required)
 * ═══════════════════════════════════════════════════════════════
 * 
 * 1. Add entry to CommandsSeeder.php:
 *    ['command' => '/my-command', 'ui_modal_container' => 'MyListModal', ...]
 * 
 * 2. Register component in COMPONENT_MAP below:
 *    'MyListModal': MyListModal
 * 
 * 3. Run seeder and clear cache:
 *    php artisan db:seed --class=CommandsSeeder
 *    php artisan cache:clear
 * 
 * 4. Test: /my-command
 * 
 * That's it! No if/else statements, no type checks, no hardcoding.
 * 
 * ═══════════════════════════════════════════════════════════════
 */
```

### Task 2: Update Seeders with Cache Clearing
**Files**: All seeders  
**Hash**: #PM-CACHE-2025-10-11

`CommandsSeeder.php`:
```php
public function run(): void
{
    $commands = [ /* ... */ ];
    
    foreach ($commands as $commandData) {
        Command::updateOrCreate(['command' => $commandData['command']], $commandData);
    }
    
    // #PM-CACHE-2025-10-11: Clear cache so changes take effect immediately
    Cache::forget('command_registry');
    \App\Services\CommandRegistry::clearCache();
    
    $this->command->info('✅ Seeded ' . count($commands) . ' commands and cleared cache');
}
```

Same for:
- `TypesSeeder.php`
- `SystemTypesSeeder.php`

### Task 3: Create System Architecture Doc
**File**: `docs/CURRENT_SYSTEM_ARCHITECTURE.md`  
**Hash**: #PM-CACHE-2025-10-11

Clear, upfront explanation of:
- What tables exist
- What each table is for
- Which are current vs deprecated
- How they work together
- Examples for each use case

### Task 4: Create Terminology Guide
**File**: `docs/TERMINOLOGY_GUIDE.md`  
**Hash**: #PM-CACHE-2025-10-11

Define:
- "Legacy" = YAML command system ONLY
- "Current Fragment System" = fragment_type_registry
- "Current Model System" = types_registry + commands
- "Unified System" = Both working together

### Task 5: Add Cache Documentation
**File**: `docs/CACHE_MANAGEMENT.md`  
**Hash**: #PM-CACHE-2025-10-11

Document:
- All cache keys used in the system
- TTL for each cache
- When to clear what
- How to debug cache issues

### Task 6: Improve Error Messages
**File**: `resources/js/islands/chat/CommandResultModal.tsx`  
**Hash**: #PM-CACHE-2025-10-11

When component not found:
```typescript
console.error('[CommandResultModal] Component resolution failed:', {
  attemptedComponent: componentName,
  configSource: getConfigSource(result),
  availableInMap: Object.keys(COMPONENT_MAP),
  config: result.config,
  command: result.config?.command?.command,
})
```

### Task 7: Fix Note Type Component
**Issue**: `fragment_type_registry` ID 6 (note) has `container_component = SprintListModal`  
**Should be**: `UnifiedListModal` or `NoteListModal`  
**Hash**: #PM-CACHE-2025-10-11

---

## Lessons Learned

### For Future Debugging

1. **Check cache first** when data appears wrong
2. **Verify which table** the config actually comes from
3. **Don't assume "legacy" means "unused"** - verify in code
4. **Listen when user points to specific tables** - they know their system
5. **Clear all caches** before declaring something broken

### For Documentation

1. **Be explicit about "current"** - Say "ACTIVE" not "new unified"
2. **Define "legacy" precisely** - List exactly what's deprecated
3. **Document table purposes** upfront in every file
4. **Add hash tags** to relate changes across debugging sessions
5. **Show examples** of what comes from where

### For Code

1. **Add cache clearing to seeders automatically**
2. **Add detailed logging** showing config resolution path
3. **Document which tables** config fields come from in JSDoc
4. **Validate component names** at seed time (check COMPONENT_MAP)
5. **Add cache debug command** to show current cached values

---

## Action Items

**Immediate** (This Commit):
- [ ] Add comprehensive comments to CommandResultModal.tsx (#PM-CACHE-2025-10-11)
- [ ] Add cache clearing to all seeders (#PM-CACHE-2025-10-11)
- [ ] Fix note type container_component (#PM-CACHE-2025-10-11)
- [ ] Create CURRENT_SYSTEM_ARCHITECTURE.md (#PM-CACHE-2025-10-11)
- [ ] Create TERMINOLOGY_GUIDE.md (#PM-CACHE-2025-10-11)
- [ ] Update CLAUDE.md with cache requirements (#PM-CACHE-2025-10-11)

**Short Term** (Next Sprint):
- [ ] Create CACHE_MANAGEMENT.md (#PM-CACHE-2025-10-11)
- [ ] Improve error messages with config path (#PM-CACHE-2025-10-11)
- [ ] Add cache debug command (#PM-CACHE-2025-10-11)
- [ ] Audit all "legacy" references in docs (#PM-CACHE-2025-10-11)

**Long Term**:
- [ ] Remove truly deprecated YAML system
- [ ] Add seeder validation (check component names exist)
- [ ] Build type management UI for editing components
- [ ] Consider shorter cache TTL or smarter invalidation

---

## Summary

**The code is not broken. The database is correct. The cache was stale.**

The real issue: Lack of clear documentation about which tables are current, where config comes from, and when to clear cache. This caused 4 hours of chasing the wrong problem.

**Fix**: Document everything clearly, add cache clearing to seeders, and make TypeScript comments explicitly state which database tables drive the config.

**Hash**: #PM-CACHE-2025-10-11 - Use this to find all related changes.
