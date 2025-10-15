# Agent Workflow Process

**Standard operating procedure for agents working on tasks. Ensures context is preserved in the orchestration system.**

## Core Principle

**DO NOT write progress updates to disk files.** All context, progress, and decisions go into the orchestration system (sessions, sprints, tasks). The task record should contain everything needed to resume work.

---

## Workflow: INIT → Work → Complete → PR

### 1. INIT (Session Start)

**On every new conversation or resumption:**

```bash
/session-start
```

**What happens:**
- Auto-resumes existing session from `~/.fragments/session`
- Returns current context (active sprint, task)
- Provides instructions for next steps

**If no active task:**
```bash
/tasks                          # View available tasks
/sprint-activate SPRINT-XX      # Set working sprint
/task-activate T-XX             # Activate specific task
```

**Task is now active. Time tracking starts automatically.**

---

### 2. Planning (Before Writing Code)

**Read the task details:**
- Check `task.metadata.description` - What needs to be done
- Check `task.metadata.acceptance` - Completion criteria
- Check `task.metadata.dependencies` - Prerequisites

**Update task with your plan:**

Use MCP tool `session_task_update`:
```json
{
  "task_code": "T-XX",
  "agent_content": "## Plan\n1. Step one\n2. Step two\n3. Step three",
  "todo_progress": [
    {"id": "1", "content": "Step one", "status": "pending"},
    {"id": "2", "content": "Step two", "status": "pending"}
  ]
}
```

**Or via API:**
```http
POST /api/orchestration/tasks/save
{
  "task_code": "T-XX",
  "agent_content": "Detailed plan...",
  "todo_progress": [...]
}
```

---

### 3. Working (During Implementation)

**Update progress frequently (every 15-30 minutes or after key milestones):**

```json
{
  "task_code": "T-XX",
  "agent_content": "## Progress\n- Completed X\n- Working on Y\n- Next: Z",
  "todo_progress": [
    {"id": "1", "content": "Step one", "status": "completed"},
    {"id": "2", "content": "Step two", "status": "in_progress"}
  ]
}
```

**Add context notes:**
Use MCP tool `session_task_note`:
```json
{
  "note": "Discovered edge case with authentication flow. Need to handle expired tokens.",
  "task_code": "T-XX"
}
```

**If you encounter errors/blockers:**
```json
{
  "task_code": "T-XX",
  "agent_content": "## Current Status\n...\n\n## Blockers\n- Error: XYZ when doing ABC\n- Attempted: Solution 1, Solution 2\n- Next: Try Solution 3",
  "delegation_status": "blocked"  // If truly blocked
}
```

**Key:** Task should always reflect current state. If interrupted, next agent reads task and knows exactly where you left off.

---

### 4. Completion (Task Finished)

**Before deactivating:**

Update task with summary and completion status:
```json
{
  "task_code": "T-XX",
  "agent_content": "## Summary\nImplemented X, Y, Z.\n\n## Changes\n- File A: Added feature\n- File B: Updated logic\n\n## Testing\nAll tests passing.\n\n## Next Steps\nReady for PR. Consider adding integration test for edge case.",
  "status": "done",
  "delegation_status": "completed"
}
```

**Then deactivate:**
```bash
/task-deactivate --summary="Implemented session-based orchestration core system"
```

**Time tracking stops. Variance vs estimate shown.**

---

### 5. Pull Request

**When creating PR:**

1. **DO NOT create CHANGELOG entries or summary files**
2. **The PR description comes from sprint/task context:**

```markdown
## Summary
[From task.agent_content summary]

## Tasks Completed
- T-XX: [task_name]
- T-YY: [task_name]

## Changes
[From task.agent_content changes]

## Testing
[From task acceptance criteria]
```

**Use orchestration system to query:**
```http
GET /api/orchestration/tasks?sprint=SPRINT-XX&status=completed
```

---

## Task Content Structure

**Every task should have:**

### Required in `agent_content`:
```markdown
## Plan
High-level approach and steps

## Progress
Current status and what's been done

## Implementation Details
Key decisions, approaches, patterns used

## Testing
What was tested and results

## Summary
Final outcome (on completion)

## Next Steps
What should happen next (always)

## Blockers/Issues
Any unresolved problems or errors (if applicable)
```

### Required in `todo_progress`:
```json
[
  {"id": "1", "content": "Specific atomic step", "status": "completed"},
  {"id": "2", "content": "Another step", "status": "in_progress"},
  {"id": "3", "content": "Final step", "status": "pending"}
]
```

### Optional but recommended:
- `context`: JSON with technical details (file paths, function names, patterns)
- `metadata.files_modified`: Array of files changed
- `metadata.tests_added`: Boolean or count

---

## Session Management

### When to update session context:

**Every 30 minutes or major milestone:**
```bash
/session-status  # Verify context is current
```

**Before taking a break or expecting interruption:**
```json
{
  "task_code": "T-XX",
  "agent_content": "## Current Position\nJust finished implementing X. About to start Y.\n\nFiles open:\n- src/ServiceA.php (line 45)\n- tests/ServiceATest.php\n\n**Resume by**: Running tests for ServiceA"
}
```

**End of work session:**
```bash
/session-end --summary="Completed 3 tasks: T-01, T-02, T-03. Sprint is 60% complete."
```

---

## Recovery Scenario

**If conversation lost/agent fails:**

1. New agent: `/session-start` (auto-resumes)
2. System returns: Active task, sprint, last activity time
3. Agent reads: `task.agent_content`, `task.todo_progress`, `task.context`
4. Agent knows: Exactly what was being done, what's next, where in the code
5. **Data loss: < 30 seconds** (last update) vs entire conversation

---

## Anti-Patterns (DO NOT DO)

❌ Writing progress to `PROGRESS.md` files  
❌ Creating `TODO.txt` files  
❌ Documenting next steps in code comments  
❌ Storing context in git commit messages only  
❌ Keeping todo list in conversation only  

✅ **Everything goes in the orchestration system**

---

## Quick Reference

| Action | Command/Tool |
|--------|--------------|
| Start session | `/session-start` |
| Activate task | `/task-activate T-XX` |
| Update progress | `session_task_update` (MCP) |
| Add note | `session_task_note` (MCP) |
| Mark complete | Update status to `done`, then `/task-deactivate` |
| End session | `/session-end --summary="..."` |

**Remember:** The task is your notebook. Future you (or another agent) will thank you for detailed context.
