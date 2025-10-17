# Orchestration v2 Package Migration Plan

## Overview
This document identifies all files that need to be extracted from the Seer Laravel application into a standalone `hollis-labs/orchestration` package.

**Current Location:** Mixed throughout Laravel `/app`, `/database`, `/config`, `/routes`  
**Target Package:** `vendor/hollis-labs/orchestration`

---

## Package Structure

```
orchestration/
├── src/
│   ├── Models/                      # 5 Eloquent models
│   ├── Services/                    # 16 orchestration services
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                # 6 API controllers
│   │   │   └── Orchestration/      # 4 legacy controllers
│   ├── Console/Commands/            # 31 artisan commands
│   ├── Tools/Orchestration/         # 27 MCP tool implementations
│   ├── Events/                      # 1 Laravel event
│   ├── Enums/                       # 1 enum (OrchestrationPhase)
│   └── OrchestrationServiceProvider.php
├── database/
│   ├── migrations/                  # 10 migrations
│   └── factories/                   # 2 factories
├── routes/
│   ├── api.php                      # API routes
│   └── console.php                  # Console routes (if any)
├── config/
│   └── orchestration.php            # Package configuration
├── resources/
│   └── templates/
│       └── orchestration/
│           └── workflow.yaml        # Workflow configuration
├── tests/
├── README.md
└── composer.json
```

---

## File Inventory

### 1. Models (5 files)
**Location:** `app/Models/`

```
OrchestrationTask.php           # Task/work item management
OrchestrationSprint.php          # Sprint/iteration management
OrchestrationEvent.php           # Event sourcing/audit log
OrchestrationArtifact.php        # File artifacts attached to tasks
OrchestrationBug.php             # Bug tracking
```

**Dependencies:**
- Relationships with `AgentProfile`, `Project` (stay in main app)
- Soft deletes
- JSON casting for metadata fields

---

### 2. Services (16 files)
**Location:** `app/Services/` and `app/Services/Orchestration/`

#### Core Orchestration Services:
```
TaskOrchestrationService.php            # Task lifecycle management
SprintOrchestrationService.php          # Sprint lifecycle management
AgentOrchestrationService.php           # Agent coordination
PromptOrchestrator.php                  # Prompt template management
```

#### Specialized Services:
```
Orchestration/OrchestrationEventService.php          # Event sourcing
Orchestration/OrchestrationSessionService.php        # Session state management
Orchestration/OrchestrationHashService.php           # Content hashing
Orchestration/OrchestrationTemplateService.php       # Template rendering
Orchestration/OrchestrationBugService.php            # Bug logging
Orchestration/OrchestrationPMToolsService.php        # PM tool integrations
Orchestration/OrchestrationAutomationService.php     # Workflow automation
Orchestration/OrchestrationReplayService.php         # Event replay
Orchestration/OrchestrationContextBrokerService.php  # Context distribution
Orchestration/OrchestrationContextSearchService.php  # Context search
Orchestration/OrchestrationGitService.php            # Git integration
Orchestration/OrchestrationFileSyncService.php       # File sync
Orchestration/MemoryService.php                      # Memory/context storage
```

**Key Dependencies:**
- Uses `resources/templates/orchestration/workflow.yaml`
- Depends on models: `AgentProfile`, `Project`, `Fragment`
- Uses vector search and full-text search
- Integrates with Git
- Memory service for AI context

---

### 3. HTTP Controllers (10 files)

#### API Controllers (6 files)
**Location:** `app/Http/Controllers/Api/`

```
OrchestrationSprintController.php    # Sprint CRUD + from-template
OrchestrationTaskController.php      # Task CRUD + from-template
OrchestrationEventController.php     # Event queries, timeline, replay
OrchestrationAgentController.php     # Agent init, context, activity
OrchestrationTemplateController.php  # Template listing
OrchestrationPMToolsController.php   # ADR, bug reports, status
```

#### Legacy Controllers (4 files)
**Location:** `app/Http/Controllers/Orchestration/`

```
MessagingController.php              # Agent inbox/messaging
ArtifactsController.php              # Artifact upload/download
TaskController.php                   # Task field updates
TaskActivityController.php           # Task activity log
```

---

### 4. Console Commands (31 files)
**Location:** `app/Console/Commands/`

#### Session Management:
```
OrchestrationSessionStart.php
OrchestrationSessionEnd.php
```

#### Sprint Management:
```
OrchestrationSprintsCommand.php
OrchestrationSprintDetailCommand.php
OrchestrationSprintSaveCommand.php
OrchestrationSprintStatusCommand.php
OrchestrationSprintTasksAttachCommand.php
```

#### Task Management:
```
OrchestrationTasksCommand.php
OrchestrationTaskDetailCommand.php
OrchestrationTaskSaveCommand.php
OrchestrationTaskStatusCommand.php
OrchestrationTaskStatus.php
OrchestrationTaskAssignCommand.php
```

#### Agent Management:
```
OrchestrationAgentsCommand.php
OrchestrationAgentDetailCommand.php
OrchestrationAgentSaveCommand.php
OrchestrationAgentStatusCommand.php
```

#### Workflow & Automation:
```
OrchestrationPhaseComplete.php
OrchestrationStatusReport.php
```

#### PM Tools:
```
OrchestrationADRGenerate.php
OrchestrationBugLog.php
OrchestrationBugReport.php
```

#### Git Integration:
```
OrchestrationGitLinkPr.php
```

#### Context & Search:
```
OrchestrationContextSearch.php
```

#### Maintenance:
```
ArchiveOrchestrationEvents.php
```

#### Migration Commands (keep in main app, but document):
```
MigrateSprintsToOrchestration.php
MigrateWorkItemsToOrchestration.php
DelegationImportCommand.php
ImportDelegationContent.php
```

#### Utility:
```
OrchestrationMcp.php                 # MCP server integration
MakeCommandClass.php                 # Code generator
```

---

### 5. MCP Tools (27 files + 1 concern)
**Location:** `app/Tools/Orchestration/`

#### Agent Tools:
```
AgentsListTool.php
AgentDetailTool.php
AgentSaveTool.php
AgentStatusTool.php
```

#### Task Tools:
```
TasksListTool.php
TaskDetailTool.php
TaskSaveTool.php
TaskStatusTool.php
TaskAssignTool.php
```

#### Sprint Tools:
```
SprintsListTool.php
SprintDetailTool.php
SprintSaveTool.php
SprintStatusTool.php
SprintTasksAttachTool.php
```

#### Session Management:
```
SessionStartTool.php
SessionEndTool.php
SessionStatusTool.php
SessionTaskActivateTool.php
SessionTaskDeactivateTool.php
SessionTaskUpdateTool.php
SessionTaskNoteTool.php
SessionSprintActivateTool.php
```

#### Messaging:
```
MessagesCheckTool.php
MessageAckTool.php
HandoffTool.php
```

#### Artifacts:
```
ArtifactsPullTool.php
```

#### Concerns:
```
Concerns/NormalisesFilters.php       # Shared trait for filter normalization
```

**Tool Architecture:**
- All tools implement MCP (Model Context Protocol)
- Used by AI agents to interact with orchestration system
- Configured in `config/orchestration.php`

---

### 6. Database Migrations (10 files)
**Location:** `database/migrations/`

```
2025_10_05_180542_enhance_work_items_for_orchestration.php
2025_10_06_211016_create_orchestration_artifacts_table.php
2025_10_12_233110_create_orchestration_sprints_table.php
2025_10_12_233110_create_orchestration_tasks_table.php
2025_10_12_233111_create_orchestration_events_table.php
2025_10_13_003025_add_archived_at_to_orchestration_events_table.php
2025_10_13_232030_create_orchestration_bugs_table.php
2025_10_14_205144_add_date_fields_to_orchestration_sprints_table.php
2025_10_14_205201_add_work_item_fields_to_orchestration_tasks_table.php
2025_10_15_035307_add_description_to_orchestration_tasks_table.php
```

**Tables Created:**
- `orchestration_tasks` - Core task/work item table
- `orchestration_sprints` - Sprint/iteration table
- `orchestration_events` - Event sourcing audit log
- `orchestration_artifacts` - File attachments
- `orchestration_bugs` - Bug tracking

---

### 7. Database Factories (2 files)
**Location:** `database/factories/`

```
OrchestrationArtifactFactory.php
OrchestrationSprintFactory.php
```

---

### 8. Configuration (1 file)
**Location:** `config/`

```
orchestration.php                    # Complete orchestration config
```

**Config Sections:**
- `enabled_tools` - Tool feature flags
- `priority_tools` - High-priority tool list
- `categories` - Tool categorization
- `models` - Model class references
- `services` - Service class references
- `artifacts` - File storage config
- `messaging` - Message retention
- `secret_redaction` - Security patterns
- `search` - Search engine config
- `workflow` - Workflow behavior
- `git` - Git integration settings

---

### 9. Templates & Resources (1 file)
**Location:** `resources/templates/orchestration/`

```
workflow.yaml                        # Workflow phase definitions
```

**Purpose:**
- Defines orchestration workflow phases
- Used by `OrchestrationSessionService`
- Configures phase transitions and requirements

---

### 10. Events (1 file)
**Location:** `app/Events/`

```
OrchestrationEventCreated.php       # Laravel event dispatched on event creation
```

---

### 11. Enums (1 file)
**Location:** `app/Enums/`

```
OrchestrationPhase.php               # Workflow phase enum
```

**Phases:**
- Planning
- Implementation
- Review
- Testing
- Deployment
- Closed

---

### 12. Routes
**Location:** `routes/api.php`

**Routes to Extract:**
```php
Route::prefix('orchestration')->group(function () {
    // Sprint routes (7 endpoints)
    Route::get('/sprints', ...);
    Route::post('/sprints', ...);
    Route::get('/sprints/{code}', ...);
    Route::put('/sprints/{code}', ...);
    Route::delete('/sprints/{code}', ...);
    Route::post('/sprints/from-template', ...);
    Route::post('/sprints/{code}/sync', ...);
    Route::post('/sprints/{code}/tasks/from-template', ...);
    Route::get('/sprints/{code}/history', ...);
    
    // Task routes (6 endpoints)
    Route::get('/tasks', ...);
    Route::post('/tasks', ...);
    Route::get('/tasks/{code}', ...);
    Route::put('/tasks/{code}', ...);
    Route::delete('/tasks/{code}', ...);
    Route::get('/tasks/{code}/history', ...);
    
    // Event routes (7 endpoints)
    Route::get('/events', ...);
    Route::get('/events/correlation/{correlationId}', ...);
    Route::get('/events/session/{sessionKey}', ...);
    Route::get('/events/timeline', ...);
    Route::get('/events/stats', ...);
    Route::post('/events/replay', ...);
    
    // Template routes (2 endpoints)
    Route::get('/templates', ...);
    Route::get('/templates/{type}/{name}', ...);
    
    // Agent routes (3 endpoints)
    Route::post('/agent/init', ...);
    Route::get('/sessions/{sessionKey}/context', ...);
    Route::post('/sessions/{sessionKey}/activity', ...);
    
    // PM Tools routes (4 endpoints)
    Route::post('/pm-tools/adr', ...);
    Route::post('/pm-tools/bug-report', ...);
    Route::post('/pm-tools/task-status', ...);
    Route::get('/pm-tools/status-report', ...);
    
    // Legacy messaging routes (4 endpoints)
    Route::post('/agents/{agentId}/inbox', ...);
    Route::get('/agents/{agentId}/inbox', ...);
    Route::post('/messages/{messageId}/read', ...);
    Route::post('/projects/{projectId}/broadcast', ...);
    
    // Legacy artifact routes (3 endpoints)
    Route::post('/tasks/{taskId}/artifacts', ...);
    Route::get('/tasks/{taskId}/artifacts', ...);
    Route::get('/artifacts/{artifactId}/download', ...);
    
    // Legacy task routes (3 endpoints)
    Route::patch('/tasks/{id}/field', ...);
    Route::patch('/tasks/{id}/tags', ...);
    Route::get('/tasks/sprints/available', ...);
    
    // Task activity routes (3 endpoints)
    Route::get('/tasks/{taskId}/activities', ...);
    Route::post('/tasks/{taskId}/activities', ...);
    Route::get('/tasks/{taskId}/activities/summary', ...);
});
```

**Total API Endpoints:** ~42 endpoints

---

## Dependencies & Integration Points

### External Dependencies (Main App)
These models/services remain in the main app, package depends on them:

1. **Models:**
   - `App\Models\AgentProfile` - Agent definitions
   - `App\Models\Project` - Project context
   - `App\Models\Fragment` - Memory/context fragments
   - `App\Models\User` - User authentication

2. **Services:**
   - Vector search service (for context search)
   - Memory/fragment service (for AI context)
   - Git service (shared Git operations)

3. **Infrastructure:**
   - Laravel auth
   - File storage (disk configuration)
   - Queue system (for event processing)
   - Cache (for search results)

### Package Exports (Available to Main App)

1. **Models:** All 5 orchestration models
2. **Services:** All 16 orchestration services
3. **Tools:** All 27 MCP tools (auto-registered)
4. **Commands:** All 31 console commands (auto-registered)
5. **Routes:** All API routes (mounted at `/api/orchestration`)
6. **Config:** `config/orchestration.php`

---

## Migration Strategy

### Phase 1: Package Scaffolding
1. Create `vendor/hollis-labs/orchestration` package structure
2. Set up `composer.json` with dependencies
3. Create `OrchestrationServiceProvider.php`
4. Add basic README and package documentation

### Phase 2: Core Files Migration
1. Move models (5 files)
2. Move enums (1 file)
3. Move events (1 file)
4. Update namespaces from `App\` to `HollisLabs\Orchestration\`

### Phase 3: Services Migration
1. Move all 16 service files
2. Update service provider bindings
3. Update internal references
4. Handle external dependencies (inject interfaces)

### Phase 4: Controllers & Routes
1. Move 10 controller files
2. Extract routes to package `routes/api.php`
3. Update controller namespaces
4. Register routes in service provider

### Phase 5: Commands & Tools
1. Move 31 console commands
2. Move 27 MCP tools + concerns
3. Auto-register in service provider
4. Update tool configuration

### Phase 6: Database & Config
1. Move migrations (10 files)
2. Move factories (2 files)
3. Move config file
4. Move workflow template

### Phase 7: Testing & Documentation
1. Update imports in main app
2. Test all endpoints
3. Test all commands
4. Test MCP tool integration
5. Update package README
6. Create migration guide

### Phase 8: Cleanup
1. Remove old files from main app
2. Update main app's `composer.json`
3. Clear cached routes/config
4. Run full test suite

---

## Configuration Changes

### Main App Changes

**composer.json:**
```json
{
  "require": {
    "hollis-labs/orchestration": "^1.0"
  }
}
```

**config/app.php:**
```php
'providers' => [
    // ...
    HollisLabs\Orchestration\OrchestrationServiceProvider::class,
]
```

### Package composer.json

```json
{
  "name": "hollis-labs/orchestration",
  "description": "AI agent orchestration and workflow management",
  "type": "library",
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "symfony/yaml": "^7.0"
  },
  "autoload": {
    "psr-4": {
      "HollisLabs\\Orchestration\\": "src/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "HollisLabs\\Orchestration\\OrchestrationServiceProvider"
      ]
    }
  }
}
```

---

## Testing Checklist

After migration, verify:

### API Endpoints
- [ ] Sprint CRUD operations
- [ ] Task CRUD operations
- [ ] Event querying and replay
- [ ] Template listing and instantiation
- [ ] Agent initialization and context
- [ ] PM tools (ADR, bug reports, status)
- [ ] Legacy messaging APIs
- [ ] Artifact upload/download
- [ ] Task activity logging

### Console Commands
- [ ] Session start/end
- [ ] Sprint management commands
- [ ] Task management commands
- [ ] Agent management commands
- [ ] Status reporting
- [ ] Bug logging
- [ ] Context search
- [ ] Event archival

### MCP Tools
- [ ] All 27 tools callable via MCP
- [ ] Tool configuration loading
- [ ] Filter normalization
- [ ] Error handling

### Integration
- [ ] Event sourcing working
- [ ] Git integration functioning
- [ ] File sync operational
- [ ] Memory service accessible
- [ ] Vector search working
- [ ] Template rendering correct

---

## File Count Summary

| Category | Count |
|----------|-------|
| Models | 5 |
| Services | 16 |
| Controllers | 10 |
| Console Commands | 31 |
| MCP Tools | 27 + 1 concern |
| Migrations | 10 |
| Factories | 2 |
| Config Files | 1 |
| Templates | 1 |
| Events | 1 |
| Enums | 1 |
| **TOTAL** | **106 files** |

---

## Next Steps

1. Review this plan for completeness
2. Decide on package versioning strategy
3. Determine if package should be open source
4. Create package repository
5. Begin Phase 1 migration
6. Document breaking changes (if any)
7. Create upgrade guide for existing installations
