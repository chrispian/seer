# Migration Sync Solution

**Date**: 2025-10-09  
**Problem**: Database has tables/columns but migrations tracking table doesn't reflect this  
**Cause**: Database restore included tables but migrations table from earlier point in time

---

## Problem Analysis

### Current State

**Database Tables**: ✅ Exist with correct schema
- `fragment_type_registry` has ALL 28 columns (including new ones)
- `telemetry_*` tables all exist
- `sprints`, `work_items`, `agent_profiles` all exist
- All expected tables are present

**Migrations Tracking Table**: ❌ Out of sync
- Shows only 44 migrations completed (batch 7)
- Latest migration: `2025_09_14_000000_create_vault_routing_rules_table`
- Missing ~25+ migrations from tracking (Oct 5-9, 2025)

**Migration Files**: ✅ Exist
- All files present in `database/migrations/`
- Including the ones database doesn't think have run

**Result**: 
```bash
php artisan migrate
# ERROR: Duplicate table "telemetry_events" already exists
```

Laravel tries to create tables that already exist because migrations table doesn't know they've run.

---

## Solution Options

### Option 1: Mark Migrations as Run (RECOMMENDED) ⭐

Manually insert migration records into the `migrations` table so Laravel knows they've been run.

**Advantages:**
- ✅ Safe - doesn't modify any tables
- ✅ Fast - just inserts records
- ✅ Preserves all existing data
- ✅ Future migrations will work normally

**Steps:**

```bash
# 1. Create a script to mark migrations as run
php artisan tinker
```

Then in tinker:
```php
// Get the next batch number
$nextBatch = DB::table('migrations')->max('batch') + 1;

// List of migrations that exist in DB but aren't tracked
$missingMigrations = [
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
    '2024_10_04_create_telemetry_tables',
    '20251004151938_create_saved_queries_table',
    '20251004151939_create_prompt_registry_table',
    '20251004151940_create_work_items_tables',
    '20251004151941_create_sprints_tables',
    '20251004151942_create_agent_memory_tables',
    // ... (see full list below)
];

// Insert them
foreach ($missingMigrations as $migration) {
    DB::table('migrations')->insert([
        'migration' => $migration,
        'batch' => $nextBatch
    ]);
    echo "✓ Marked: $migration\n";
}

echo "\n✅ Done! " . count($missingMigrations) . " migrations marked as run.\n";
exit;
```

### Option 2: Fresh Migration with Data Export/Import

**NOT RECOMMENDED** - Too risky with existing data

### Option 3: Run Migrations with --force and catch errors

**NOT RECOMMENDED** - Will fail on first duplicate table

---

## Full Migration List to Mark

Based on `php artisan migrate:status`, these migrations need to be marked as run:

```php
$missingMigrations = [
    // Core Laravel tables (if they exist)
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
    
    // Telemetry system
    '2024_10_04_create_telemetry_tables',
    
    // Orchestration system
    '20251004151938_create_saved_queries_table',
    '20251004151939_create_prompt_registry_table',
    '20251004151940_create_work_items_tables',
    '20251004151941_create_sprints_tables',
    '20251004151942_create_agent_memory_tables',
    
    // Ingestion sources
    '2025_01_06_000001_seed_obsidian_source',
    '2025_10_06_230300_seed_chatgpt_sources',
    '2025_10_07_000100_seed_readwise_source',
    
    // Agent system
    '2025_10_07_001827_create_agents_table',
    '2025_10_07_011023_add_avatar_to_agents_table',
    
    // Task activities
    '2025_10_07_055556_create_task_activities_table',
    
    // Fragment type registry enhancements
    '2025_10_07_063016_add_type_management_columns_to_fragment_type_registry_table',
    '2025_10_07_073618_add_ui_component_columns_to_fragment_type_registry_table',
    '2025_10_07_074624_add_detail_component_to_fragment_type_registry_table',
    
    // Documentation
    '2025_10_07_063956_create_documentation_table',
    
    // Tool definitions
    '2025_10_09_031557_create_tool_definitions_table',
    
    // Work items enhancements
    '2025_10_05_180555_create_task_assignments_table',
    '2025_10_05_180609_add_foreign_keys_to_work_items',
    '2025_10_05_215240_add_content_fields_to_work_items_table',
    '2025_10_05_220836_add_pr_url_to_work_items_table',
    
    // Agent logs
    '2025_10_05_222036_create_agent_logs_table',
    '2025_10_06_001038_add_codex_to_agent_logs_source_type_constraint',
    '2025_10_06_002123_add_claude_projects_to_agent_logs_source_type_constraint',
    
    // Messages and artifacts
    '2025_10_06_210929_create_messages_table',
    '2025_10_06_211016_create_orchestration_artifacts_table',
];
```

---

## Automated Solution Script

Create this as a one-time artisan command:

```php
// app/Console/Commands/SyncMigrationsCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMigrationsCommand extends Command
{
    protected $signature = 'migrations:sync {--dry-run : Show what would be done without doing it}';
    protected $description = 'Sync migrations table with actual database state';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get current max batch
        $nextBatch = DB::table('migrations')->max('batch') + 1;
        $this->info("Next batch number: {$nextBatch}");

        // List of migrations to mark as run
        $missingMigrations = [
            '0001_01_01_000000_create_users_table',
            '0001_01_01_000001_create_cache_table',
            '0001_01_01_000002_create_jobs_table',
            '2024_10_04_create_telemetry_tables',
            '20251004151938_create_saved_queries_table',
            '20251004151939_create_prompt_registry_table',
            '20251004151940_create_work_items_tables',
            '20251004151941_create_sprints_tables',
            '20251004151942_create_agent_memory_tables',
            '2025_01_06_000001_seed_obsidian_source',
            '2025_10_05_180555_create_task_assignments_table',
            '2025_10_05_180609_add_foreign_keys_to_work_items',
            '2025_10_05_215240_add_content_fields_to_work_items_table',
            '2025_10_05_220836_add_pr_url_to_work_items_table',
            '2025_10_05_222036_create_agent_logs_table',
            '2025_10_06_001038_add_codex_to_agent_logs_source_type_constraint',
            '2025_10_06_002123_add_claude_projects_to_agent_logs_source_type_constraint',
            '2025_10_06_210929_create_messages_table',
            '2025_10_06_211016_create_orchestration_artifacts_table',
            '2025_10_06_230300_seed_chatgpt_sources',
            '2025_10_07_000100_seed_readwise_source',
            '2025_10_07_001827_create_agents_table',
            '2025_10_07_011023_add_avatar_to_agents_table',
            '2025_10_07_055556_create_task_activities_table',
            '2025_10_07_063016_add_type_management_columns_to_fragment_type_registry_table',
            '2025_10_07_063956_create_documentation_table',
            '2025_10_07_073618_add_ui_component_columns_to_fragment_type_registry_table',
            '2025_10_07_074624_add_detail_component_to_fragment_type_registry_table',
            '2025_10_09_031557_create_tool_definitions_table',
        ];

        $this->info("\n📋 Migrations to mark as run: " . count($missingMigrations));

        foreach ($missingMigrations as $migration) {
            // Check if already marked
            $exists = DB::table('migrations')
                ->where('migration', $migration)
                ->exists();

            if ($exists) {
                $this->line("  ⏭️  Skip: {$migration} (already marked)");
                continue;
            }

            if (!$dryRun) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $nextBatch
                ]);
                $this->info("  ✅ Marked: {$migration}");
            } else {
                $this->line("  📝 Would mark: {$migration}");
            }
        }

        if ($dryRun) {
            $this->info("\n🔍 Dry run complete. Run without --dry-run to apply changes.");
        } else {
            $this->info("\n✅ Migration sync complete!");
            $this->info("You can now run 'php artisan migrate' for any future migrations.");
        }

        return 0;
    }
}
```

---

## Execution Steps

### Step 1: Create the sync command

```bash
# Create the command file
touch app/Console/Commands/SyncMigrationsCommand.php

# Paste the code above into the file
```

### Step 2: Test with dry run

```bash
php artisan migrations:sync --dry-run
# This will show what would be done without making changes
```

### Step 3: Execute the sync

```bash
php artisan migrations:sync
# This will mark all migrations as run
```

### Step 4: Verify

```bash
php artisan migrate:status
# Should show all migrations as "Ran"

php artisan migrate
# Should say "Nothing to migrate"
```

---

## Why This Happened

**Database Restore Scenario:**

1. **Before restore**: Database had all tables, migrations tracking was up to date
2. **Database error occurred**: Some corruption or issue
3. **Restore executed**: 
   - ✅ Tables restored from recent backup (Oct 7+)
   - ❌ Migrations table restored from earlier backup (Sept 14)
4. **Result**: Tables exist but migrations tracking is stale

**This is a common issue when:**
- Restoring from backup
- Cloning a database
- Manually copying tables between environments
- Using database replication with lag

---

## Prevention for Future

1. **Always backup migrations table with data**
   - Include it in backup scripts
   - Keep it in sync with schema backups

2. **Use database dump/restore tools that preserve all tables**
   ```bash
   # PostgreSQL example
   pg_dump -Fc fragments > backup.dump
   pg_restore -d fragments backup.dump
   ```

3. **Version control schema state**
   - Document last known good migration state
   - Keep migration state files

---

## Verification After Sync

Run these checks to confirm everything works:

```bash
# 1. Check migration status
php artisan migrate:status

# 2. Try to migrate (should say nothing to migrate)
php artisan migrate

# 3. Test a command that uses type registry
# In chat UI or via API
/sprints
/tasks
/agents

# 4. Check fragment type registry has data
php artisan tinker --execute='echo DB::table("fragment_type_registry")->count() . " types\n";'
```

---

## Expected Results After Fix

- ✅ `php artisan migrate:status` shows all migrations as "Ran"
- ✅ `/sprints` command shows 19 sprints
- ✅ `/tasks` command shows tasks (after filter is fixed)
- ✅ `/agents` command shows 13 agent profiles
- ✅ All modals render with data
- ✅ No more "table already exists" errors

---

## Next Steps After Sync

1. Fix TaskListCommand filter (separate issue):
   ```php
   // Remove the else clause that filters by sprint_code
   // Let it show ALL tasks when no filter specified
   ```

2. Seed fragment_type_registry if empty:
   ```bash
   php artisan db:seed --class=SystemTypesSeeder
   ```

3. Test all slash commands in UI

---

## Status

**Ready to Execute**: Solution documented, command code provided, steps clear.

**Risk Level**: ⚠️ LOW
- Only inserts records to migrations table
- No schema changes
- No data changes
- Reversible (can delete the batch if needed)

**Recommendation**: Execute with dry-run first, then apply.
