# SPRINT-51: Quick Start Guide

## Sprint Overview
**Code:** SPRINT-51  
**Title:** Artifacts Store + Postmaster + Agent INIT Phase  
**Duration:** Oct 7-14, 2025 (7 days)  
**Total Tasks:** 18 (T-ART-01 through T-ART-18)

## Get Started Immediately (No Dependencies)

These 3 tasks can start RIGHT NOW in parallel:

### 1. T-ART-01-CONFIG (1 hour)
```bash
# Edit config/orchestration.php
# Add these new config keys:

'artifacts' => [
    'disk' => env('FE_ARTIFACTS_DISK', 'local'),
    'root' => env('FE_ARTIFACTS_ROOT', 'orchestration/artifacts'),
],

'messaging' => [
    'retention_days' => env('FE_MSG_RETENTION_DAYS', 365),
],

# Edit .env.example
# Add:
FE_ARTIFACTS_DISK=local
FE_ARTIFACTS_ROOT=orchestration/artifacts
FE_MSG_RETENTION_DAYS=365
```

### 2. T-ART-03-MSG-MIGRATION (1-2 hours)
```bash
php artisan make:migration create_messages_table
```

Schema:
- id (uuid, primary)
- stream (string, indexed)
- type (string)
- task_id (uuid, nullable, foreign → work_items)
- project_id (uuid, nullable)
- to_agent_id (uuid, nullable, foreign → agent_profiles)
- from_agent_id (uuid, nullable, foreign → agent_profiles)
- headers (jsonb)
- envelope (jsonb)
- read_at (timestamp, nullable, indexed)
- created_at, updated_at (indexed)

### 3. T-ART-04-ART-MIGRATION (1-2 hours)
```bash
php artisan make:migration create_artifacts_table
```

Schema:
- id (uuid, primary)
- task_id (uuid, foreign → work_items, indexed)
- hash (string, 64 chars, indexed)
- filename (string)
- mime_type (string, nullable)
- size_bytes (bigint)
- metadata (jsonb)
- fe_uri (string)
- storage_path (string)
- created_at, updated_at
- UNIQUE(task_id, filename)

## Dependency Chain

After completing the 3 foundation tasks above, follow this order:

```
Phase 1 (DONE) ─┬─> T-ART-02-CAS (3-4h)
                ├─> T-ART-05-MSG-MODEL (2h)
                └─> T-ART-06-ART-MODEL (2h)
                         │
Phase 2 ────────┬────────┴────────────┬────────────────────┐
                │                     │                    │
                ▼                     ▼                    ▼
        T-ART-07-POSTMASTER    T-ART-09-INBOX-API  T-ART-10-ARTIFACTS-API
             (4-5h)                 (4-5h)              (3-4h)
                │                     │                    │
                ├─> T-ART-08-CMD      │                    │
                │       (2h)           │                    │
                │                      │                    │
Phase 3 ────────┴──────────────────────┴────────────────────┘
                │                      │
                ├─> T-ART-12-MEMORY    ├─> T-ART-11-SLASH-COMMANDS
                │       (3-4h)         │        (3-4h)
                │          │           │
                │          ▼           │
                ├─> T-ART-13-INIT      │
                │       (5-6h)         │
                │                      │
                └─> T-ART-14-REDACTION │
                        (3-4h)         │
                                       │
Phase 4 ───────────────────────────────┘
                │
                ▼
        T-ART-15-TESTS (6-8h)
                │
                ▼
        T-ART-18-INTEGRATION (4-5h)

        [Parallel: T-ART-16-DOCS, T-ART-17-MCP-TOOLS]
```

## Key File Locations

**Models:**
- `app/Models/Message.php` (T-ART-05)
- `app/Models/Artifact.php` (T-ART-06)

**Services:**
- `app/Services/Orchestration/Artifacts/ContentStore.php` (T-ART-02)
- `app/Services/Orchestration/MemoryService.php` (T-ART-12)
- `app/Services/Orchestration/AgentInitService.php` (T-ART-13)

**Jobs:**
- `app/Jobs/Postmaster/ProcessParcel.php` (T-ART-07)

**Commands:**
- `app/Console/Commands/PostmasterRun.php` (T-ART-08)

**Controllers:**
- `app/Http/Controllers/Orchestration/InboxController.php` (T-ART-09)
- `app/Http/Controllers/Orchestration/ArtifactsController.php` (T-ART-10)

**Tests:**
- `tests/Unit/Services/Orchestration/ContentStoreTest.php`
- `tests/Feature/Orchestration/InboxApiTest.php`
- `tests/Feature/Orchestration/ArtifactsApiTest.php`

**Documentation:**
- `docs/orchestration/postmaster-and-init.md` (T-ART-16)

## Daily Progress Targets

**Day 1:** Complete T-ART-01, T-ART-03, T-ART-04 + start T-ART-02, T-ART-05, T-ART-06  
**Day 2:** Complete Phase 2 (models) + start T-ART-07, T-ART-09, T-ART-10  
**Day 3:** Complete Postmaster + APIs  
**Day 4:** Complete T-ART-11, T-ART-12, start T-ART-13  
**Day 5:** Complete T-ART-13, T-ART-14  
**Day 6:** Complete T-ART-15 (tests)  
**Day 7:** Complete T-ART-18 (integration) + T-ART-16/T-ART-17

## API Endpoints to Implement

### Inbox API (T-ART-09)
```php
POST   /api/agents/{id}/inbox              # Send message
GET    /api/agents/{id}/inbox?status=unread # List messages
POST   /api/messages/{id}/read             # Mark as read
POST   /api/projects/{id}/broadcast        # Broadcast message
```

### Artifacts API (T-ART-10)
```php
POST   /api/tasks/{id}/artifacts           # Create artifact (internal only)
GET    /api/tasks/{id}/artifacts           # List task artifacts
GET    /api/artifacts/{id}/download        # Download artifact
```

## Slash Commands (T-ART-11)
```bash
/check-messages {agent_id}           # Check inbox
/ack {message_id}                    # Mark message read
/pull-artifacts {task_id}            # List/download artifacts
/handoff {target} --with {pack|auto} # Handoff task
```

## Testing Checklist (T-ART-15)

**Unit Tests:**
- [ ] ContentStore put/get/exists
- [ ] Message model scopes
- [ ] Artifact model accessors
- [ ] SecretRedactor patterns
- [ ] MemoryService key lifecycle
- [ ] AgentInitService all steps

**Feature Tests:**
- [ ] POST /api/agents/{id}/inbox
- [ ] GET /api/agents/{id}/inbox
- [ ] POST /api/messages/{id}/read
- [ ] POST /api/projects/{id}/broadcast
- [ ] POST /api/tasks/{id}/artifacts
- [ ] GET /api/tasks/{id}/artifacts
- [ ] GET /api/artifacts/{id}/download

**Integration Tests:**
- [ ] Full parcel workflow: send → process → deliver → receive
- [ ] Large file (>5MB) handling
- [ ] Secret redaction
- [ ] INIT phase execution
- [ ] Memory compaction on completion

## Common Patterns from Codebase

**Model Pattern:**
```php
// Follow Sprint, WorkItem, AgentProfile patterns
use HasUuids;
public $incrementing = false;
protected $keyType = 'string';
protected $casts = ['metadata' => 'array'];
```

**Service Pattern:**
```php
// Follow TaskOrchestrationService pattern
public function resolve{Model}(string|Model $identifier): Model
// Use DB::transaction for multi-step operations
// Return fresh() models after updates
```

**Controller Pattern:**
```php
// Follow existing API controllers
// Use FormRequest validation
// Return consistent JSON responses
// Follow RESTful conventions
```

## MCP Tool Usage

```bash
# List tasks
orchestration_tasks_list --filter sprint_code:SPRINT-51

# Get task detail
orchestration_tasks_detail --task T-ART-01-CONFIG

# Assign task
orchestration_tasks_assign --task T-ART-01-CONFIG --agent A-fe-builder

# Update status
orchestration_tasks_status --task T-ART-01-CONFIG --status in_progress

# Check sprint progress
orchestration_sprints_detail --sprint SPRINT-51
```

## Critical Reminders

🚨 **NEVER commit secrets** - T-ART-14 prevents this  
📁 **Keep context OUT of repos** - Use fe:// URIs only  
🔒 **Messages never delete** - Use read markers  
⚡ **Parallelize aggressively** - Follow dependency chain  
🧪 **Test everything** - Especially secret redaction  
📝 **Document as you go** - Update T-ART-16 continuously

## Quick Commands

```bash
# Create migrations
php artisan make:migration create_messages_table
php artisan make:migration create_artifacts_table

# Create models
php artisan make:model Message
php artisan make:model Artifact

# Create controllers
php artisan make:controller Orchestration/InboxController
php artisan make:controller Orchestration/ArtifactsController

# Create jobs
php artisan make:job Postmaster/ProcessParcel

# Create tests
php artisan make:test Orchestration/InboxApiTest
php artisan make:test Orchestration/ContentStoreTest --unit

# Run tests
composer test
composer test:feature -- --filter=Orchestration
```

## Questions? Check These Docs

- Full sprint plan: `delegation/backlog/SPRINT-51-ARTIFACTS-POSTMASTER-INIT.md`
- Context pack: Task T-ARTIFACTS-POSTMASTER-INIT (metadata)
- Orchestration patterns: `app/Services/*OrchestrationService.php`
- Existing models: `app/Models/{Sprint,WorkItem,AgentProfile}.php`

---
**Start with T-ART-01, T-ART-03, T-ART-04 NOW!**
