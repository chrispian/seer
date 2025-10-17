# Orchestration Package Migration Checklist

Quick reference checklist for migrating orchestration system to a standalone package.

---

## Pre-Migration

- [ ] Review PACKAGE-MIGRATION-PLAN.md
- [ ] Review FILE-INVENTORY.md
- [ ] Create backup of current codebase
- [ ] Create `hollis-labs/orchestration` package repository
- [ ] Set up package structure and composer.json
- [ ] Create package service provider skeleton

---

## Phase 1: Core Models & Data Structures

### Models (5 files)
- [ ] `OrchestrationTask.php`
- [ ] `OrchestrationSprint.php`
- [ ] `OrchestrationEvent.php`
- [ ] `OrchestrationArtifact.php`
- [ ] `OrchestrationBug.php`

### Supporting Files
- [ ] `OrchestrationPhase.php` (enum)
- [ ] `OrchestrationEventCreated.php` (event)

### Update Namespaces
- [ ] Change `App\Models\` → `HollisLabs\Orchestration\Models\`
- [ ] Change `App\Enums\` → `HollisLabs\Orchestration\Enums\`
- [ ] Change `App\Events\` → `HollisLabs\Orchestration\Events\`

### Testing
- [ ] Test model relationships
- [ ] Test model casts and accessors
- [ ] Test soft deletes
- [ ] Test scopes

---

## Phase 2: Services Layer

### Core Services (4 files)
- [ ] `TaskOrchestrationService.php`
- [ ] `SprintOrchestrationService.php`
- [ ] `AgentOrchestrationService.php`
- [ ] `PromptOrchestrator.php`

### Specialized Services (12 files)
- [ ] `OrchestrationEventService.php`
- [ ] `OrchestrationSessionService.php`
- [ ] `OrchestrationHashService.php`
- [ ] `OrchestrationTemplateService.php`
- [ ] `OrchestrationBugService.php`
- [ ] `OrchestrationPMToolsService.php`
- [ ] `OrchestrationAutomationService.php`
- [ ] `OrchestrationReplayService.php`
- [ ] `OrchestrationContextBrokerService.php`
- [ ] `OrchestrationContextSearchService.php`
- [ ] `OrchestrationGitService.php`
- [ ] `OrchestrationFileSyncService.php`
- [ ] `MemoryService.php`

### Update Namespaces
- [ ] Change `App\Services\` → `HollisLabs\Orchestration\Services\`

### Service Provider Bindings
- [ ] Register all services in service provider
- [ ] Create interfaces for external dependencies
- [ ] Inject dependencies properly

### Testing
- [ ] Test each service independently
- [ ] Test service integrations
- [ ] Test event firing
- [ ] Test workflow state management

---

## Phase 3: HTTP Layer

### API Controllers (6 files)
- [ ] `OrchestrationSprintController.php`
- [ ] `OrchestrationTaskController.php`
- [ ] `OrchestrationEventController.php`
- [ ] `OrchestrationAgentController.php`
- [ ] `OrchestrationTemplateController.php`
- [ ] `OrchestrationPMToolsController.php`

### Legacy Controllers (4 files)
- [ ] `MessagingController.php`
- [ ] `ArtifactsController.php`
- [ ] `TaskController.php`
- [ ] `TaskActivityController.php`

### Update Namespaces
- [ ] Change `App\Http\Controllers\Api\` → `HollisLabs\Orchestration\Http\Controllers\Api\`
- [ ] Change `App\Http\Controllers\Orchestration\` → `HollisLabs\Orchestration\Http\Controllers\`

### Routes
- [ ] Extract orchestration routes from `routes/api.php`
- [ ] Create package `routes/api.php`
- [ ] Register routes in service provider
- [ ] Test route prefixing (`/api/orchestration`)

### Testing
- [ ] Test all API endpoints (42 total)
- [ ] Test request validation
- [ ] Test response formats
- [ ] Test error handling
- [ ] Test authentication/authorization

---

## Phase 4: Console Commands

### Session Commands (2 files)
- [ ] `OrchestrationSessionStart.php`
- [ ] `OrchestrationSessionEnd.php`

### Sprint Commands (5 files)
- [ ] `OrchestrationSprintsCommand.php`
- [ ] `OrchestrationSprintDetailCommand.php`
- [ ] `OrchestrationSprintSaveCommand.php`
- [ ] `OrchestrationSprintStatusCommand.php`
- [ ] `OrchestrationSprintTasksAttachCommand.php`

### Task Commands (6 files)
- [ ] `OrchestrationTasksCommand.php`
- [ ] `OrchestrationTaskDetailCommand.php`
- [ ] `OrchestrationTaskSaveCommand.php`
- [ ] `OrchestrationTaskStatusCommand.php`
- [ ] `OrchestrationTaskStatus.php`
- [ ] `OrchestrationTaskAssignCommand.php`

### Agent Commands (4 files)
- [ ] `OrchestrationAgentsCommand.php`
- [ ] `OrchestrationAgentDetailCommand.php`
- [ ] `OrchestrationAgentSaveCommand.php`
- [ ] `OrchestrationAgentStatusCommand.php`

### Workflow Commands (2 files)
- [ ] `OrchestrationPhaseComplete.php`
- [ ] `OrchestrationStatusReport.php`

### PM Tool Commands (3 files)
- [ ] `OrchestrationADRGenerate.php`
- [ ] `OrchestrationBugLog.php`
- [ ] `OrchestrationBugReport.php`

### Other Commands (5 files)
- [ ] `OrchestrationGitLinkPr.php`
- [ ] `OrchestrationContextSearch.php`
- [ ] `ArchiveOrchestrationEvents.php`
- [ ] `OrchestrationMcp.php`
- [ ] `MakeCommandClass.php`

### Update Namespaces
- [ ] Change `App\Console\Commands\` → `HollisLabs\Orchestration\Console\Commands\`

### Command Registration
- [ ] Auto-register commands in service provider
- [ ] Test command discovery

### Testing
- [ ] Test each command independently
- [ ] Test command arguments/options
- [ ] Test command output
- [ ] Test error handling

---

## Phase 5: MCP Tools

### Agent Tools (4 files)
- [ ] `AgentsListTool.php`
- [ ] `AgentDetailTool.php`
- [ ] `AgentSaveTool.php`
- [ ] `AgentStatusTool.php`

### Task Tools (5 files)
- [ ] `TasksListTool.php`
- [ ] `TaskDetailTool.php`
- [ ] `TaskSaveTool.php`
- [ ] `TaskStatusTool.php`
- [ ] `TaskAssignTool.php`

### Sprint Tools (5 files)
- [ ] `SprintsListTool.php`
- [ ] `SprintDetailTool.php`
- [ ] `SprintSaveTool.php`
- [ ] `SprintStatusTool.php`
- [ ] `SprintTasksAttachTool.php`

### Session Tools (7 files)
- [ ] `SessionStartTool.php`
- [ ] `SessionEndTool.php`
- [ ] `SessionStatusTool.php`
- [ ] `SessionTaskActivateTool.php`
- [ ] `SessionTaskDeactivateTool.php`
- [ ] `SessionTaskUpdateTool.php`
- [ ] `SessionTaskNoteTool.php`
- [ ] `SessionSprintActivateTool.php`

### Messaging Tools (3 files)
- [ ] `MessagesCheckTool.php`
- [ ] `MessageAckTool.php`
- [ ] `HandoffTool.php`

### Artifact Tools (1 file)
- [ ] `ArtifactsPullTool.php`

### Concerns (1 file)
- [ ] `Concerns/NormalisesFilters.php`

### Update Namespaces
- [ ] Change `App\Tools\Orchestration\` → `HollisLabs\Orchestration\Tools\`

### Tool Registration
- [ ] Register tools in config
- [ ] Verify tool discovery
- [ ] Test tool categorization

### Testing
- [ ] Test each tool via MCP
- [ ] Test tool parameters
- [ ] Test tool responses
- [ ] Test filter normalization

---

## Phase 6: Database & Configuration

### Migrations (10 files)
- [ ] `2025_10_05_180542_enhance_work_items_for_orchestration.php`
- [ ] `2025_10_06_211016_create_orchestration_artifacts_table.php`
- [ ] `2025_10_12_233110_create_orchestration_sprints_table.php`
- [ ] `2025_10_12_233110_create_orchestration_tasks_table.php`
- [ ] `2025_10_12_233111_create_orchestration_events_table.php`
- [ ] `2025_10_13_003025_add_archived_at_to_orchestration_events_table.php`
- [ ] `2025_10_13_232030_create_orchestration_bugs_table.php`
- [ ] `2025_10_14_205144_add_date_fields_to_orchestration_sprints_table.php`
- [ ] `2025_10_14_205201_add_work_item_fields_to_orchestration_tasks_table.php`
- [ ] `2025_10_15_035307_add_description_to_orchestration_tasks_table.php`

### Factories (2 files)
- [ ] `OrchestrationArtifactFactory.php`
- [ ] `OrchestrationSprintFactory.php`

### Configuration (1 file)
- [ ] `orchestration.php`

### Templates (1 file)
- [ ] `resources/templates/orchestration/workflow.yaml`

### Publishing
- [ ] Configure migration publishing
- [ ] Configure config publishing
- [ ] Configure template publishing
- [ ] Test `php artisan vendor:publish`

### Testing
- [ ] Test migrations (up and down)
- [ ] Test factories
- [ ] Test config loading
- [ ] Test workflow.yaml parsing

---

## Phase 7: Package Integration

### Main App Updates

#### composer.json
- [ ] Add package to `require` section
- [ ] Run `composer update`

#### Service Provider
- [ ] Register package service provider (auto-discovery)
- [ ] Or manually add to `config/app.php` if needed

#### Configuration
- [ ] Publish package config: `php artisan vendor:publish --tag=orchestration-config`
- [ ] Publish migrations: `php artisan vendor:publish --tag=orchestration-migrations`
- [ ] Run migrations: `php artisan migrate`

#### Routes
- [ ] Verify routes are loaded
- [ ] Test route list: `php artisan route:list | grep orchestration`

#### Commands
- [ ] Verify commands registered
- [ ] Test command list: `php artisan list orchestration`

### Testing
- [ ] Full integration test suite
- [ ] Test from clean Laravel install
- [ ] Test package auto-discovery
- [ ] Test published assets

---

## Phase 8: Documentation

### Package Documentation
- [ ] README.md with installation instructions
- [ ] CHANGELOG.md
- [ ] UPGRADE.md (if breaking changes)
- [ ] API documentation
- [ ] Configuration reference
- [ ] Workflow guide
- [ ] MCP tool reference

### Code Documentation
- [ ] PHPDoc for all public methods
- [ ] Inline comments for complex logic
- [ ] Architecture decision records (ADRs)

### Examples
- [ ] Basic usage examples
- [ ] Advanced usage examples
- [ ] Integration examples

---

## Phase 9: Testing & Quality Assurance

### Unit Tests
- [ ] Model tests
- [ ] Service tests
- [ ] Tool tests
- [ ] Command tests

### Integration Tests
- [ ] Controller tests
- [ ] Workflow tests
- [ ] Event sourcing tests
- [ ] Git integration tests

### Feature Tests
- [ ] End-to-end sprint workflow
- [ ] End-to-end task workflow
- [ ] Session management workflow
- [ ] MCP tool integration

### Code Quality
- [ ] Run PHPStan/Psalm
- [ ] Run PHP CS Fixer
- [ ] Check test coverage
- [ ] Review security implications

---

## Phase 10: Cleanup & Finalization

### Remove Old Files from Main App
- [ ] Delete `app/Models/Orchestration*.php`
- [ ] Delete `app/Services/*Orchestration*.php`
- [ ] Delete `app/Services/Orchestration/`
- [ ] Delete `app/Http/Controllers/Api/Orchestration*.php`
- [ ] Delete `app/Http/Controllers/Orchestration/`
- [ ] Delete `app/Console/Commands/Orchestration*.php`
- [ ] Delete `app/Console/Commands/ArchiveOrchestrationEvents.php`
- [ ] Delete `app/Tools/Orchestration/`
- [ ] Delete `app/Events/OrchestrationEventCreated.php`
- [ ] Delete `app/Enums/OrchestrationPhase.php`
- [ ] Delete `config/orchestration.php`
- [ ] Delete `resources/templates/orchestration/`
- [ ] Remove orchestration routes from `routes/api.php`

### Update Imports
- [ ] Search and replace `App\Models\Orchestration` → `HollisLabs\Orchestration\Models\Orchestration`
- [ ] Search and replace `App\Services\*Orchestration` → `HollisLabs\Orchestration\Services\`
- [ ] Search and replace `App\Tools\Orchestration` → `HollisLabs\Orchestration\Tools\`
- [ ] Verify no broken imports

### Clear Caches
- [ ] `php artisan config:clear`
- [ ] `php artisan route:clear`
- [ ] `php artisan cache:clear`
- [ ] `composer dump-autoload`

### Final Tests
- [ ] Run full test suite
- [ ] Test all API endpoints
- [ ] Test all console commands
- [ ] Test MCP tool integration
- [ ] Performance testing
- [ ] Load testing

---

## Phase 11: Release

### Version Control
- [ ] Tag package version (e.g., v1.0.0)
- [ ] Create GitHub release
- [ ] Update CHANGELOG.md

### Package Registry
- [ ] Publish to Packagist (if open source)
- [ ] Or configure private registry

### Main App Update
- [ ] Update main app to use package version
- [ ] Create migration guide
- [ ] Update main app documentation

### Communication
- [ ] Announce package release
- [ ] Update team documentation
- [ ] Provide training if needed

---

## Rollback Plan

If issues arise:

- [ ] Document rollback procedure
- [ ] Keep backup of original files
- [ ] Test rollback process
- [ ] Have contingency plan

---

## Success Criteria

- [ ] All 103 files migrated successfully
- [ ] All tests passing
- [ ] No broken imports in main app
- [ ] All API endpoints working
- [ ] All console commands working
- [ ] All MCP tools working
- [ ] Documentation complete
- [ ] Package installable via composer
- [ ] Zero downtime during migration

---

## Estimated Timeline

| Phase | Estimated Time | Complexity |
|-------|---------------|------------|
| Phase 1: Models | 4 hours | Low |
| Phase 2: Services | 12 hours | High |
| Phase 3: HTTP | 8 hours | Medium |
| Phase 4: Commands | 10 hours | Medium |
| Phase 5: Tools | 8 hours | Medium |
| Phase 6: Database | 4 hours | Low |
| Phase 7: Integration | 6 hours | Medium |
| Phase 8: Documentation | 8 hours | Medium |
| Phase 9: Testing | 16 hours | High |
| Phase 10: Cleanup | 4 hours | Low |
| Phase 11: Release | 2 hours | Low |
| **TOTAL** | **~82 hours** | **~2 weeks** |

---

## Notes

- Work in a separate branch
- Commit frequently with descriptive messages
- Test after each phase
- Don't rush - this is critical infrastructure
- Ask for help when needed
- Document any deviations from plan

---

## Contact

If questions arise during migration:
- Review documentation in `docs/orchestration-v2/`
- Check package README
- Consult with team lead
