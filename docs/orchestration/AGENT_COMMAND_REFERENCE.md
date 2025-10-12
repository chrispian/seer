# Agent Command Reference

Quick reference for orchestration commands. Use these to track work in sessions.

## Session Management

### Start/Resume Session
```bash
/session-start              # Start new session (auto-resumes if exists)
/session-resume [KEY]       # Explicitly resume session
/session-status             # View current session context
/session-end [--summary=""] # End session
```

**Auto-resume:** Sessions persist in `~/.fragments/session` and auto-resume on `/session-start`

## Context Management

### Sprint Activation
```bash
/sprint-activate SPRINT-XX  # Set active sprint
```
**Effect:** Adds sprint to context stack, updates status to in_progress

### Task Activation
```bash
/task-activate T-XX         # Set active task
/task-deactivate            # Remove task from context
```
**Effect:** 
- Activates task (status → in_progress)
- **Starts automatic time tracking**
- Deactivate stops timer, shows variance

## MCP Tools (For Programmatic Use)

### Session Tools
- `session_start` - Create new session
- `session_status` - Get session state
- `session_end` - Complete session

### Contextual Tools
- `session_sprint_activate` - Activate sprint in session
- `session_task_activate` - Activate task in session
- `session_task_deactivate` - Deactivate task
- `session_task_update` - Update task (uses session context)
- `session_task_note` - Add note to task

### Standard Tools (Session-aware)
- `task_save` - Create/update task (accepts optional `session_key`)
- `task_status` - Update task status (accepts optional `session_key`)

## Task Management

### View Tasks
```bash
/tasks                      # List all tasks
/tasks --sprint=SPRINT-XX   # Filter by sprint
```

### Update Task
```bash
/task-update T-XX --status=in_progress
/task-update T-XX --note="Progress update"
```

## API Endpoints

### Session API
```http
POST   /api/sessions                    # Start session
GET    /api/sessions                    # List sessions
GET    /api/sessions/{key}/status       # Get status
POST   /api/sessions/{key}/end          # End session
POST   /api/sessions/{key}/pause        # Pause
POST   /api/sessions/{key}/resume       # Resume
```

**Headers:**
- `X-Session-Key: SESSION-XXX` - Include in requests for auto-context

### Orchestration API
```http
GET    /api/orchestration/sprints       # List sprints
GET    /api/orchestration/tasks         # List tasks
POST   /api/orchestration/tasks/save    # Create/update task
POST   /api/orchestration/tasks/status  # Update status
```

## Key Concepts

**Context Stack:** Hierarchical context (project → sprint → task)
- Commands infer context automatically
- Reduces need for explicit IDs

**Time Tracking:** Automatic via task activate/deactivate
- Start: `/task-activate T-XX`
- Stop: `/task-deactivate`
- Compares actual vs estimate

**Instructions:** Every command returns structured guidance
- `instructions.next_actions` - What to do next
- `instructions.suggested_commands` - Commands to run
- `instructions.context_reminder` - Current context

**Recovery (INIT Pattern):**
1. Session interrupted → data persists in DB
2. New agent: `/session-resume SESSION-XXX`
3. System returns full context + instructions
4. Data loss < 30 seconds (vs entire conversation)
