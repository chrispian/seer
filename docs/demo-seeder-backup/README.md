# Demo Data Seeder System - ARCHIVED

**Date Archived:** 2025-10-10  
**Reason:** Cleanup phase caused accidental database wipes  
**Status:** Extracted for future redesign

---

## What This Was

The Demo Data Seeder system (`database/seeders/Demo/`) was designed to create consistent demo data for development environments:

- Demo vaults (work, personal)
- Demo projects within vaults
- Demo contacts, todos, chats
- Demo types and routing rules

### Architecture

```
DemoDataSeeder (main orchestrator)
├── Cleanup Phase (RUNS FIRST - THIS WAS THE PROBLEM)
│   ├── Delete all demo vaults (with metadata->demo_seed = true)
│   ├── Delete all demo projects (with metadata->demo_seed = true)
│   └── Delete all demo data
└── Seed Phase
    ├── Create demo vaults
    ├── Create demo projects
    └── Create demo data
```

---

## The Problem

### Root Cause
**Before seeding**, `DemoDataSeeder::run()` called `cleanup()` on all sub-seeders in reverse order:

```php
// database/seeders/Demo/DemoDataSeeder.php:44-46
foreach (array_reverse($this->seeders) as $seeder) {
    $seeder->cleanup($context);  // ← DELETES DEMO DATA
}
```

This deleted:
1. All vaults where `metadata->demo_seed = true`
2. All projects where `metadata->demo_seed = true`

### Cascade Effect
Foreign key cascade deletes wiped out:
- ✅ All projects in deleted vaults (`vault_id` cascadeOnDelete)
- ✅ All bookmarks in deleted vaults/projects (`onDelete('cascade')`)
- ⚠️ Chat sessions (set vault_id/project_id to NULL)
- ⚠️ Fragments (set project_id to NULL)

### When It Triggered
**Every time you ran:**
- `php artisan db:seed` (called DemoDataSeeder)
- `php artisan migrate:fresh --seed` (called DemoDataSeeder)

In local/development environments, this ran automatically.

---

## Why It Was Removed

1. **Safety Risk:** Cleanup phase could delete real user data if it had `demo_seed` flag
2. **Unpredictable:** Not obvious that seeding would delete data first
3. **Cascade Deletes:** Foreign key constraints amplified damage
4. **Lost Work:** Orchestration sprints/tasks were wiped during active development

---

## Files Archived

```
docs/demo-seeder-backup/
├── Demo/
│   ├── Contracts/
│   │   └── DemoSubSeeder.php
│   ├── Seeders/
│   │   ├── ChatSeeder.php
│   │   ├── ContactSeeder.php
│   │   ├── ProjectSeeder.php
│   │   ├── TodoSeeder.php
│   │   ├── TypeSeeder.php
│   │   └── UserSeeder.php
│   ├── Support/
│   │   └── DemoSeedContext.php
│   └── DemoDataSeeder.php
├── DemoRoutingDataSeeder.php
└── README.md (this file)
```

---

## Removal Actions Taken

### 1. Removed from DatabaseSeeder
**File:** `database/seeders/DatabaseSeeder.php`

Commented out:
```php
// \Database\Seeders\Demo\DemoDataSeeder::class,
```

### 2. Deleted Original Files
```bash
rm -rf database/seeders/Demo/
rm database/seeders/DemoRoutingDataSeeder.php
```

### 3. Created Orchestration Task
Task created for future redesign: See orchestration system for tracking.

---

## Future Redesign (Orchestration Task)

### Requirements for New Demo System

1. **No Cleanup Phase**
   - Seeders should be idempotent (safe to run multiple times)
   - Use `firstOrCreate` / `updateOrCreate` only
   - Never delete existing data

2. **Explicit Flag Check**
   - Only run when `APP_SEED_DEMO_DATA=true` in `.env`
   - Never run by default
   - Require explicit opt-in

3. **Isolated Demo Data**
   - Use separate database schema or prefix
   - Clear separation from real data
   - Easy to identify and remove

4. **Documentation**
   - Clear warning in seeder file
   - Document what data will be created
   - Document how to reset (without deleting real data)

### Proposed Implementation

```php
// Future: database/seeders/SafeDemoDataSeeder.php
public function run(): void
{
    // Guard: only run if explicitly enabled
    if (!config('app.seed_demo_data', false)) {
        $this->command->warn('Demo data seeding skipped. Enable with APP_SEED_DEMO_DATA=true');
        return;
    }
    
    // Guard: warn user
    if (!$this->command->confirm('This will create demo data. Continue?')) {
        return;
    }
    
    // NO CLEANUP - only create/update
    $this->seedDemoVaults();
    $this->seedDemoProjects();
    $this->seedDemoData();
}

private function seedDemoVaults(): void
{
    // Use updateOrCreate - safe to run multiple times
    Vault::updateOrCreate(
        ['slug' => 'demo-work'],
        ['name' => 'Demo Work Vault', 'metadata' => ['is_demo' => true]]
    );
}
```

---

## Reference: Cascade Delete Schema

From migrations:

```php
// Projects cascade delete when vault deleted
$table->foreignId('vault_id')->constrained()->cascadeOnDelete();

// Bookmarks cascade delete when vault/project deleted
$table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
$table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

// Chat sessions NULL on delete (safer)
$table->foreignId('vault_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
```

---

## Lessons Learned

1. ✅ **Never delete data in seeders** - only create/update
2. ✅ **Be careful with cascade deletes** - they amplify damage
3. ✅ **Use explicit opt-in** for destructive operations
4. ✅ **Test seeders on fresh database** before running on real data
5. ✅ **Document side effects** clearly in code

---

## Safe Migration Practices (Going Forward)

### ✅ DO USE
```bash
php artisan migrate              # Safe - only runs new migrations
php artisan migrate --pretend    # Preview without executing
php artisan migrate:rollback     # Rollback last batch
php artisan db:seed --class=SpecificSeeder  # Run specific seeder
```

### ❌ NEVER USE (Now Blocked)
```bash
php artisan migrate:fresh        # DISABLED - drops all tables
php artisan migrate:refresh      # DISABLED - rolls back all + re-runs
php artisan db:wipe              # DISABLED - drops all tables
```

These commands are now blocked by safe guards in:
- `app/Console/Commands/SafeMigrateFreshCommand.php`
- `app/Console/Commands/SafeMigrateRefreshCommand.php`
- `app/Console/Commands/SafeDbWipeCommand.php`

---

**Archived By:** AI Agent (Sprint Cleanup)  
**Orchestration Task:** Will be created for redesign  
**Priority:** Medium (nice-to-have for development, not critical)
