# Orchestration System Architecture

**Version**: 2.0  
**Last Updated**: 2025-10-13  
**Status**: Production Ready

## Overview

The Orchestration System is a comprehensive workflow management framework that guides agents through structured task execution. It combines phase-driven workflows, bug tracking, context search, and git integration to create a flexible, observable, and maintainable system for collaborative work between humans and AI agents.

## Core Philosophy

1. **Template-Driven**: Workflows defined in YAML, easy to customize without code changes
2. **Phase-Based**: Clear progression through defined stages with validation gates
3. **Observable**: All actions logged as events, full audit trail maintained
4. **Flexible**: User override capability when validation is too strict
5. **Context-Rich**: Searchable history across sessions, tasks, and sprints
6. **Git-Integrated**: Automatic tracking of commits and pull requests

## System Components

### 1. Session State Machine

**Purpose**: Enforce structured workflow progression through 6 sequential phases.

**Phases**:
```
INTAKE → RESEARCH → PLAN → EXECUTE → REVIEW → CLOSE
```

**Key Features**:
- **Phase Validation**: Checks required fields and artifacts before transition
- **User Override**: `--override` flag bypasses validation when needed
- **Next-Step Instructions**: Each phase completion returns guidance for the next phase
- **Session Resume**: Existing active sessions can be continued seamlessly

**Components**:
- `OrchestrationSessionService` - Core state machine logic
- `OrchestrationPhase` enum - Phase definitions with ordering
- `workflow.yaml` - Template defining phase requirements and instructions
- CLI Commands: `session-start`, `session-end`, `phase-complete`

**State Storage**:
```json
{
  "session": {
    "session_key": "sess_TSK-ABC123_20250113_143022",
    "started_at": "2025-01-13T14:30:22Z",
    "current_phase": "execute",
    "active": true,
    "phase_history": [
      {"phase": "intake", "completed_at": "2025-01-13T14:45:00Z"},
      {"phase": "research", "completed_at": "2025-01-13T15:00:00Z"}
    ]
  }
}
```

### 2. Bug Tracking System

**Purpose**: Intelligent bug deduplication and occurrence tracking.

**Key Features**:
- **Smart Hashing**: Normalizes errors to catch duplicates (strips IDs, addresses, line numbers)
- **Single Record**: One record per unique bug, occurrence count in context
- **Interactive Prompts**: User chooses action (fix now / log and continue / provide context)
- **Error Classification**: Auto-detects type (syntax, runtime, database, network, etc.)

**Components**:
- `OrchestrationBugService` - Bug hashing and deduplication logic
- `OrchestrationBug` model - Bug storage with occurrence tracking
- `orchestration_bugs` table - PostgreSQL storage
- CLI Command: `bug-log`

**Hash Generation**:
```php
// Normalized components for consistent hashing
hash('sha256', implode('|', [
    normalizeErrorMessage($error),  // Replace numbers with #, strings with STRING
    normalizeFilePath($file),       // Relative to project root
    $lineNumber,
    extractStackTraceSignature()    // First line only, normalized
]));
```

**Bug Record Structure**:
```json
{
  "bug_hash": "0acc79b37a5989dfd...",
  "task_code": "TSK-001",
  "error_message": "Undefined variable: foo",
  "file_path": "app/Services/Test.php",
  "line_number": 42,
  "context": {
    "captured_at": "2025-01-13T14:30:22Z",
    "error_type": "type",
    "occurrence_count": 3,
    "last_seen": "2025-01-13T15:00:00Z",
    "last_task_code": "TSK-002"
  }
}
```

### 3. Context Search

**Purpose**: Fast, scoped search across orchestration events and history.

**Search Scopes**:
- **SESSION**: Current session's events only
- **TASK**: All events for a specific task
- **SPRINT**: All tasks within a sprint
- **PROJECT**: Entire project history

**Key Features**:
- **Full-Text Search**: PostgreSQL ILIKE on event_type and payload
- **Relevance Scoring**: Term matching + recency weighting
- **Caching**: Redis with 1-hour TTL (configurable)
- **Summary Statistics**: Event counts by type, time spans
- **pgvector Ready**: Architecture supports future vector search upgrade

**Components**:
- `OrchestrationContextSearchService` - Search and filtering logic
- `ContextScope` enum - Search scope definitions
- CLI Command: `context-search`

**Search Flow**:
```
Query → Extract Terms → Apply Scope Filter → Full-Text Search → 
Calculate Relevance → Cache Results → Return SearchResults
```

**Example Queries**:
```bash
# Search task events
orchestration:context-search "validation" --scope=task --id=TSK-001

# View summary stats
orchestration:context-search "any" --scope=sprint --id=SPR-001 --summary

# Fresh search (bypass cache)
orchestration:context-search "error" --scope=project --no-cache
```

### 4. Git Integration

**Purpose**: Track commits and link pull requests to tasks automatically.

**Key Features**:
- **Commit Capture**: Store hash, message, author, phase at any point
- **PR Linking**: Associate GitHub/GitLab PR URLs with tasks
- **CHANGES.md Generation**: Auto-generate from git diff with file lists
- **Auto-Commit**: Optional automatic commits with template messages
- **Status Checking**: Query repository state, branch, modified files

**Components**:
- `OrchestrationGitService` - Git operations wrapper
- CLI Command: `git-link-pr`

**Tracked Commit Structure**:
```json
{
  "commits": [
    {
      "hash": "16124713051261b28ee5da030c2bfc7ce228cc1d",
      "short_hash": "1612471",
      "captured_at": "2025-01-13T14:30:00Z",
      "phase": "execute",
      "message": "feat: implement new feature",
      "author": "John Doe <john@example.com>"
    }
  ],
  "pull_request": {
    "url": "https://github.com/org/repo/pull/123",
    "number": 123,
    "linked_at": "2025-01-13T15:00:00Z"
  }
}
```

**CHANGES.md Template**:
```markdown
# Changes Summary

**Task**: Feature Implementation (TSK-001)
**Date**: 2025-01-13

## Overview
Task description here...

## Modified Files
- app/Services/Feature.php
- tests/Feature/FeatureTest.php

## Diff Summary
2 files changed, 150 insertions(+), 20 deletions(-)

## Related Commits
- 1612471 - feat: implement new feature (Phase: execute)
```

## Data Model

### Database Tables

**orchestration_sprints**:
- Core sprint metadata
- `sprint_code`, `title`, `status`, `metadata`

**orchestration_tasks**:
- Task tracking with session state
- `task_code`, `sprint_id`, `status`, `priority`, `metadata`
- Session state stored in `metadata->session`
- Commits stored in `metadata->commits`
- PR link stored in `metadata->pull_request`

**orchestration_events**:
- Complete event log
- `event_type`, `entity_type`, `entity_id`, `session_key`, `payload`
- Indexed for fast querying by type, entity, session

**orchestration_bugs**:
- Bug tracking with deduplication
- `bug_hash` (unique), `task_code`, `error_message`, `context`
- Occurrence count in `context->occurrence_count`

### Event Types

**Session Events**:
- `orchestration.session.start`
- `orchestration.session.end`

**Phase Events**:
- `orchestration.phase.{phase}.start`
- `orchestration.phase.{phase}.end`
- `orchestration.phase.override`

**Task Events**:
- `orchestration.task.created`
- `orchestration.task.updated`
- `orchestration.task.status_updated`

**Sprint Events**:
- `orchestration.sprint.created`
- `orchestration.sprint.updated`

## Configuration

**File**: `config/orchestration.php`

```php
'workflow' => [
    'allow_phase_skip' => false,           // Enforce sequential phases
    'allow_user_override' => true,         // Enable --override flag
    'require_artifact_validation' => true, // Check artifacts exist
    'sync_files_on_close' => true,        // Write files at session end
],

'search' => [
    'driver' => 'fulltext',                // 'fulltext' or 'pgvector'
    'cache_ttl' => 3600,                   // 1 hour cache
],

'git' => [
    'enabled' => true,                     // Enable git integration
    'auto_commit' => false,                // Auto-commit changes
    'track_commits' => true,               // Track commits to tasks
    'commit_message_template' => 'feat({sprint_code}): {task_title} [TSK-{task_code}]',
],
```

## Workflow Template

**File**: `resources/templates/orchestration/workflow.yaml`

Defines for each phase:
- `goal` - What the phase accomplishes
- `description` - Detailed instructions for agents
- `artifacts.required` - Files that must exist
- `artifacts.optional` - Recommended files
- `validation` - Field requirements
- `events` - Events to emit
- `hooks` - Git operations to trigger
- `next_step` - Instructions displayed after completion

**Example Phase Definition**:
```yaml
execute:
  order: 4
  goal: "Implement changes according to the plan"
  artifacts:
    required: []
    optional: [BUGS.md, TOOLS.md]
  hooks:
    on_start: [git.capture_commit_hash]
    on_end: [git.capture_final_commit]
  events:
    - execute.start
    - execute.step_complete
    - execute.end
  next_step: |
    Now transition to REVIEW phase.
    Actions: Run tests, generate CHANGES.md, present summary.
    When ready: orchestration:phase-complete execute
```

## Agent Workflow

### Basic Task Execution

```bash
# 1. Start session (begins in INTAKE phase)
orchestration:session-start TSK-001

# Agent: Ask clarifying questions, create TASK.md

# 2. Complete intake
orchestration:phase-complete TSK-001

# Agent: Search codebase, gather context, create CONTEXT.md

# 3. Complete research
orchestration:phase-complete TSK-001

# Agent: Create implementation plan, generate PLAN.md and TODO.md

# 4. Complete planning
orchestration:phase-complete TSK-001

# Agent: Implement changes, track bugs, update TODO.md

# 5. Complete execution
orchestration:phase-complete TSK-001

# Agent: Run tests, create CHANGES.md, present results

# 6. Complete review (after user approval)
orchestration:phase-complete TSK-001

# Agent: Finalize docs, sync files, mark complete

# 7. End session
orchestration:session-end TSK-001
```

### Bug Logging During Work

```bash
# Log bug when encountered
orchestration:bug-log "Undefined variable: foo" \
  --task-code=TSK-001 \
  --file=app/Services/Test.php \
  --line=42

# System checks for duplicates, prompts for action
# Options: Fix now / Log and continue / Provide context
```

### Context Search During Research

```bash
# Find related work
orchestration:context-search "authentication" \
  --scope=project \
  --limit=10

# Check task history
orchestration:context-search "validation error" \
  --scope=task \
  --id=TSK-001

# View sprint summary
orchestration:context-search "any" \
  --scope=sprint \
  --id=SPR-001 \
  --summary
```

### Git Integration at Close

```bash
# Link PR to task (usually at close phase)
orchestration:git-link-pr TSK-001 \
  https://github.com/org/repo/pull/123

# Commits are automatically tracked during execute phase
```

## Human Workflow

### Reviewing Agent Work

```bash
# Check current phase
orchestration:context-search "" --scope=task --id=TSK-001 --summary

# View recent events
orchestration:context-search "phase" --scope=task --id=TSK-001

# Review session timeline
# All events visible in orchestration_events table
```

### Handling Validation Failures

When agent encounters validation errors:

```bash
# Agent attempts phase completion
orchestration:phase-complete TSK-001

# Output shows missing requirements:
# ✗ Required artifact missing: PLAN.md
# ⚠ Recommended field missing: tags

# Human options:
# 1. Tell agent to complete requirements
# 2. Override validation if acceptable
orchestration:phase-complete TSK-001 --override
```

### Managing Bug Reports

```bash
# View all bugs for task
SELECT * FROM orchestration_bugs WHERE task_code = 'TSK-001';

# Check duplicate bugs across project
SELECT bug_hash, error_message, 
       context->>'occurrence_count' as count
FROM orchestration_bugs
WHERE context->>'occurrence_count'::int > 1;
```

## Performance Considerations

### Event Volume

- **Expected**: 5-10 events per phase completion
- **Per Task**: ~50-100 events for full lifecycle
- **Per Sprint** (20 tasks): ~1,000-2,000 events

### Search Performance

- **Full-Text**: ~10-50ms for typical queries (with cache)
- **Cache Hit Rate**: >80% for repeated queries
- **Scope Impact**:
  - SESSION: Fastest (<10ms)
  - TASK: Fast (10-20ms)
  - SPRINT: Medium (20-50ms)
  - PROJECT: Slower (50-200ms, use sparingly)

### Optimization Tips

1. **Use Caching**: Default 1-hour TTL is appropriate for most cases
2. **Narrow Scope**: Use SESSION or TASK scope when possible
3. **Event Retention**: Consider archiving events >90 days old
4. **Bug Cleanup**: Resolve old bugs to reduce table size

## Extension Points

### Future Enhancements

1. **Vector Search**: Swap `driver` to `pgvector` in config
   - Semantic similarity search
   - Better relevance for natural language queries

2. **File Sync**: Currently disabled, can enable via config
   - Mirror task artifacts to file system
   - Useful for git-based workflows

3. **Custom Phases**: Extend workflow.yaml
   - Define workflow variants per task type
   - Example: `bug_fix: [intake, research, execute, review, close]`

4. **Webhook Integration**: Event emission hooks
   - Trigger external systems on phase transitions
   - Slack/Discord notifications

5. **AI-Assisted Search**: LLM query expansion
   - Convert natural language to search terms
   - Suggest related context automatically

## Troubleshooting

### Session Not Starting

**Symptom**: `orchestration:session-start` fails or returns existing session

**Solution**:
```bash
# Check if session is active
orchestration:context-search "" --scope=task --id=TSK-001 --summary

# If needed, force end old session
orchestration:session-end TSK-001 --force

# Start new session
orchestration:session-start TSK-001
```

### Phase Validation Always Failing

**Symptom**: Cannot complete phase even after meeting requirements

**Check**:
1. Verify artifact exists in `task.metadata->artifacts`
2. Check required fields in workflow.yaml match task metadata structure
3. Use `--override` flag if validation is incorrect

### Search Returns No Results

**Symptom**: Context search finds nothing despite events existing

**Debug**:
```bash
# Check event count
orchestration:context-search "" --scope=task --id=TSK-001 --summary

# Verify scope ID is correct
# Task code should match exactly (case-sensitive)

# Try broader scope
orchestration:context-search "term" --scope=project
```

### Bug Not Detected as Duplicate

**Symptom**: Same error creates multiple bug records

**Cause**: Hash normalization may be too aggressive or not aggressive enough

**Solution**:
- Check `OrchestrationBugService::normalizeErrorMessage()`
- Adjust normalization patterns if needed
- Bug hashes are deterministic, so adjust code and re-log

## Security Considerations

1. **Session Keys**: Not cryptographically secure, used for correlation only
2. **Event Payloads**: May contain sensitive data, sanitize before logging
3. **Git Integration**: Uses local git credentials, no separate auth
4. **Search Access**: No built-in ACL, relies on application-level permissions

## Summary

The Orchestration System provides a complete framework for structured task execution with:

- ✅ Clear phase progression with validation
- ✅ Intelligent bug tracking and deduplication  
- ✅ Fast, scoped context search
- ✅ Automatic git integration
- ✅ Full observability via events
- ✅ Template-driven flexibility
- ✅ Human-agent collaboration support

**Related Documentation**:
- [Session State Machine Implementation](./SESSION_STATE_MACHINE_IMPLEMENTATION.md)
- [Task Session Lifecycle](./TASK_SESSION_LIFECYCLE.md)
- [API Reference](./API_REFERENCE.md)
- [Agent Workflow Process](./AGENT_WORKFLOW_PROCESS.md)
