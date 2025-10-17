# Orchestration Package - Complete File Inventory

This document provides a detailed listing of every file that needs to be migrated to the orchestration package.

---

## Models (5 files)

### Location: `app/Models/`

| File | Lines | Description | Key Relationships |
|------|-------|-------------|-------------------|
| `OrchestrationTask.php` | ~180 | Task/work item model | BelongsTo: Sprint, Agent, Project<br>HasMany: Events, Artifacts |
| `OrchestrationSprint.php` | ~85 | Sprint/iteration model | HasMany: Tasks, Events |
| `OrchestrationEvent.php` | ~95 | Event sourcing log | MorphTo: Entity (task/sprint) |
| `OrchestrationArtifact.php` | ~70 | File artifact model | BelongsTo: Task |
| `OrchestrationBug.php` | ~60 | Bug tracking model | BelongsTo: Task |

**Package Path:** `src/Models/`

---

## Services (16 files)

### Core Services (4 files)
**Location:** `app/Services/`

| File | Lines | Description | Key Dependencies |
|------|-------|-------------|------------------|
| `TaskOrchestrationService.php` | ~450 | Task lifecycle management | OrchestrationTask, EventService, HashService |
| `SprintOrchestrationService.php` | ~380 | Sprint lifecycle management | OrchestrationSprint, EventService |
| `AgentOrchestrationService.php` | ~320 | Agent coordination | AgentProfile, SessionService |
| `PromptOrchestrator.php` | ~280 | Prompt template management | TemplateService |

### Specialized Services (12 files)
**Location:** `app/Services/Orchestration/`

| File | Lines | Description | Key Dependencies |
|------|-------|-------------|------------------|
| `OrchestrationEventService.php` | ~420 | Event sourcing/audit | OrchestrationEvent, Queue |
| `OrchestrationSessionService.php` | ~380 | Session state management | OrchestrationTask, MemoryService, workflow.yaml |
| `OrchestrationHashService.php` | ~150 | Content hashing for change detection | - |
| `OrchestrationTemplateService.php` | ~250 | Template rendering (Blade/Twig) | Storage, View |
| `OrchestrationBugService.php` | ~180 | Bug logging and tracking | OrchestrationBug |
| `OrchestrationPMToolsService.php` | ~320 | PM tool integrations (ADR, reports) | TemplateService, EventService |
| `OrchestrationAutomationService.php` | ~280 | Workflow automation | EventService, SessionService |
| `OrchestrationReplayService.php` | ~220 | Event replay functionality | OrchestrationEvent |
| `OrchestrationContextBrokerService.php` | ~310 | Context distribution to agents | Fragment, Memory |
| `OrchestrationContextSearchService.php` | ~340 | Context search (vector/fulltext) | OrchestrationEvent, VectorSearch |
| `OrchestrationGitService.php` | ~420 | Git integration (commit tracking) | Process, OrchestrationTask |
| `OrchestrationFileSyncService.php` | ~290 | File synchronization | Storage, HashService |
| `MemoryService.php` | ~260 | Memory/context storage | Fragment, VectorStore |

**Package Path:** `src/Services/`

---

## Controllers (10 files)

### API Controllers (6 files)
**Location:** `app/Http/Controllers/Api/`

| File | Lines | Description | Endpoints |
|------|-------|-------------|-----------|
| `OrchestrationSprintController.php` | ~380 | Sprint CRUD + templates | 7 endpoints |
| `OrchestrationTaskController.php` | ~420 | Task CRUD + templates | 6 endpoints |
| `OrchestrationEventController.php` | ~450 | Event queries, timeline, replay | 7 endpoints |
| `OrchestrationAgentController.php` | ~280 | Agent init, context, activity | 3 endpoints |
| `OrchestrationTemplateController.php` | ~180 | Template listing | 2 endpoints |
| `OrchestrationPMToolsController.php` | ~320 | ADR, bug reports, status | 4 endpoints |

### Legacy Controllers (4 files)
**Location:** `app/Http/Controllers/Orchestration/`

| File | Lines | Description | Endpoints |
|------|-------|-------------|-----------|
| `MessagingController.php` | ~240 | Agent inbox/messaging | 4 endpoints |
| `ArtifactsController.php` | ~220 | Artifact upload/download | 3 endpoints |
| `TaskController.php` | ~280 | Task field updates | 3 endpoints |
| `TaskActivityController.php` | ~180 | Task activity log | 3 endpoints |

**Package Path:** `src/Http/Controllers/`

---

## Console Commands (31 files)

**Location:** `app/Console/Commands/`

### Session Management (2 files)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationSessionStart.php` | ~180 | `orchestration:session:start` | Start agent session for task |
| `OrchestrationSessionEnd.php` | ~160 | `orchestration:session:end` | End agent session |

### Sprint Management (5 files)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationSprintsCommand.php` | ~220 | `orchestration:sprints` | List all sprints |
| `OrchestrationSprintDetailCommand.php` | ~180 | `orchestration:sprint:detail` | Show sprint details |
| `OrchestrationSprintSaveCommand.php` | ~240 | `orchestration:sprint:save` | Create/update sprint |
| `OrchestrationSprintStatusCommand.php` | ~160 | `orchestration:sprint:status` | Update sprint status |
| `OrchestrationSprintTasksAttachCommand.php` | ~190 | `orchestration:sprint:attach-tasks` | Attach tasks to sprint |

### Task Management (6 files)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationTasksCommand.php` | ~260 | `orchestration:tasks` | List all tasks |
| `OrchestrationTaskDetailCommand.php` | ~200 | `orchestration:task:detail` | Show task details |
| `OrchestrationTaskSaveCommand.php` | ~320 | `orchestration:task:save` | Create/update task |
| `OrchestrationTaskStatusCommand.php` | ~180 | `orchestration:task:status` | Update task status |
| `OrchestrationTaskStatus.php` | ~170 | `orchestration:task-status` | Legacy status command |
| `OrchestrationTaskAssignCommand.php` | ~160 | `orchestration:task:assign` | Assign task to agent |

### Agent Management (4 files)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationAgentsCommand.php` | ~220 | `orchestration:agents` | List all agents |
| `OrchestrationAgentDetailCommand.php` | ~180 | `orchestration:agent:detail` | Show agent details |
| `OrchestrationAgentSaveCommand.php` | ~240 | `orchestration:agent:save` | Create/update agent |
| `OrchestrationAgentStatusCommand.php` | ~160 | `orchestration:agent:status` | Update agent status |

### Workflow & Automation (2 files)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationPhaseComplete.php` | ~280 | `orchestration:phase:complete` | Complete workflow phase |
| `OrchestrationStatusReport.php` | ~320 | `orchestration:status-report` | Generate status report |

### PM Tools (3 files)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationADRGenerate.php` | ~240 | `orchestration:adr:generate` | Generate ADR document |
| `OrchestrationBugLog.php` | ~180 | `orchestration:bug:log` | Log a bug |
| `OrchestrationBugReport.php` | ~220 | `orchestration:bug:report` | Generate bug report |

### Git Integration (1 file)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationGitLinkPr.php` | ~160 | `orchestration:git:link-pr` | Link PR to task |

### Context & Search (1 file)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationContextSearch.php` | ~280 | `orchestration:context:search` | Search context/events |

### Maintenance (1 file)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `ArchiveOrchestrationEvents.php` | ~140 | `orchestration:events:archive` | Archive old events |

### Utility (2 files)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `OrchestrationMcp.php` | ~380 | `orchestration:mcp` | MCP server integration |
| `MakeCommandClass.php` | ~220 | `make:command-class` | Generate command classes |

### Migration Commands (4 files - Keep in main app)

| File | Lines | Command Signature | Description |
|------|-------|-------------------|-------------|
| `MigrateSprintsToOrchestration.php` | ~280 | `migrate:sprints-to-orchestration` | Migrate legacy sprints |
| `MigrateWorkItemsToOrchestration.php` | ~320 | `migrate:work-items-to-orchestration` | Migrate legacy work items |
| `DelegationImportCommand.php` | ~240 | `delegation:import` | Import delegation files |
| `ImportDelegationContent.php` | ~260 | `import:delegation-content` | Import delegation content |

**Package Path:** `src/Console/Commands/`  
**Note:** Migration commands should remain in main app as one-time utilities

---

## MCP Tools (28 files)

**Location:** `app/Tools/Orchestration/`

### Agent Tools (4 files)

| File | Lines | Tool Name | Description |
|------|-------|-----------|-------------|
| `AgentsListTool.php` | ~240 | `orchestration_agents_list` | List all agents with filters |
| `AgentDetailTool.php` | ~120 | `orchestration_agents_detail` | Get agent details |
| `AgentSaveTool.php` | ~220 | `orchestration_agents_save` | Create/update agent |
| `AgentStatusTool.php` | ~130 | `orchestration_agents_status` | Update agent status |

### Task Tools (5 files)

| File | Lines | Tool Name | Description |
|------|-------|-----------|-------------|
| `TasksListTool.php` | ~280 | `orchestration_tasks_list` | List tasks with filters |
| `TaskDetailTool.php` | ~140 | `orchestration_tasks_detail` | Get task details |
| `TaskSaveTool.php` | ~320 | `orchestration_tasks_save` | Create/update task |
| `TaskStatusTool.php` | ~240 | `orchestration_tasks_status` | Update task status |
| `TaskAssignTool.php` | ~180 | `orchestration_tasks_assign` | Assign task to agent |

### Sprint Tools (5 files)

| File | Lines | Tool Name | Description |
|------|-------|-----------|-------------|
| `SprintsListTool.php` | ~200 | `orchestration_sprints_list` | List sprints with filters |
| `SprintDetailTool.php` | ~140 | `orchestration_sprints_detail` | Get sprint details |
| `SprintSaveTool.php` | ~210 | `orchestration_sprints_save` | Create/update sprint |
| `SprintStatusTool.php` | ~150 | `orchestration_sprints_status` | Update sprint status |
| `SprintTasksAttachTool.php` | ~170 | `orchestration_sprints_attach_tasks` | Attach tasks to sprint |

### Session Management Tools (7 files)

| File | Lines | Tool Name | Description |
|------|-------|-----------|-------------|
| `SessionStartTool.php` | ~200 | `orchestration_session_start` | Start work session |
| `SessionEndTool.php` | ~210 | `orchestration_session_end` | End work session |
| `SessionStatusTool.php` | ~260 | `orchestration_session_status` | Get session status |
| `SessionTaskActivateTool.php` | ~240 | `orchestration_session_task_activate` | Activate task in session |
| `SessionTaskDeactivateTool.php` | ~220 | `orchestration_session_task_deactivate` | Deactivate task |
| `SessionTaskUpdateTool.php` | ~240 | `orchestration_session_task_update` | Update task in session |
| `SessionTaskNoteTool.php` | ~200 | `orchestration_session_task_note` | Add note to task |
| `SessionSprintActivateTool.php` | ~210 | `orchestration_session_sprint_activate` | Activate sprint |

### Messaging Tools (3 files)

| File | Lines | Tool Name | Description |
|------|-------|-----------|-------------|
| `MessagesCheckTool.php` | ~210 | `orchestration_messages_check` | Check inbox messages |
| `MessageAckTool.php` | ~130 | `orchestration_message_ack` | Acknowledge message |
| `HandoffTool.php` | ~280 | `orchestration_handoff` | Hand off to another agent |

### Artifact Tools (1 file)

| File | Lines | Tool Name | Description |
|------|-------|-----------|-------------|
| `ArtifactsPullTool.php` | ~220 | `orchestration_artifacts_pull` | Pull task artifacts |

### Shared Concerns (1 file)

| File | Lines | Description |
|------|-------|-------------|
| `Concerns/NormalisesFilters.php` | ~160 | Filter normalization trait |

**Package Path:** `src/Tools/`

---

## Database Migrations (10 files)

**Location:** `database/migrations/`

| File | Description | Tables/Columns |
|------|-------------|----------------|
| `2025_10_05_180542_enhance_work_items_for_orchestration.php` | Add orchestration fields to work_items | Adds: orchestration metadata columns |
| `2025_10_06_211016_create_orchestration_artifacts_table.php` | Create artifacts table | Table: `orchestration_artifacts` |
| `2025_10_12_233110_create_orchestration_sprints_table.php` | Create sprints table | Table: `orchestration_sprints` |
| `2025_10_12_233110_create_orchestration_tasks_table.php` | Create tasks table | Table: `orchestration_tasks` |
| `2025_10_12_233111_create_orchestration_events_table.php` | Create events table | Table: `orchestration_events` |
| `2025_10_13_003025_add_archived_at_to_orchestration_events_table.php` | Add archival support | Adds: `archived_at` column |
| `2025_10_13_232030_create_orchestration_bugs_table.php` | Create bugs table | Table: `orchestration_bugs` |
| `2025_10_14_205144_add_date_fields_to_orchestration_sprints_table.php` | Add sprint dates | Adds: `starts_on`, `ends_on` |
| `2025_10_14_205201_add_work_item_fields_to_orchestration_tasks_table.php` | Add work item fields | Adds: delegation fields |
| `2025_10_15_035307_add_description_to_orchestration_tasks_table.php` | Add task description | Adds: `description` column |

**Package Path:** `database/migrations/`

---

## Database Factories (2 files)

**Location:** `database/factories/`

| File | Model | Purpose |
|------|-------|---------|
| `OrchestrationArtifactFactory.php` | OrchestrationArtifact | Generate test artifacts |
| `OrchestrationSprintFactory.php` | OrchestrationSprint | Generate test sprints |

**Package Path:** `database/factories/`

---

## Configuration (1 file)

**Location:** `config/`

| File | Sections | Description |
|------|----------|-------------|
| `orchestration.php` | 11 sections | Complete orchestration configuration:<br>- Tool toggles (27 tools)<br>- Priority tools<br>- Tool categories<br>- Model bindings<br>- Service bindings<br>- Artifact storage<br>- Message retention<br>- Secret redaction<br>- Search config<br>- Workflow behavior<br>- Git integration |

**Package Path:** `config/`

---

## Templates & Resources (1 file)

**Location:** `resources/templates/orchestration/`

| File | Format | Description |
|------|--------|-------------|
| `workflow.yaml` | YAML | Workflow phase definitions:<br>- Phase names<br>- Phase transitions<br>- Phase requirements<br>- Validation rules |

**Package Path:** `resources/templates/orchestration/`

---

## Events (1 file)

**Location:** `app/Events/`

| File | Description | Listeners |
|------|-------------|-----------|
| `OrchestrationEventCreated.php` | Dispatched when orchestration event created | Can be used for logging, notifications, webhooks |

**Package Path:** `src/Events/`

---

## Enums (1 file)

**Location:** `app/Enums/`

| File | Values | Description |
|------|--------|-------------|
| `OrchestrationPhase.php` | Planning, Implementation, Review, Testing, Deployment, Closed | Workflow phase enum |

**Package Path:** `src/Enums/`

---

## Routes (1 file)

**Location:** `routes/api.php` (extract section)

**Endpoints to Extract:** ~42 REST API endpoints grouped by:
- Sprints (7 endpoints)
- Tasks (6 endpoints)
- Events (7 endpoints)
- Templates (2 endpoints)
- Agents (3 endpoints)
- PM Tools (4 endpoints)
- Legacy Messaging (4 endpoints)
- Legacy Artifacts (3 endpoints)
- Legacy Tasks (3 endpoints)
- Task Activities (3 endpoints)

**Package Path:** `routes/api.php`

---

## Summary Statistics

### By Category
| Category | File Count |
|----------|------------|
| Models | 5 |
| Services | 16 |
| Controllers (API) | 6 |
| Controllers (Legacy) | 4 |
| Console Commands | 27 (31 total, 4 stay in app) |
| MCP Tools | 27 |
| MCP Concerns | 1 |
| Migrations | 10 |
| Factories | 2 |
| Config | 1 |
| Templates | 1 |
| Events | 1 |
| Enums | 1 |
| Routes | 1 section (~42 endpoints) |
| **TOTAL** | **103 files** |

### By Approximate Size
| Size Range | Count | Examples |
|------------|-------|----------|
| Small (< 150 lines) | 28 | Enums, simple tools, basic commands |
| Medium (150-300 lines) | 45 | Most tools, many commands, simple services |
| Large (300-450 lines) | 26 | Complex services, controllers, MCP server |
| Very Large (> 450 lines) | 4 | TaskOrchestrationService, EventController |

### By Complexity
| Complexity | Count | Description |
|------------|-------|-------------|
| Low | 35 | Simple CRUD, data models, DTOs |
| Medium | 48 | Business logic, tool implementations |
| High | 20 | Event sourcing, session management, search |

---

## External Dependencies Summary

### Laravel Framework
- Eloquent ORM (models, relationships, query builder)
- Service Container (dependency injection)
- Events & Listeners
- Console (artisan commands)
- HTTP (controllers, routes, requests)
- Validation
- File Storage
- Cache
- Queue

### Third-Party Packages
- `symfony/yaml` - For workflow.yaml parsing
- MCP Protocol - For tool definitions
- Vector Search - For context search (via main app)

### Main App Dependencies
- `App\Models\AgentProfile` - Agent definitions
- `App\Models\Project` - Project context  
- `App\Models\Fragment` - Memory/context storage
- `App\Models\User` - Authentication
- Vector search service
- Git service (shared)

---

## Migration Complexity Assessment

### Low Risk (Simple to migrate)
- Models (clear boundaries, minimal dependencies)
- Enums (standalone)
- Events (simple dispatching)
- Most MCP tools (standardized interface)
- Simple console commands

### Medium Risk (Moderate complexity)
- Basic services (task, sprint, agent services)
- Controllers (mainly CRUD operations)
- Routes (straightforward extraction)
- Migrations (need careful ordering)

### High Risk (Careful planning required)
- EventService (event sourcing complexity)
- SessionService (state management, workflow.yaml dependency)
- ContextSearchService (vector search integration)
- OrchestrationMcp command (MCP server integration)
- Git integration (process management, commit tracking)
- Memory/context services (integration with main app's memory system)

---

## Recommendations

1. **Start with low-risk files** (models, enums, events)
2. **Create comprehensive tests** before migrating high-risk services
3. **Use interfaces** for main app dependencies (AgentProfile, Project, etc.)
4. **Version carefully** - this is a major extraction
5. **Document breaking changes** - namespace changes will affect consumers
6. **Create migration guide** - step-by-step upgrade instructions
7. **Consider feature flags** - gradual rollout option
