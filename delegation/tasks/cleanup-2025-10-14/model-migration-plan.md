# Model Migration Plans

## 1. Sprint → OrchestrationSprint Migration

### Field Comparison

**Sprint (legacy):**
- `id` (uuid, primary)
- `code` (string, unique)
- `starts_on` (date, nullable)
- `ends_on` (date, nullable)
- `meta` (json, nullable)
- `created_at`, `updated_at` (timestamps)
- Relations: `sprintItems()`, `workItems()`

**OrchestrationSprint (target):**
- `id` (bigint, auto-increment)
- `sprint_code` (string, unique) ← maps to `code`
- `title` (string) ← **NEW - needs default from code**
- `status` (enum: planning, active, completed, on_hold) ← **NEW - derive from dates**
- `owner` (string, nullable) ← **NEW**
- `hash` (string, 64) ← **NEW - auto-generated**
- `metadata` (json, nullable) ← maps to `meta`
- `file_path` (string, nullable) ← **NEW**
- `created_at`, `updated_at`, `deleted_at` (soft deletes)
- Relations: `tasks()`, `events()`

### Migration Strategy

**Phase 1: Add Missing Fields to OrchestrationSprint**
```php
// New migration: add_sprint_dates_to_orchestration_sprints
Schema::table('orchestration_sprints', function (Blueprint $table) {
    $table->date('starts_on')->nullable()->after('status');
    $table->date('ends_on')->nullable()->after('starts_on');
});
```

**Phase 2: Data Migration**
```php
// Migrate existing Sprint data to OrchestrationSprint
Sprint::all()->each(function ($sprint) {
    OrchestrationSprint::create([
        'sprint_code' => $sprint->code,
        'title' => $sprint->code, // Use code as title initially
        'status' => deriveStatus($sprint), // Active if dates active, else completed
        'starts_on' => $sprint->starts_on,
        'ends_on' => $sprint->ends_on,
        'metadata' => $sprint->meta,
        'owner' => null, // Populate manually if needed
        'file_path' => null,
    ]);
});

function deriveStatus($sprint) {
    $now = now();
    if ($sprint->ends_on && $now->gt($sprint->ends_on)) {
        return 'completed';
    }
    if ($sprint->starts_on && $now->gte($sprint->starts_on)) {
        return 'active';
    }
    return 'planning';
}
```

**Phase 3: Update SprintItem References**
- SprintItem currently uses `sprint_id` (uuid) to Sprint
- Need to migrate to OrchestrationSprint (bigint id)
- Options:
  1. Create new pivot table for OrchestrationSprint ↔ WorkItem
  2. Update SprintItem to reference OrchestrationSprint
  3. Keep SprintItem deprecated, use direct OrchestrationTask.sprint_id

**Recommendation:** Use OrchestrationTask.sprint_id directly (already exists)

**Phase 4: Update Code References**
- Find all `Sprint::` references → change to `OrchestrationSprint::`
- Update relationships in WorkItem model
- Update any controllers/services using Sprint

**Phase 5: Deprecate Legacy Models**
- Move Sprint, SprintItem to backup/
- Keep migrations for historical record
- Add note in DB about legacy tables

---

## 2. WorkItem → OrchestrationTask Migration

### Field Comparison

**WorkItem (legacy):**
- `id` (uuid, primary)
- `type` (string: epic|story|task|bug|spike|decision)
- `parent_id` (uuid, nullable) - hierarchical tasks
- `assignee_type` (string: agent|user)
- `assignee_id` (uuid, nullable)
- `status` (string, default: backlog)
- `priority` (string, nullable)
- `project_id` (uuid, nullable)
- `tags` (json, nullable)
- `state` (json, nullable)
- `metadata` (json, nullable)
- **Additional fields in model:**
  - `delegation_status`, `delegation_context`, `delegation_history`
  - `estimated_hours`, `actual_hours`
  - `agent_content`, `plan_content`, `context_content`, `todo_content`, `summary_content`
  - `pr_url`
  - `completed_at` (datetime)
- Relations: `parent()`, `children()`, `assignments()`, `activities()`, `sprints()`

**OrchestrationTask (target):**
- `id` (bigint, auto-increment)
- `sprint_id` (bigint, nullable, FK to orchestration_sprints)
- `task_code` (string, unique)
- `title` (string) ← **NEW**
- `status` (enum: pending, in_progress, completed, blocked)
- `priority` (enum: P0, P1, P2, P3)
- `phase` (int, nullable) ← **NEW**
- `hash` (string, 64) ← **NEW - auto-generated**
- `metadata` (json, nullable)
- `agent_config` (json, nullable) ← **NEW - for agent assignment**
- `file_path` (string, nullable) ← **NEW**
- `created_at`, `updated_at`, `deleted_at` (soft deletes)
- Relations: `sprint()`, `events()`

### Fields to Add to OrchestrationTask

```php
// New migration: add_work_item_fields_to_orchestration_tasks
Schema::table('orchestration_tasks', function (Blueprint $table) {
    $table->string('type')->default('task')->after('task_code');
    $table->bigInteger('parent_id')->nullable()->after('type');
    $table->string('assignee_type')->nullable()->after('priority');
    $table->uuid('assignee_id')->nullable()->after('assignee_type');
    $table->uuid('project_id')->nullable()->after('assignee_id');
    $table->json('tags')->nullable()->after('metadata');
    $table->json('state')->nullable()->after('tags');
    
    // Delegation fields
    $table->string('delegation_status')->nullable()->after('state');
    $table->json('delegation_context')->nullable()->after('delegation_status');
    $table->json('delegation_history')->nullable()->after('delegation_context');
    
    // Time tracking
    $table->decimal('estimated_hours', 8, 2)->nullable()->after('delegation_history');
    $table->decimal('actual_hours', 8, 2)->nullable()->after('estimated_hours');
    
    // Content fields
    $table->longText('agent_content')->nullable()->after('actual_hours');
    $table->longText('plan_content')->nullable()->after('agent_content');
    $table->longText('context_content')->nullable()->after('plan_content');
    $table->longText('todo_content')->nullable()->after('context_content');
    $table->longText('summary_content')->nullable()->after('todo_content');
    
    // External links
    $table->string('pr_url')->nullable()->after('summary_content');
    
    // Completion tracking
    $table->timestamp('completed_at')->nullable()->after('pr_url');
    
    // Indexes
    $table->index('parent_id');
    $table->index('assignee_id');
    $table->index('project_id');
    $table->index('type');
    $table->index('delegation_status');
    $table->index('completed_at');
    
    // Foreign keys
    $table->foreign('parent_id')->references('id')->on('orchestration_tasks')->nullOnDelete();
});
```

### Migration Strategy

**Phase 1: Add Missing Fields**
- Run migration above to add all WorkItem fields to OrchestrationTask

**Phase 2: Update Model**
```php
// app/Models/OrchestrationTask.php
protected $fillable = [
    // Existing...
    'type', 'parent_id', 'assignee_type', 'assignee_id', 'project_id',
    'tags', 'state', 'delegation_status', 'delegation_context', 'delegation_history',
    'estimated_hours', 'actual_hours', 'agent_content', 'plan_content', 
    'context_content', 'todo_content', 'summary_content', 'pr_url', 'completed_at',
];

protected $casts = [
    // Existing...
    'tags' => 'array',
    'state' => 'array',
    'delegation_context' => 'array',
    'delegation_history' => 'array',
    'estimated_hours' => 'decimal:2',
    'actual_hours' => 'decimal:2',
    'completed_at' => 'datetime',
];

// Add relationships
public function parent(): BelongsTo
{
    return $this->belongsTo(OrchestrationTask::class, 'parent_id');
}

public function children(): HasMany
{
    return $this->hasMany(OrchestrationTask::class, 'parent_id');
}

public function assignments(): HasMany
{
    return $this->hasMany(TaskAssignment::class, 'work_item_id', 'id');
}

public function activities(): HasMany
{
    return $this->hasMany(TaskActivity::class, 'task_id', 'id');
}

public function assignedAgent(): BelongsTo
{
    return $this->belongsTo(AgentProfile::class, 'assignee_id');
}

public function assignedUser(): BelongsTo
{
    return $this->belongsTo(User::class, 'assignee_id')
        ->where('assignee_type', 'user');
}

// Add scopes
public function scopeAssignedToAgents($query)
{
    return $query->where('assignee_type', 'agent');
}

public function scopeByDelegationStatus($query, string $status)
{
    return $query->where('delegation_status', $status);
}
```

**Phase 3: Data Migration**
```php
// Migrate WorkItem data to OrchestrationTask
WorkItem::all()->each(function ($item) {
    OrchestrationTask::create([
        'task_code' => $item->id, // Use UUID as task_code initially
        'title' => extractTitle($item), // Extract from metadata or content
        'type' => $item->type,
        'parent_id' => migrateParentId($item->parent_id), // Map to new parent
        'status' => mapStatus($item->status),
        'priority' => mapPriority($item->priority),
        'assignee_type' => $item->assignee_type,
        'assignee_id' => $item->assignee_id,
        'project_id' => $item->project_id,
        'tags' => $item->tags,
        'state' => $item->state,
        'metadata' => $item->metadata,
        'delegation_status' => $item->delegation_status,
        'delegation_context' => $item->delegation_context,
        'delegation_history' => $item->delegation_history,
        'estimated_hours' => $item->estimated_hours,
        'actual_hours' => $item->actual_hours,
        'agent_content' => $item->agent_content,
        'plan_content' => $item->plan_content,
        'context_content' => $item->context_content,
        'todo_content' => $item->todo_content,
        'summary_content' => $item->summary_content,
        'pr_url' => $item->pr_url,
        'completed_at' => $item->completed_at,
        'created_at' => $item->created_at,
        'updated_at' => $item->updated_at,
    ]);
});
```

**Phase 4: Update References**
- Update TaskAssignment to use orchestration_tasks
- Update TaskActivity to use orchestration_tasks
- Update all services using WorkItem
- Update controllers
- Update tests

**Phase 5: Deprecate Legacy**
- Move WorkItem to backup/
- Keep migration for historical record

---

## 3. SeerLog Removal Plan

### Current State
- `SeerLog` model points to `seer_logs` table
- Has foreign key to `fragments` table
- Used in: FragmentTest.php, SeerLogController.php

### Issue
Fragments are for content-addressable storage, not application logs. Mixing concerns.

### Strategy
1. Review SeerLogController - what functionality?
2. Check if any production data in seer_logs table
3. If unused, drop table and remove model
4. If used, migrate to TelemetryEvent or create proper application_logs table

**Action:** Investigate SeerLogController usage first

---

## 4. Article Model Removal

### Current State
- Only used in: `ObsidianFragmentPipelineTest.php`
- Not used in production code

### Strategy
1. Check if test can use Fragment model instead
2. Update test to use Fragment
3. Move Article to backup/
4. Remove any article-related migrations if exist

**Action:** Update test, then remove model

---

## Implementation Order

1. ✅ Document plans (this file)
2. Add date fields to OrchestrationSprint
3. Add WorkItem fields to OrchestrationTask
4. Update OrchestrationTask model with relationships
5. Test models work correctly
6. Create data migration scripts (don't run yet - manual decision)
7. Update code references (Sprint → OrchestrationSprint, WorkItem → OrchestrationTask)
8. Create deprecation notes for legacy tables
9. Move legacy models to backup/
10. Test application thoroughly

---

## Notes

- **DO NOT drop legacy tables yet** - keep for data safety
- **Manual review required** before running data migrations
- **Test thoroughly** after each phase
- **Update documentation** after completion
