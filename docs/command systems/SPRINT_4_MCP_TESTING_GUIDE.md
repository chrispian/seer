# Sprint 4 - MCP Tools Testing Guide

**Date**: 2025-10-09  
**Status**: Ready for Testing  
**MCP Server**: `orchestration:mcp`

---

## Overview

All orchestration write operations now exposed via MCP using unified command architecture:
- **6 write tools** updated to use unified commands
- **Read tools** remain unchanged (already working)
- All tools accessible via snake_case names through Claude Desktop

---

## MCP Server Setup

### 1. Server Configuration

The MCP server is already configured in `.mcp.json`:

```json
{
  "mcpServers": {
    "orch": {
      "command": "php",
      "args": ["./artisan", "orchestration:mcp"]
    }
  }
}
```

### 2. Start MCP Server

```bash
# From project root
cd /Users/chrispian/Projects/seer
php artisan orchestration:mcp
```

The server will start and wait for connections from Claude Desktop.

### 3. Claude Desktop Configuration

Ensure Claude Desktop is configured to connect to the MCP server.  
The server should appear as `orch` in Claude Desktop's MCP server list.

---

## Available MCP Tools

### Sprint Write Operations

#### 1. `orchestration_sprints_save`
Create or update a sprint.

**Example Usage**:
```
Please use the orchestration_sprints_save tool to create a new sprint:
- code: "SPRINT-100"
- title: "Q4 2025 Backend Improvements"
- priority: "High"
- status: "Planned"
- starts_on: "2025-10-15"
- ends_on: "2025-10-29"
```

**Parameters**:
- `code` (required): Sprint code (e.g., "SPRINT-100" or just "100")
- `title`: Human-friendly sprint title
- `priority`: Priority label
- `status`: Status text (e.g., "Planned", "In Progress", "Completed")
- `estimate`: Estimate string
- `notes`: Array of note strings
- `starts_on`: Start date (Y-m-d format)
- `ends_on`: End date (Y-m-d format)
- `meta`: Additional metadata object
- `upsert`: Boolean (default true) - update if exists

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "id": "...",
    "code": "SPRINT-100",
    "title": "Q4 2025 Backend Improvements",
    "priority": "High",
    "status": "Planned",
    "starts_on": "2025-10-15",
    "ends_on": "2025-10-29",
    "stats": {
      "total": 0,
      "completed": 0,
      "in_progress": 0,
      "blocked": 0,
      "unassigned": 0
    }
  }
}
```

---

#### 2. `orchestration_sprints_status`
Update sprint status and optionally add a note.

**Example Usage**:
```
Use orchestration_sprints_status to update SPRINT-100:
- sprint: "SPRINT-100"
- status: "In Progress"
- note: "Kicked off sprint planning meeting"
```

**Parameters**:
- `sprint` (required): Sprint code or UUID
- `status` (required): New status label
- `note`: Optional note to append to sprint notes

---

#### 3. `orchestration_sprints_attach_tasks`
Attach tasks to a sprint.

**Example Usage**:
```
Use orchestration_sprints_attach_tasks to add tasks to SPRINT-100:
- sprint: "SPRINT-100"
- tasks: ["T-BE-01", "T-BE-02", "T-BE-03"]
```

**Parameters**:
- `sprint` (required): Sprint code or UUID
- `tasks` (required): Array of task codes or UUIDs
- `tasks_limit`: Number of tasks to include in response (default 10)
- `include_assignments`: Include assignment details (default false)

---

### Task Write Operations

#### 4. `orchestration_tasks_save`
Create or update a task.

**Example Usage**:
```
Use orchestration_tasks_save to create a new task:
- task_code: "T-BE-01"
- task_name: "Refactor authentication service"
- description: "Improve security and add 2FA support"
- sprint_code: "SPRINT-100"
- status: "todo"
- delegation_status: "unassigned"
- priority: "high"
- estimate_text: "3-4 days"
- estimated_hours: 28
- agent_content: "Review current auth implementation, identify security gaps, implement 2FA using TOTP standard"
```

**Parameters**:
- `task_code` (required): Task code (e.g., "T-BE-01")
- `task_name`: Human-friendly task name
- `description`: Task description
- `sprint_code`: Sprint to associate with
- `status`: Work item status (todo, in_progress, done, etc.)
- `delegation_status`: unassigned, assigned, in_progress, blocked, completed, cancelled
- `priority`: low, medium, high, critical
- `estimate_text`: Human-readable estimate
- `estimated_hours`: Numeric hours
- `dependencies`: Array of task codes this depends on
- `tags`: Array of tags
- `agent_content`: Detailed instructions for agents
- `type`: Task type (task, feature, bug, etc.)
- `acceptance`: Acceptance criteria
- `upsert`: Boolean (default true)

---

#### 5. `orchestration_tasks_assign`
Assign a task to an agent.

**Example Usage**:
```
Use orchestration_tasks_assign to assign T-BE-01:
- task: "T-BE-01"
- agent: "backend-engineer-template"
- status: "assigned"
- note: "Assigned for Q4 sprint work"
```

**Parameters**:
- `task` (required): Task code or UUID
- `agent` (required): Agent slug, name, or UUID
- `status`: Delegation status (default "assigned")
- `note`: Optional note stored with assignment
- `context`: Additional context payload object

---

#### 6. `orchestration_tasks_status`
Update task delegation status.

**Example Usage**:
```
Use orchestration_tasks_status to mark T-BE-01 as in progress:
- task: "T-BE-01"
- status: "in_progress"
- note: "Started refactoring auth service"
```

**Parameters**:
- `task` (required): Task code or UUID
- `status` (required): unassigned, assigned, in_progress, blocked, completed, cancelled
- `note`: Optional note for delegation history

---

## Testing Scenarios

### Scenario 1: Complete Sprint Workflow

**Step 1**: Create a sprint
```
Create SPRINT-TEST-01 with title "Test Sprint" and status "Planned"
```

**Step 2**: Create tasks for the sprint
```
Create task T-TEST-TASK-01 with name "Test Task 1" in SPRINT-TEST-01
Create task T-TEST-TASK-02 with name "Test Task 2" in SPRINT-TEST-01
```

**Step 3**: Attach tasks to sprint (if not already associated)
```
Attach T-TEST-TASK-01 and T-TEST-TASK-02 to SPRINT-TEST-01
```

**Step 4**: Assign a task
```
Assign T-TEST-TASK-01 to backend-engineer-template
```

**Step 5**: Update task status
```
Update T-TEST-TASK-01 status to in_progress
```

**Step 6**: Complete the task
```
Update T-TEST-TASK-01 status to completed
```

**Step 7**: Update sprint status
```
Update SPRINT-TEST-01 status to "Completed"
```

### Scenario 2: Agent Self-Assignment Workflow

Simulate an agent working through their tasks:

**Step 1**: Agent checks their assigned tasks
```
List my assigned tasks (use orchestration_tasks_list with agent filter)
```

**Step 2**: Agent assigns themselves a task
```
Assign T-TEST-TASK-02 to myself (use appropriate agent slug)
```

**Step 3**: Agent starts work
```
Update T-TEST-TASK-02 to in_progress with note "Starting implementation"
```

**Step 4**: Agent completes work
```
Update T-TEST-TASK-02 to completed with note "Implementation complete, tests passing"
```

### Scenario 3: Sprint Planning Workflow

**Step 1**: Create sprint with metadata
```
Create SPRINT-DEMO with:
- title: "Demo Sprint"
- priority: "High"
- estimate: "2 weeks"
- starts_on: "2025-10-15"
- ends_on: "2025-10-29"
```

**Step 2**: Create backlog tasks
```
Create multiple tasks without sprint assignment first
```

**Step 3**: Attach tasks during planning
```
Attach selected tasks to SPRINT-DEMO
```

**Step 4**: Update sprint status
```
Update SPRINT-DEMO to "In Progress" when starting
```

---

## Validation Checklist

### Tool Availability
- [ ] All 6 write tools appear in Claude Desktop MCP tools list
- [ ] Tool descriptions are clear and helpful
- [ ] Parameter schemas are complete and accurate

### Sprint Operations
- [ ] ✅ Can create a new sprint
- [ ] ✅ Can update existing sprint
- [ ] ✅ Can change sprint status
- [ ] ✅ Can attach tasks to sprint
- [ ] ✅ Response includes sprint details and stats

### Task Operations
- [ ] ✅ Can create a new task
- [ ] ✅ Can update existing task
- [ ] ✅ Can assign task to agent
- [ ] ✅ Can update task status through workflow
- [ ] ✅ Response includes task details

### Error Handling
- [ ] Invalid task/sprint codes return clear errors
- [ ] Missing required parameters are caught
- [ ] Invalid status values are rejected
- [ ] Service-level errors are handled gracefully

### Data Persistence
- [ ] Created sprints appear in `orchestration:sprints` CLI
- [ ] Created tasks appear in `orchestration:tasks` CLI
- [ ] Status changes persist correctly
- [ ] Assignments are recorded
- [ ] Notes and context are saved

---

## CLI Verification Commands

After MCP testing, verify data persistence:

```bash
# Verify sprint created
php artisan orchestration:sprint:detail SPRINT-TEST-01

# Verify tasks created
php artisan orchestration:task:detail T-TEST-TASK-01

# List all tasks in sprint
php artisan orchestration:tasks --sprint=SPRINT-TEST-01

# Check task assignment
php artisan orchestration:task:detail T-TEST-TASK-01 | grep -A 5 "Agent"

# Verify sprint status updated
php artisan orchestration:sprint:detail SPRINT-TEST-01 | grep "Status"
```

---

## Troubleshooting

### MCP Server Won't Start
```bash
# Check for PHP errors
php artisan orchestration:mcp 2>&1 | head -20

# Verify MCP package installed
composer show | grep mcp

# Check server registration
php artisan list | grep mcp
```

### Tools Not Appearing in Claude Desktop
1. Restart Claude Desktop
2. Check MCP server connection status
3. Verify `.mcp.json` configuration
4. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Command Errors
1. Check autoloader: `composer dump-autoload`
2. Verify command classes exist: `ls app/Commands/Orchestration/{Sprint,Task}/`
3. Check for PHP syntax errors: `php -l app/Commands/Orchestration/**/*.php`

### Service Errors
**Known Issue**: TaskOrchestrationService has bug in `logAssignment()` method (metadata parameter mismatch)
- **Impact**: Assignment works, but activity logging may fail
- **Workaround**: Ignore logging errors for now
- **Fix**: Update TaskActivity::logAssignment signature or service call

---

## Success Criteria

Sprint 4 Task 4.2 is complete when:

1. ✅ All 6 MCP write tools are accessible from Claude Desktop
2. ✅ Complete workflow works end-to-end (create sprint → create task → assign → complete)
3. ✅ Data persists correctly (verified via CLI)
4. ✅ Error handling works as expected
5. ✅ Response formats are consistent and useful
6. ✅ No regressions in existing MCP read tools

---

## Next Steps After Testing

1. **Document Issues**: Note any bugs or unexpected behavior
2. **Performance Check**: Verify response times are acceptable
3. **Update Documentation**: Add any clarifications needed
4. **Sprint 5**: Move to cleanup and documentation tasks
5. **Production Readiness**: Consider what's needed for production use

---

**Ready for Testing!** 🎉

The MCP tools are now using our unified command architecture. All write operations go through the same code path whether invoked via web UI, MCP, or CLI.