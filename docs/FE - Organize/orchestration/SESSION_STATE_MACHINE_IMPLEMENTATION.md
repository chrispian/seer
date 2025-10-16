# Orchestration Session State Machine - Sprint 1 Implementation

**Status**: ✅ Complete  
**Date**: 2025-01-13  
**Sprint**: Orchestration API v2 - Session Workflow Enhancement

## Overview

Implemented a phase-driven state machine for orchestration task sessions. The system enforces sequential workflow progression (Intake → Research → Plan → Execute → Review → Close) with validation, artifact tracking, and clear next-step instructions for agents.

## Core Components

### 1. OrchestrationPhase Enum
**File**: `app/Enums/OrchestrationPhase.php`

Defines six sequential phases:
- `INTAKE` - Clarify requirements
- `RESEARCH` - Gather context
- `PLAN` - Create implementation plan
- `EXECUTE` - Implement changes
- `REVIEW` - Test and iterate
- `CLOSE` - Finalize and document

**Features**:
- Order enforcement via `order()` method
- Transition validation via `canTransitionTo()`
- Automatic next phase calculation via `next()`

### 2. ContextScope Enum
**File**: `app/Enums/ContextScope.php`

Defines search scopes for future context search feature:
- `SESSION` - Current session events
- `TASK` - All events for task
- `SPRINT` - All tasks in sprint
- `PROJECT` - All sprints

### 3. Workflow Configuration
**File**: `resources/templates/orchestration/workflow.yaml`

**460 lines** of YAML defining:
- Phase goals and descriptions
- Required/optional artifacts per phase
- Validation rules (required_fields, warn_if_missing)
- Event types emitted
- Hooks (git, testing, file sync)
- Next-step instructions (shown to agents)

**Example Phase Config**:
```yaml
intake:
  order: 1
  goal: "Clarify task requirements and confirm understanding"
  artifacts:
    required:
      - name: TASK.md
        template: task_template
  validation:
    required_fields: [title, status, description]
    warn_if_missing: [tags, project_id]
  events: [intake.start, intake.confirmed, intake.end]
  next_step: |
    → Next Phase: RESEARCH
    Actions:
    1. Execute tool calls to explore code
    2. Document findings in CONTEXT.md
    When ready: orchestration:phase-complete intake
```

### 4. OrchestrationSessionService
**File**: `app/Services/Orchestration/OrchestrationSessionService.php`  
**480 lines**

**Key Methods**:
- `startSession(taskCode)` - Initialize new session, set phase to INTAKE
- `getCurrentPhase(taskCode)` - Get active phase for task
- `completePhase(taskCode, userOverride)` - Validate and transition to next phase
- `transitionToPhase(task, toPhase)` - Force transition with validation
- `endSession(taskCode)` - Close session, compact memory
- `isActiveSession(taskCode)` - Check if task has active session
- `promptAppendOrContinue(taskCode)` - Handle session resume logic

**Session State Storage**:
Stored in `orchestration_tasks.metadata->session`:
```json
{
  "session_key": "sess_TSK-ABC123_20250113_143022",
  "started_at": "2025-01-13T14:30:22Z",
  "current_phase": "research",
  "active": true,
  "phase_history": [
    {"phase": "intake", "completed_at": "2025-01-13T14:45:00Z"}
  ]
}
```

**Phase Validation**:
Before allowing phase completion:
1. Check required fields exist in task metadata
2. Verify required artifacts are present
3. Emit warnings for missing optional items
4. Allow user override with `--override` flag
5. Log override with reason in metadata

**NextStepInstructions Class**:
Returns structured guidance:
- Current phase goal
- Required/optional artifacts
- Formatted next-step text (from workflow.yaml)
- Completion command to run
- Next phase name

### 5. CLI Commands

#### orchestration:session-start
**File**: `app/Console/Commands/OrchestrationSessionStart.php`

**Usage**:
```bash
php artisan orchestration:session-start TSK-ABC123
php artisan orchestration:session-start TSK-ABC123 --session-key=custom_key
```

**Flow**:
1. Check if active session exists
2. If yes: prompt user (continue / restart / new)
3. If no: start new session
4. Display next-step instructions for INTAKE phase

#### orchestration:phase-complete
**File**: `app/Console/Commands/OrchestrationPhaseComplete.php`

**Usage**:
```bash
php artisan orchestration:phase-complete TSK-ABC123
php artisan orchestration:phase-complete TSK-ABC123 --override
```

**Flow**:
1. Validate phase completion requirements
2. If validation fails: show errors/warnings, suggest `--override`
3. If passed: transition to next phase
4. Display next-step instructions

**Example Output**:
```
✓ Phase Intake completed successfully

═══════════════════════════════════════════════════════════════
 PHASE: Research
═══════════════════════════════════════════════════════════════

Goal: Gather context from codebase, documentation, and related work

Required Artifacts:
  • CONTEXT.md

───────────────────────────────────────────────────────────────
Next Steps:
───────────────────────────────────────────────────────────────

In this phase, the agent must:
1. Search codebase for relevant files
2. Review related tasks and documentation
3. Document findings in CONTEXT.md

When ready: orchestration:phase-complete research
```

#### orchestration:session-end
**File**: `app/Console/Commands/OrchestrationSessionEnd.php`

**Usage**:
```bash
php artisan orchestration:session-end TSK-ABC123
php artisan orchestration:session-end TSK-ABC123 --force
```

**Flow**:
1. Check if in CLOSE phase
2. If not: error (unless `--force`)
3. If yes: end session, compact memory, mark inactive

## Event Emissions

All phase transitions emit events via `OrchestrationEventService`:

```php
orchestration.session.start      // Session begins
orchestration.phase.intake.start // Each phase start
orchestration.phase.intake.end   // Each phase end
orchestration.phase.override     // User overrides validation
orchestration.session.end        // Session closes
```

Events stored in `orchestration_events` table with:
- `session_key` for correlation
- `entity_type` = 'task'
- `payload` with phase details

## Integration Points

### With Existing Services

**MemoryService**:
- `compactToPostop()` called on session end
- Ephemeral scratch data archived to postop

**OrchestrationEventService**:
- All transitions emit events
- Session timeline reconstructable

### Future Integration Hooks

**OrchestrationFileSyncService**:
- Will sync artifacts to disk at phase transitions
- Controlled by `workflow.yaml` settings

**OrchestrationGitService** (Sprint 4):
- Git hooks defined in workflow.yaml
- Commit capture on execute.start/end

**OrchestrationBugService** (Sprint 2):
- Bug tracking during execute phase
- Hooks: `on_bug_found`

## Configuration (Future)

Will add to `config/orchestration.php`:
```php
'workflow' => [
    'allow_phase_skip' => false,
    'allow_user_override' => true,
    'require_artifact_validation' => true,
    'sync_files_on_phase_complete' => false,
    'sync_files_on_close' => true,
],
```

## Usage Example

**Agent Workflow**:
```bash
# 1. Start session
php artisan orchestration:session-start TSK-ABC123
# Output: Instructions for INTAKE phase

# 2. Agent performs intake (asks questions, creates TASK.md)
# ... agent work ...

# 3. Complete intake
php artisan orchestration:phase-complete TSK-ABC123
# Output: Instructions for RESEARCH phase

# 4. Agent performs research (searches code, creates CONTEXT.md)
# ... agent work ...

# 5. Complete research
php artisan orchestration:phase-complete TSK-ABC123
# Output: Instructions for PLAN phase

# 6. Continue through all phases...

# 7. End session
php artisan orchestration:session-end TSK-ABC123
# Session closed, artifacts finalized
```

## Testing Strategy

### Manual Testing
1. Create test task: `TSK-TEST001`
2. Start session and verify phase = INTAKE
3. Attempt phase-complete without TASK.md → should fail
4. Add TASK.md to metadata, retry → should pass
5. Verify phase transitions through all 6 phases
6. Test `--override` flag bypasses validation
7. Test session resume (start existing session)

### Automated Tests (Next)
File: `tests/Feature/OrchestrationSessionWorkflowTest.php`
- Test session start/end lifecycle
- Test phase transition validation
- Test user override behavior
- Test session resume logic
- Test artifact validation
- Test event emissions

## Key Design Decisions

### 1. Metadata Storage Over New Table
**Decision**: Store session state in `orchestration_tasks.metadata->session`  
**Rationale**: 
- Simpler schema (no joins)
- Session is task-scoped (1:1 relationship)
- Metadata is already JSON, flexible

### 2. Workflow Config in YAML
**Decision**: Use YAML file vs database config  
**Rationale**:
- Version controlled
- Easy to read/edit
- No DB migration for workflow changes
- Can be customized per deployment

### 3. Validation with User Override
**Decision**: Block invalid transitions but allow override  
**Rationale**:
- Agent compliance is goal, not enforcement
- User always has final say
- Log overrides for analysis

### 4. NextStepInstructions as Class
**Decision**: Return structured object vs array  
**Rationale**:
- Type safety
- Clean toString() for CLI output
- Easy to extend (add fields without breaking)

### 5. Phase Order Enforcement
**Decision**: Only allow sequential transitions (no skipping)  
**Rationale**:
- Forces complete workflow
- Simplifies state machine logic
- Can relax later if needed

## File Summary

**New Files** (7):
1. `app/Enums/OrchestrationPhase.php` (65 lines)
2. `app/Enums/ContextScope.php` (25 lines)
3. `app/Services/Orchestration/OrchestrationSessionService.php` (480 lines)
4. `app/Console/Commands/OrchestrationSessionStart.php` (85 lines)
5. `app/Console/Commands/OrchestrationPhaseComplete.php` (70 lines)
6. `app/Console/Commands/OrchestrationSessionEnd.php` (75 lines)
7. `resources/templates/orchestration/workflow.yaml` (460 lines)

**Total**: 1,260 lines of production code

## Next Steps

### Sprint 2: Bug Tracking
- Create `OrchestrationBugService`
- Implement bug hashing + duplicate detection
- Add `orchestration_bugs` table migration
- Integrate with execute phase hooks

### Sprint 3: Context Search
- Create `OrchestrationContextSearchService`
- Implement full-text search with scope filtering
- Add CLI command: `orchestration:context-search`
- Prep for pgvector upgrade

### Sprint 4: Git Integration
- Create `OrchestrationGitService`
- Capture commit hashes at execute phase
- Link PRs to task metadata
- Generate CHANGES.md from git diff

### Sprint 5: Polish & Testing
- Comprehensive test suite
- File sync integration
- Memory pinning feature
- End-to-end workflow validation

## Dependencies

- ✅ `symfony/yaml` ^7.3 (already installed)
- ✅ `OrchestrationEventService` (from v2 sprint)
- ✅ `MemoryService` (from earlier work)
- ✅ `OrchestrationTask` model

## Notes

- No database migrations required (uses existing metadata column)
- Backward compatible (tasks without sessions work normally)
- CLI commands ready for MCP tool integration
- Event system enables future replay/debugging features
