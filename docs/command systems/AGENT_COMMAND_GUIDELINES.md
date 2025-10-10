# Agent Command Guidelines

**Last Updated**: 2025-10-09  
**For**: AI Agents working with Fragments Engine  
**System**: Unified Command Architecture

---

## Quick Start for Agents

You have access to orchestration commands through MCP (Model Context Protocol). All commands follow a unified architecture with consistent patterns.

### Available Command Categories

- **Sprint Management**: Create, update, and track sprints
- **Task Management**: Create, assign, and update tasks
- **Agent Management**: View and manage agent profiles
- **Backlog Management**: View unassigned work items

---

## Core Principles

### 1. Commands Are Unified
- Same logic works across web UI, MCP, and CLI
- Consistent parameter names and response formats
- Context-aware responses (you get structured data)

### 2. Use Structured Parameters
```
✅ Good: Use tool with {"code": "SPRINT-67", "title": "My Sprint"}
❌ Bad: Use tool with unstructured text
```

### 3. Handle Responses
All commands return:
```json
{
  "success": true,
  "data": {...},
  "type": "sprint|task|agent",
  "meta": {
    "timestamp": "...",
    "command": "..."
  }
}
```

---

## Sprint Commands

### List Sprints
**Tool**: `orchestration_sprints_list`

**Purpose**: Get list of sprints with optional filtering

**Parameters**:
```json
{
  "codes": ["SPRINT-67", "SPRINT-68"],  // optional
  "limit": 20,                           // optional, default 50
  "details": false,                       // optional, include tasks
  "tasks_limit": 10                      // optional, max tasks per sprint
}
```

**Example**:
```
List active sprints: Use orchestration_sprints_list with limit: 10
```

---

### Get Sprint Details
**Tool**: `orchestration_sprints_detail`

**Purpose**: Get detailed information about a specific sprint

**Parameters**:
```json
{
  "code": "SPRINT-67",      // required
  "include_tasks": true,    // optional, default true
  "tasks_limit": 20         // optional, default 10
}
```

**Example**:
```
Show details for SPRINT-67: Use orchestration_sprints_detail with code: "SPRINT-67"
```

---

### Create/Update Sprint
**Tool**: `orchestration_sprints_save`

**Purpose**: Create a new sprint or update existing one

**Parameters**:
```json
{
  "code": "SPRINT-67",                  // required
  "title": "Q4 Backend Improvements",  // optional
  "priority": "High",                   // optional
  "status": "Planned",                  // optional
  "estimate": "2 weeks",                // optional
  "starts_on": "2025-10-15",           // optional (Y-m-d)
  "ends_on": "2025-10-29",             // optional (Y-m-d)
  "notes": ["Planning complete"],       // optional array
  "meta": {"key": "value"},             // optional object
  "upsert": true                        // optional, default true
}
```

**Example**:
```
Create sprint: Use orchestration_sprints_save with code: "SPRINT-100", title: "New Sprint", priority: "High"
```

---

### Update Sprint Status
**Tool**: `orchestration_sprints_status`

**Purpose**: Update sprint status and optionally add a note

**Parameters**:
```json
{
  "sprint": "SPRINT-67",           // required (code or UUID)
  "status": "In Progress",         // required
  "note": "Sprint planning done"   // optional
}
```

**Example**:
```
Mark sprint as started: Use orchestration_sprints_status with sprint: "SPRINT-67", status: "In Progress"
```

---

### Attach Tasks to Sprint
**Tool**: `orchestration_sprints_attach_tasks`

**Purpose**: Associate tasks with a sprint

**Parameters**:
```json
{
  "sprint": "SPRINT-67",                    // required
  "tasks": ["T-BE-01", "T-BE-02"],         // required array
  "include_tasks": true,                    // optional
  "include_assignments": false,             // optional
  "tasks_limit": 10                        // optional
}
```

**Example**:
```
Add tasks to sprint: Use orchestration_sprints_attach_tasks with sprint: "SPRINT-67", tasks: ["T-BE-01", "T-BE-02"]
```

---

## Task Commands

### List Tasks
**Tool**: `orchestration_tasks_list`

**Purpose**: Get list of tasks with filtering

**Parameters**:
```json
{
  "sprint": ["SPRINT-67"],           // optional array
  "delegation_status": ["assigned"],  // optional array
  "status": ["todo", "in_progress"], // optional array
  "agent": "backend-engineer",       // optional slug
  "search": "authentication",         // optional text
  "limit": 20                         // optional, default 20
}
```

**Example**:
```
Show my tasks: Use orchestration_tasks_list with agent: "backend-engineer", limit: 10
```

---

### Get Task Details
**Tool**: `orchestration_tasks_detail`

**Purpose**: Get detailed information about a task

**Parameters**:
```json
{
  "task": "T-BE-01",           // required (code or UUID)
  "assignments_limit": 10,      // optional
  "include_history": true       // optional
}
```

**Example**:
```
Show task details: Use orchestration_tasks_detail with task: "T-BE-01"
```

---

### Create/Update Task
**Tool**: `orchestration_tasks_save`

**Purpose**: Create a new task or update existing one

**Parameters**:
```json
{
  "task_code": "T-BE-01",                      // required
  "task_name": "Refactor auth service",        // optional
  "description": "Improve security",           // optional
  "sprint_code": "SPRINT-67",                 // optional
  "status": "todo",                            // optional
  "delegation_status": "unassigned",          // optional
  "priority": "high",                          // optional
  "estimate_text": "3-4 days",                // optional
  "estimated_hours": 28,                       // optional
  "agent_content": "Detailed instructions",   // optional
  "type": "task",                              // optional
  "acceptance": "Tests pass, PR approved",    // optional
  "dependencies": ["T-BE-00"],                // optional array
  "tags": ["security", "backend"],            // optional array
  "upsert": true                               // optional, default true
}
```

**Example**:
```
Create task: Use orchestration_tasks_save with task_code: "T-BE-01", task_name: "Refactor authentication", priority: "high"
```

---

### Assign Task
**Tool**: `orchestration_tasks_assign`

**Purpose**: Assign a task to an agent

**Parameters**:
```json
{
  "task": "T-BE-01",                  // required
  "agent": "backend-engineer",        // required (slug/UUID)
  "status": "assigned",               // optional, default "assigned"
  "note": "Assigned for Q4 work",    // optional
  "context": {"key": "value"}        // optional object
}
```

**Example**:
```
Assign task to myself: Use orchestration_tasks_assign with task: "T-BE-01", agent: "backend-engineer"
```

---

### Update Task Status
**Tool**: `orchestration_tasks_status`

**Purpose**: Update task delegation status

**Parameters**:
```json
{
  "task": "T-BE-01",              // required
  "status": "in_progress",        // required: unassigned, assigned, in_progress, blocked, completed, cancelled
  "note": "Started implementation" // optional
}
```

**Example**:
```
Mark task in progress: Use orchestration_tasks_status with task: "T-BE-01", status: "in_progress"
```

---

## Agent Commands

### List Agents
**Tool**: `orchestration_agents_list`

**Purpose**: Get list of agent profiles

**Parameters**:
```json
{
  "type": "backend-engineer",     // optional
  "mode": "implementation",       // optional
  "status": ["active"],           // optional array
  "search": "backend",            // optional
  "limit": 20                     // optional
}
```

**Example**:
```
Show active backend agents: Use orchestration_agents_list with type: "backend-engineer", status: ["active"]
```

---

## Common Workflows

### 1. Start a New Sprint

```
Step 1: Create sprint
Use orchestration_sprints_save:
  code: "SPRINT-NEW"
  title: "New Sprint"
  status: "Planned"
  starts_on: "2025-10-15"
  ends_on: "2025-10-29"

Step 2: Create tasks
Use orchestration_tasks_save:
  task_code: "T-NEW-01"
  task_name: "First task"
  sprint_code: "SPRINT-NEW"

Step 3: Update sprint status
Use orchestration_sprints_status:
  sprint: "SPRINT-NEW"
  status: "In Progress"
```

### 2. Self-Assign and Complete Work

```
Step 1: Find available tasks
Use orchestration_tasks_list:
  delegation_status: ["unassigned"]
  sprint: ["SPRINT-67"]

Step 2: Assign to yourself
Use orchestration_tasks_assign:
  task: "T-BE-01"
  agent: "your-agent-slug"
  status: "assigned"

Step 3: Start work
Use orchestration_tasks_status:
  task: "T-BE-01"
  status: "in_progress"

Step 4: Complete work
Use orchestration_tasks_status:
  task: "T-BE-01"
  status: "completed"
  note: "Implementation complete"
```

### 3. Sprint Planning

```
Step 1: Create sprint
Use orchestration_sprints_save

Step 2: Create multiple tasks
For each task:
  Use orchestration_tasks_save with sprint_code

Step 3: Review sprint
Use orchestration_sprints_detail to see all tasks

Step 4: Assign tasks
For each task:
  Use orchestration_tasks_assign
```

---

## Best Practices for Agents

### 1. Always Provide Context
```
✅ Good: Use note parameter to explain actions
   Use orchestration_tasks_status with note: "Started after reviewing requirements"

❌ Bad: Silent status updates without explanation
```

### 2. Check Before Acting
```
✅ Good: List tasks first, then operate on specific ones
   1. orchestration_tasks_list
   2. orchestration_tasks_assign

❌ Bad: Operate on tasks without verifying they exist
```

### 3. Use Structured Data
```
✅ Good: {"task": "T-BE-01", "status": "in_progress"}
❌ Bad: "Update T-BE-01 to in progress"
```

### 4. Handle Errors Gracefully
```
✅ Good: Check response.success and handle errors
❌ Bad: Assume all operations succeed
```

### 5. Provide Meaningful Notes
```
✅ Good: note: "Blocked waiting for API endpoint implementation (T-BE-05)"
❌ Bad: note: "Blocked"
```

---

## Response Formats

### Success Response
```json
{
  "success": true,
  "data": {
    "id": "...",
    "code": "SPRINT-67",
    "title": "...",
    "status": "In Progress",
    ...
  },
  "type": "sprint",
  "meta": {
    "timestamp": "2025-10-09T12:00:00Z",
    "command": "orchestration_sprints_save",
    "count": 1
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Sprint code is required",
  "type": "error"
}
```

---

## Status Values Reference

### Sprint Status
- "Planned"
- "In Progress"
- "Completed"
- "Cancelled"
- (Custom values allowed)

### Task Delegation Status
- "unassigned" - No agent assigned
- "assigned" - Assigned but not started
- "in_progress" - Actively being worked on
- "blocked" - Cannot proceed
- "completed" - Work finished
- "cancelled" - No longer needed

### Task Work Status
- "todo" - Not started
- "in_progress" - Being worked on
- "done" - Completed
- "blocked" - Cannot proceed
- (Custom values allowed)

### Task Priority
- "low"
- "medium"
- "high"
- "critical"

---

## Tips for Efficient Usage

### 1. Batch Operations
When creating multiple tasks, call save multiple times:
```
For task 1-5:
  Use orchestration_tasks_save
```

### 2. Use Filters Effectively
```
✅ Good: Use specific filters to narrow results
   orchestration_tasks_list with sprint: ["SPRINT-67"], status: ["todo"]

❌ Bad: Get all tasks then filter manually
```

### 3. Include Relevant Data
```
✅ Good: Use include_tasks: true when you need task details
❌ Bad: Make separate calls for each task
```

### 4. Set Meaningful Limits
```
✅ Good: Use limit: 10 for quick overviews
✅ Good: Use limit: 100 for comprehensive lists
❌ Bad: Always use default without considering needs
```

---

## Troubleshooting

### "Command not recognized"
- Check tool name spelling (use underscore, not hyphen)
- Verify MCP server is connected

### "Required parameter missing"
- Review parameter requirements in this guide
- All required fields must be provided

### "Task not found"
- Use correct task code format (e.g., "T-BE-01")
- Verify task exists with orchestration_tasks_list

### "Status not updated"
- Check status value matches allowed values
- Verify you have permission to update

---

## Examples of Good Agent Behavior

### Example 1: Proactive Status Updates
```
Agent: I'm starting work on T-BE-01. Let me update the status.

Use orchestration_tasks_status:
  task: "T-BE-01"
  status: "in_progress"
  note: "Starting implementation of authentication refactor. Reviewed existing code and created implementation plan."
```

### Example 2: Comprehensive Task Creation
```
Agent: Creating a new task for the API endpoint work.

Use orchestration_tasks_save:
  task_code: "T-BE-05"
  task_name: "Implement user profile API endpoint"
  description: "Create RESTful endpoint for user profile management"
  sprint_code: "SPRINT-67"
  status: "todo"
  delegation_status: "unassigned"
  priority: "high"
  estimate_text: "1-2 days"
  agent_content: "Implement GET, PUT endpoints. Include validation, error handling, and tests. Follow existing API patterns in app/Http/Controllers/Api/"
  acceptance: "Tests pass, endpoints documented, PR approved"
  tags: ["api", "backend", "user-management"]
```

### Example 3: Sprint Planning
```
Agent: Let me set up the new sprint with tasks.

Step 1: Create sprint
Use orchestration_sprints_save:
  code: "SPRINT-68"
  title: "API Improvements Sprint"
  priority: "High"
  status: "Planned"
  starts_on: "2025-10-15"
  ends_on: "2025-10-29"
  notes: ["Focus on RESTful API improvements", "Target 5 endpoints"]

Step 2: Create tasks...
(multiple orchestration_tasks_save calls)

Step 3: Verify
Use orchestration_sprints_detail:
  code: "SPRINT-68"
  include_tasks: true
```

---

## Command Aliases Reference

Some commands have multiple names:

- `orchestration_sprints_list` = `orchestration_sprint_list`
- `orchestration_tasks_list` = `orchestration_task_list`

**Tip**: Use the plural form (`sprints`, `tasks`) for consistency.

---

## Additional Resources

- **Development Guide**: `COMMAND_DEVELOPMENT_GUIDE.md` - For creating new commands
- **MCP Testing Guide**: `SPRINT_4_MCP_TESTING_GUIDE.md` - Testing procedures
- **Architecture Docs**: `COMMAND_SYSTEM_CURRENT_STATE_ANALYSIS.md` - System overview

---

**Remember**: All orchestration commands work across web, MCP, and CLI. Use structured parameters, provide meaningful notes, and handle responses properly. Happy orchestrating! 🎯
