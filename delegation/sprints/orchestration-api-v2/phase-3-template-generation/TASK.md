# Task: Template Generation API - Sprint/Task Creation from Templates

**Task ID**: `phase-3-template-generation`  
**Sprint**: `orchestration-api-v2`  
**Phase**: 3  
**Status**: Pending  
**Priority**: P0  
**Estimated Duration**: 3-5 days

---

## Objective

Build API endpoints to generate sprints and tasks from templates in `delegation/.templates/`, automatically creating database records and file system structures, enabling one-command sprint/task creation.

---

## Context

Phase 1 delivered CRUD APIs, Phase 2 added event system. Phase 3 connects templates to the database, allowing:
1. **Sprint creation from template** → DB record + file structure
2. **Task creation from template** → DB record + TASK.md/AGENT.yml files
3. **File system sync** → Keep `delegation/sprints/` in sync with DB
4. **Variable interpolation** → Replace placeholders in templates

This enables `POST /api/orchestration/sprints/from-template` to scaffold entire sprint directories.

Reference:
- `delegation/.templates/` - Template system structure
- Phase 1 controllers for CRUD patterns
- Phase 2 event service for emission

---

## Tasks

### 1. Template Service
- [ ] Create `OrchestrationTemplateService`
  - `loadTemplate($type, $name)` - Load sprint/task template
  - `parseTemplate($content, $variables)` - Interpolate variables
  - `getAvailableTemplates()` - List all templates
  - `validateTemplate($content)` - Check required fields
- [ ] Support template types:
  - Sprint templates (`sprint-template/SPRINT_TEMPLATE.md`)
  - Task templates (`task-template/TASK_TEMPLATE.md`)
  - Agent templates (`agent-base/AGENT_BASE.yml`)

### 2. Sprint Generation
- [ ] Add `OrchestrationSprintController::createFromTemplate()`
  - Accept: `template_name`, `sprint_code`, `title`, `variables`
  - Create DB record in `orchestration_sprints`
  - Generate file structure: `delegation/sprints/{sprint_code}/`
  - Create `SPRINT.md` from template
  - Create `README.md` from template
  - Emit `orchestration.sprint.generated` event
- [ ] Variable interpolation:
  - `{{sprint_code}}` → actual sprint code
  - `{{title}}` → sprint title
  - `{{owner}}` → owner name
  - `{{start_date}}` → start date
  - Custom variables from request

### 3. Task Generation
- [ ] Add `OrchestrationTaskController::createFromTemplate()`
  - Accept: `template_name`, `task_code`, `sprint_id`, `variables`
  - Create DB record in `orchestration_tasks`
  - Generate task directory: `delegation/sprints/{sprint}/{task}/`
  - Create `TASK.md` from template
  - Create `AGENT.yml` from template
  - Emit `orchestration.task.generated` event
- [ ] Batch task creation:
  - `POST /api/orchestration/sprints/{code}/tasks/from-template`
  - Create multiple tasks at once from template array

### 4. File System Sync Service
- [ ] Create `OrchestrationFileSyncService`
  - `syncSprintToFile(OrchestrationSprint)` - Write DB → file
  - `syncTaskToFile(OrchestrationTask)` - Write DB → file
  - `ensureDirectoryStructure($path)` - Create dirs if missing
  - `writeMarkdownFile($path, $content)` - Write MD files
  - `writeYamlFile($path, $data)` - Write YAML files
- [ ] Auto-sync on DB changes (via events):
  - When sprint created/updated → sync SPRINT.md
  - When task created/updated → sync TASK.md
  - Optional: background job for large operations

### 5. Template API Endpoints
- [ ] `GET /api/orchestration/templates` - List available templates
  - Response: `{ sprints: [], tasks: [], agents: [] }`
- [ ] `GET /api/orchestration/templates/{type}/{name}` - Get template content
- [ ] `POST /api/orchestration/sprints/from-template` - Create sprint from template
- [ ] `POST /api/orchestration/sprints/{code}/tasks/from-template` - Create tasks
- [ ] `POST /api/orchestration/sprints/{code}/sync` - Force file sync

### 6. Validation & Error Handling
- [ ] Validate template exists before generation
- [ ] Validate required variables provided
- [ ] Handle file system errors gracefully
- [ ] Rollback DB on file creation failure
- [ ] Emit error events for failed generations

### 7. Testing
- [ ] Test sprint generation from template
- [ ] Test task generation from template
- [ ] Test variable interpolation
- [ ] Test file sync service
- [ ] Test batch task creation
- [ ] Test error handling and rollback

---

## Deliverables

1. **Template Service**
   - `OrchestrationTemplateService` with loading/parsing
   - Template validation and variable interpolation

2. **Generation Controllers**
   - Sprint generation from template
   - Task generation from template
   - Batch task creation

3. **File Sync Service**
   - `OrchestrationFileSyncService` for DB → file system
   - Auto-sync on events
   - Directory structure management

4. **API Endpoints**
   - `GET /api/orchestration/templates` (list)
   - `GET /api/orchestration/templates/{type}/{name}` (get)
   - `POST /api/orchestration/sprints/from-template` (create sprint)
   - `POST /api/orchestration/sprints/{code}/tasks/from-template` (create tasks)

5. **Tests**
   - Template loading and parsing tests
   - Sprint/task generation tests
   - File sync tests

---

## Acceptance Criteria

- ✅ Can list available templates via API
- ✅ Can create sprint from template with variable interpolation
- ✅ Sprint creation generates `delegation/sprints/{code}/` directory
- ✅ Generated SPRINT.md matches template with variables replaced
- ✅ Can create task from template linked to sprint
- ✅ Task creation generates TASK.md and AGENT.yml files
- ✅ File changes auto-sync from DB (via events)
- ✅ Can create multiple tasks in one API call
- ✅ Template validation catches missing variables
- ✅ All tests pass

---

## Example Usage

### Create Sprint from Template
```bash
POST /api/orchestration/sprints/from-template
{
  "template_name": "default",
  "sprint_code": "my-feature-sprint",
  "title": "My Feature Sprint",
  "owner": "agent-001",
  "variables": {
    "start_date": "2025-10-13",
    "duration": "2 weeks",
    "goals": ["Implement feature X", "Fix bug Y"]
  }
}
```

**Result**:
- DB record in `orchestration_sprints`
- File: `delegation/sprints/my-feature-sprint/SPRINT.md`
- File: `delegation/sprints/my-feature-sprint/README.md`
- Event: `orchestration.sprint.generated`

### Create Tasks from Template
```bash
POST /api/orchestration/sprints/my-feature-sprint/tasks/from-template
{
  "template_name": "default",
  "tasks": [
    {
      "task_code": "phase-1-setup",
      "title": "Setup Phase",
      "priority": "P0",
      "variables": {
        "objectives": ["Set up infrastructure"],
        "deliverables": ["Config files"]
      }
    },
    {
      "task_code": "phase-2-implementation",
      "title": "Implementation Phase",
      "priority": "P1"
    }
  ]
}
```

**Result**:
- 2 DB records in `orchestration_tasks`
- Files: `delegation/sprints/my-feature-sprint/phase-1-setup/TASK.md`
- Files: `delegation/sprints/my-feature-sprint/phase-1-setup/AGENT.yml`
- Files: `delegation/sprints/my-feature-sprint/phase-2-implementation/TASK.md`
- Files: `delegation/sprints/my-feature-sprint/phase-2-implementation/AGENT.yml`
- Events: `orchestration.task.generated` (x2)

---

## Notes

- **Template Location**: `delegation/.templates/sprint-template/`, `task-template/`, `agent-base/`
- **File Ownership**: DB is source of truth, files are generated views
- **Sync Strategy**: Event-driven (sprint.created → sync file)
- **Rollback**: Delete files if DB creation fails
- **Permissions**: Ensure write access to `delegation/sprints/`
- **Performance**: Consider queuing file sync for large operations

---

## Dependencies

- Phase 1 (CRUD API) complete
- Phase 2 (Event system) complete
- Template files exist in `delegation/.templates/`
- File system write permissions

---

## References

- `delegation/.templates/GUIDE.md` - Template system guide
- `delegation/.templates/sprint-template/SPRINT_TEMPLATE.md`
- `delegation/.templates/task-template/TASK_TEMPLATE.md`
- Phase 1 controllers for CRUD patterns
- Phase 2 event service for emission

---

## Status Updates

**Started**: TBD  
**Progress**: 0/7 task groups  
**Blockers**: None  
**Completed**: TBD

---

**Task Hash**: TBD
