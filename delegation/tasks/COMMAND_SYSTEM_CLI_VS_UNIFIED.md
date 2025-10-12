# Command System: CLI vs Unified Commands

**Created**: October 11, 2025  
**Status**: CRITICAL - Read Before Working on Commands

---

## Two Separate Command Systems

Fragments Engine has **TWO DISTINCT** command systems that serve different purposes:

### 1. Console Commands (CLI-Only)
**Location**: `app/Console/Commands/Orchestration*.php`  
**Registration**: Automatic via Laravel's command discovery  
**Invocation**: `php artisan orchestration:sprints`  
**Purpose**: Terminal output for developers/scripts  
**Output**: Formatted text tables, JSON for parsing  

Examples:
- `orchestration:sprints`
- `orchestration:tasks`
- `orchestration:agents`
- `orchestration:sprint:detail`
- `orchestration:task:assign`

### 2. Unified Commands (Web UI + MCP)
**Location**: `app/Commands/Orchestration/*/ListCommand.php`  
**Registration**: `database/seeders/CommandsSeeder.php`  
**Invocation**: `/sprints` (web), MCP tools  
**Purpose**: Structured data for UI and AI agents  
**Output**: Arrays with data, config, type info  

Examples:
- `/sprints` → `App\Commands\Orchestration\Sprint\ListCommand`
- `/tasks` → `App\Commands\Orchestration\Task\ListCommand`
- `/agents` → `App\Commands\Orchestration\Agent\ListCommand`

---

## CRITICAL: Do NOT Mix Them

### ❌ WRONG - Adding Console Commands to Unified System

```php
// CommandsSeeder.php - DO NOT DO THIS
[
    'command' => 'orchestration:sprints',  // ❌ Console command
    'handler_class' => 'App\\Commands\\Orchestration\\Sprint\\ListCommand',
    'available_in_slash' => false,
    'available_in_cli' => true,
    ...
]
```

**Problem**: This creates duplicate entries that confuse the routing system.

### ✅ CORRECT - Console Commands Stay Separate

Console commands in `app/Console/Commands/` are automatically registered by Laravel.  
**Do NOT add them to**:
- `CommandsSeeder.php`
- `commands` database table
- `CommandRegistry` service

They work via Laravel's native artisan system.

---

## How They Work Together

Some console commands are **thin wrappers** around unified commands:

```php
// app/Console/Commands/OrchestrationSprintsCommand.php
class OrchestrationSprintsCommand extends Command
{
    public function handle(): int
    {
        // Calls the unified command
        $command = new ListCommand([...]);
        $command->setContext('cli');
        $result = $command->handle();
        
        // Formats output for terminal
        $this->outputTable($result['data']);
        return self::SUCCESS;
    }
}
```

This is GOOD - it reuses logic but keeps output formatting separate.

---

## When to Use Which

### Use Console Commands (`orchestration:*`) when:
- Running from terminal/cron jobs
- Need formatted text output
- Developers debugging locally
- Scripts processing output

### Use Unified Commands (`/sprints`) when:
- Web UI slash commands
- MCP tools for AI agents
- Need structured data (arrays/JSON)
- Building UI components

---

## The Recent Bug (Fixed)

**What Happened**: Sprint 3 (Type+Command unification) added both console commands AND unified commands to the `commands` table.

```php
// CommandsSeeder.php had BOTH:
['command' => '/sprints', ...],              // ✅ Correct
['command' => 'orchestration:sprints', ...], // ❌ Wrong - caused conflict
```

**Symptom**: Running `/sprints` showed error: `Command Failed: /orchestration:sprints`

**Root Cause**: `CommandRegistry` loaded both entries, and when building the response config, it picked the wrong one (ID 3 instead of ID 1), causing the modal title to show `/orchestration:sprints`.

**Fix**:
1. Removed `orchestration:sprints`, `orchestration:tasks`, `orchestration:agents` from `CommandsSeeder.php`
2. Deleted those entries from database
3. Added docblocks to console commands clarifying they are CLI-only
4. Updated this documentation

---

## Guidelines for Future Work

### Adding a New Orchestration Feature

**Step 1**: Create Unified Command
```php
// app/Commands/Orchestration/Feature/ListCommand.php
class ListCommand extends BaseCommand {
    public function handle(): array {
        return $this->respond([
            'features' => Feature::all(),
        ], 'FeatureListModal');
    }
}
```

**Step 2**: Register in Seeder
```php
// database/seeders/CommandsSeeder.php
[
    'command' => '/features',
    'handler_class' => 'App\\Commands\\Orchestration\\Feature\\ListCommand',
    'available_in_slash' => true,
    'available_in_mcp' => true,
    ...
]
```

**Step 3** (Optional): Create Console Wrapper
```php
// app/Console/Commands/OrchestrationFeaturesCommand.php
/**
 * CLI-ONLY - Do NOT add to CommandsSeeder
 */
class OrchestrationFeaturesCommand extends Command {
    // Wrapper that calls ListCommand with cli context
}
```

**Step 4**: DO NOT add console command to database!

---

## Reference Files

### Documentation
- `docs/TYPE_COMMAND_UNIFICATION_COMPLETE.md` - Unified system overview
- `delegation/tasks/COMMAND_SYSTEM_UNIFICATION.md` - Original planning
- `delegation/tasks/COMMAND_SYSTEM_UNIFICATION_REVISED.md` - Revised decisions
- `docs/YAML_COMMAND_CLEANUP.md` - YAML deprecation notes

### Code
- `app/Commands/` - Unified commands (web/MCP)
- `app/Console/Commands/Orchestration*.php` - CLI commands
- `database/seeders/CommandsSeeder.php` - Unified command registration
- `app/Services/CommandRegistry.php` - Runtime command resolution
- `app/Http/Controllers/CommandController.php` - HTTP endpoint

---

## Quick Reference

| Aspect | Console Commands | Unified Commands |
|--------|------------------|------------------|
| **Namespace** | `App\Console\Commands` | `App\Commands\Orchestration` |
| **Signature** | `orchestration:sprints` | `/sprints` |
| **Registration** | Auto-discovered | `CommandsSeeder.php` |
| **Database** | NOT in `commands` table | IN `commands` table |
| **Invocation** | `php artisan orchestration:*` | Web UI, MCP, direct |
| **Output** | Text/tables for terminal | Structured arrays for UI |
| **Context** | CLI only | Web, MCP, CLI (via wrapper) |

---

## Summary

**DO**:
- ✅ Use `/sprints` for web UI and MCP
- ✅ Use `orchestration:sprints` for terminal/scripts
- ✅ Keep them separate in code
- ✅ Wrap unified commands if you need CLI output
- ✅ Document which is which

**DON'T**:
- ❌ Add console commands to `CommandsSeeder.php`
- ❌ Add console commands to `commands` table
- ❌ Mix the two systems
- ❌ Assume they're the same thing
- ❌ Use `orchestration:*` from web UI

---

**Remember**: Console commands are for terminals. Unified commands are for everything else. Keep them separate!
