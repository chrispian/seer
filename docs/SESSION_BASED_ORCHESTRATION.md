# Session-Based Orchestration System

**Date:** 2025-10-10  
**Status:** Planning  
**Goal:** Context-aware command system with automatic scoping and smart validation

---

## 🎯 Vision

**Commands should be fast, contextual, and smart.** When you're working on a task, you shouldn't have to repeat the task ID in every command. When you complete something, the system should validate you've captured everything.

### Core Concepts

1. **Work Session** - A bounded working period with context
2. **Active Context** - Current sprint/task being worked on
3. **Automatic Scoping** - Commands inherit context from session
4. **Smart Completion** - System validates before closing work items
5. **Session Key** - Unique identifier for tracking all operations

---

## 💡 Improvements & Extensions

### 1. Multi-Level Context Stack

Instead of just "active task," support a context **stack**:

```
Session Context Stack:
├── Project: myproject
├── Sprint: SPRINT-UNIFY-1  (active)
├── Task: T-UNIFY-01        (active)
└── Subtask: indexing-fix   (active, optional)
```

Commands inherit from the deepest applicable level:
- `/task-update "Fixed performance"` → applies to T-UNIFY-01
- `/sprint-note "Sprint going well"` → applies to SPRINT-UNIFY-1
- `/log "Starting work"` → applies to session, tags T-UNIFY-01

### 2. Smart Validation Rules

When completing work, validate based on **type**:

**Task Completion Checklist:**
- [ ] Has summary_content?
- [ ] Has at least one context update?
- [ ] Status progression valid? (pending → in_progress → completed)
- [ ] If has subtasks, are they all complete?
- [ ] Estimated hours vs actual hours reasonable?

**Sprint Completion Checklist:**
- [ ] All tasks completed or cancelled?
- [ ] Has retrospective notes?
- [ ] Deliverables documented?
- [ ] Next sprint planned (if applicable)?

### 3. Time Tracking

Auto-track time spent in context:

```bash
/task-start T-UNIFY-01
# System starts timer

[work happens for 2.5 hours]

/task-end
# System logs: 2.5 hours spent on T-UNIFY-01
# Asks: "Actual time: 2.5h, Estimated: 3h. Update estimate? [y/N]"
```

### 4. Context Suggestions

System suggests context based on recent activity:

```bash
$ orchestration session-start

🤖 Welcome back! Recent activity:
   1. T-UNIFY-01 (in_progress) - Last worked 2 hours ago
   2. SPRINT-UNIFY-1 (active) - 3 of 5 tasks complete
   
Resume T-UNIFY-01? [Y/n]
```

### 5. Session Pause/Resume

Support interruptions:

```bash
/session-pause "Emergency meeting"
# Saves current context, stops timers

[meeting happens]

/session-resume
# Restores context: T-UNIFY-01 (in_progress)
# Continues timers
# Logs: "Session interrupted for 45 minutes"
```

### 6. Concurrent Sessions

Multiple sessions for context switching:

```bash
# Session 1: Development work
SESSION-001: SPRINT-UNIFY-1 → T-UNIFY-01

# Session 2: Support ticket
SESSION-002: SUPPORT-Q4 → TICKET-1234

# Switch between them
/session-switch SESSION-001
/session-switch SESSION-002
```

### 7. Smart Defaults & Inference

Learn patterns and suggest:

```bash
/task-start T-UNIFY-02

🤖 Based on similar tasks:
   - Estimated time: 2-3 hours
   - Suggested labels: [backend, database, migration]
   - Common next steps: Create migration → Test → Document
   
Apply suggestions? [y/N]
```

### 8. Session Summary on End

```bash
/session-end

📊 Session Summary (3h 45m):
   ✅ T-UNIFY-01 completed (2h 30m)
   ✅ T-UNIFY-02 completed (1h 15m)
   📝 6 context updates
   📎 3 artifacts created
   
Anything else to note before closing? (Ctrl+C to skip)
> Fixed performance issue with indexes, need to document in wiki

✅ Session closed. Great work!
```

---

## 🏗️ Architecture

### New Database Schema

#### `work_sessions` Table

```sql
CREATE TABLE work_sessions (
    id UUID PRIMARY KEY,
    session_key VARCHAR(100) UNIQUE NOT NULL,  -- 'SESSION-001', generated
    
    -- Ownership
    agent_id UUID,
    user_id BIGINT,
    chat_session_id UUID,  -- Link to ChatSession if from GUI
    
    -- Source & Type
    source ENUM('cli', 'mcp', 'api', 'gui') NOT NULL,
    session_type ENUM('work', 'planning', 'review') DEFAULT 'work',
    
    -- Status
    status ENUM('active', 'paused', 'completed', 'abandoned') DEFAULT 'active',
    
    -- Context Stack (JSON)
    context_stack JSON NOT NULL,  -- [{type: 'sprint', id: 'SPRINT-1'}, {type: 'task', id: 'T-01'}]
    
    -- Active Pointers (denormalized for quick lookup)
    active_project_id UUID,
    active_sprint_id UUID,
    active_task_id UUID,
    
    -- Metadata
    metadata JSON,  -- {paused_reason, interruptions: [], notes: []}
    
    -- Timing
    started_at TIMESTAMP NOT NULL,
    paused_at TIMESTAMP,
    resumed_at TIMESTAMP,
    ended_at TIMESTAMP,
    total_active_seconds INT DEFAULT 0,
    
    -- Summary (filled on end)
    summary TEXT,
    tasks_completed INT DEFAULT 0,
    artifacts_created INT DEFAULT 0,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_session_key (session_key),
    INDEX idx_agent_status (agent_id, status),
    INDEX idx_active_task (active_task_id),
    INDEX idx_started (started_at)
);
```

#### `session_context_history` Table

Track all context switches:

```sql
CREATE TABLE session_context_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    session_id UUID NOT NULL,
    
    -- Context Change
    action ENUM('push', 'pop', 'switch') NOT NULL,
    context_type ENUM('project', 'sprint', 'task', 'subtask') NOT NULL,
    context_id VARCHAR(100) NOT NULL,
    context_data JSON,  -- Full object snapshot
    
    -- Timing
    switched_at TIMESTAMP NOT NULL,
    duration_seconds INT,  -- Time spent in this context
    
    INDEX idx_session (session_id),
    INDEX idx_context (context_id),
    FOREIGN KEY (session_id) REFERENCES work_sessions(id) ON DELETE CASCADE
);
```

#### `session_activities` Table

Log all activities in session:

```sql
CREATE TABLE session_activities (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    session_id UUID NOT NULL,
    
    -- Activity
    activity_type ENUM('command', 'context_update', 'note', 'artifact', 'pause', 'resume') NOT NULL,
    command VARCHAR(255),
    description TEXT,
    
    -- Context at time of activity
    task_id UUID,
    sprint_id UUID,
    
    -- Metadata
    metadata JSON,  -- {command_args, execution_time_ms, etc.}
    
    occurred_at TIMESTAMP NOT NULL,
    
    INDEX idx_session (session_id),
    INDEX idx_type (activity_type),
    INDEX idx_task (task_id),
    FOREIGN KEY (session_id) REFERENCES work_sessions(id) ON DELETE CASCADE
);
```

---

## 📋 Command Design

### Session Lifecycle Commands

#### `/session-start` or `orchestration session:start`

**Purpose:** Initialize a new work session

```bash
# CLI
orchestration session:start [--type=work|planning|review]

# MCP
mcp_tool session_start(
  agent_id: "agent-uuid",
  type: "work"
)

# GUI (automatic on chat init)
POST /api/orchestration/sessions/start
{
  "chat_session_id": "uuid",
  "type": "work"
}
```

**Response:**
```json
{
  "session_key": "SESSION-001",
  "started_at": "2025-10-10T10:00:00Z",
  "suggestions": [
    {
      "type": "resume_task",
      "task": "T-UNIFY-01",
      "last_worked": "2 hours ago",
      "status": "in_progress"
    }
  ]
}
```

#### `/session-end` or `orchestration session:end`

**Purpose:** Complete and summarize session

```bash
orchestration session:end [--summary="Brief summary"]

# Or implicitly when closing CLI
```

**Flow:**
1. Validates all active contexts are properly closed
2. Prompts for missing information
3. Generates session summary
4. Updates all time tracking
5. Closes session

---

### Context Management Commands

#### `/sprint-start {sprint_code}` or `orchestration sprint:activate`

**Purpose:** Set active sprint in session context

```bash
orchestration sprint:activate SPRINT-UNIFY-1

# Shorthand in session
/sprint-start SPRINT-UNIFY-1
```

**Effect:**
- Adds sprint to context stack
- Marks sprint status as "in_progress" (if not already)
- All subsequent commands inherit sprint context

#### `/task-start {task_code}` or `orchestration task:activate`

**Purpose:** Set active task in session context

```bash
orchestration task:activate T-UNIFY-01

# Shorthand
/task-start T-UNIFY-01
```

**Effect:**
- Adds task to context stack (under active sprint)
- Marks task status as "in_progress"
- Starts time tracking for task
- Logs activity in session

#### `/task-end` or `orchestration task:deactivate`

**Purpose:** Complete or close active task

```bash
# End active task (no ID needed!)
orchestration task:deactivate

# Or end specific task (explicit)
orchestration task:deactivate T-UNIFY-01

# Shorthand
/task-end
/task-end T-UNIFY-01
```

**Smart Validation Flow:**
```
1. Validate task has minimum required data
2. If missing:
   ❌ Task T-UNIFY-01 cannot be completed:
      - Missing summary_content
      - No context updates recorded
      - Time not tracked (0 seconds)
   
   Would you like to:
   1. Add summary now (recommended)
   2. Mark as incomplete and close anyway
   3. Cancel and continue working
   
3. If valid:
   ✅ Task T-UNIFY-01 completed
      - Duration: 2h 30m
      - 4 context updates
      - 2 artifacts created
   
   Task removed from active context.
```

---

### Contextual Update Commands

All these commands operate on **active context** by default:

#### `/update {content}` or `orchestration update`

Updates active context (task, sprint, or session):

```bash
# Updates active task context
/update "Fixed index performance issue, reduced query time from 2s to 200ms"

# Equivalent to:
orchestration task:context-append T-UNIFY-01 --text="..."
```

#### `/note {content}` or `orchestration note`

Adds a timestamped note:

```bash
/note "Found edge case with null values, need to handle"

# Appends to active task context:
# [2025-10-10 10:30] Found edge case with null values, need to handle
```

#### `/log {content}` or `orchestration log`

Logs activity (visible in session activities):

```bash
/log "Starting code review"
/log "Running tests"
/log "All tests passing ✅"
```

#### `/done {summary}` or `orchestration complete`

Completes active task with summary:

```bash
/done "Migration created and tested, indexes added, performance improved 10x"

# Equivalent to:
# 1. Sets task summary_content
# 2. Marks task status = completed
# 3. Runs smart validation
# 4. Ends task (removes from context)
```

---

### Query Commands (Context-Aware)

#### `/status` or `orchestration status`

Shows current session status:

```bash
/status

📊 Session Status (SESSION-001):
   Started: 2h 30m ago
   Source: CLI
   
   Context Stack:
   └── Sprint: SPRINT-UNIFY-1 (in_progress)
       └── Task: T-UNIFY-01 (in_progress)
   
   Time This Task: 45 minutes
   Activities Today: 8 commands, 3 notes
```

#### `/history` or `orchestration history`

Shows recent session activities:

```bash
/history

Recent Activity (SESSION-001):
  10:45 - Command: /task-start T-UNIFY-01
  10:47 - Note: "Starting migration work"
  10:52 - Update: "Created migration file"
  11:15 - Log: "Running tests"
  11:20 - Command: /done "Migration complete"
```

---

### Bulk Operations

#### `/tasks-complete {ids}` or `orchestration tasks:bulk-complete`

Complete multiple tasks:

```bash
orchestration tasks:bulk-complete T-UNIFY-01,T-UNIFY-02,T-UNIFY-03 \
  --summary="All completed successfully"

# Interactive mode:
# For each task, prompt for individual summary if missing
```

---

## 🔄 Integration Points

### 1. CLI Integration

**On CLI startup:**
```bash
$ orchestration

Welcome to Fragments Orchestration CLI

🔍 Checking for active sessions...
   Found SESSION-001 (paused 2h ago)
   Context: SPRINT-UNIFY-1 → T-UNIFY-01
   
Resume session? [Y/n] y

✅ Session resumed: SESSION-001
   Active task: T-UNIFY-01
   
Ready for commands. Type 'help' for help.
```

**Session persistence:**
- Store session key in `~/.fragments/session`
- Auto-resume on CLI restart
- Clean shutdown saves state

### 2. MCP Integration

**MCP tools get session key:**
```typescript
mcp_tool orchestration_task_update({
  session_key: "SESSION-001",  // From agent memory
  content: "Progress update"
  // No task_id needed - inferred from session!
})
```

**Agent workflow:**
1. Start session, receive session_key
2. Store session_key in durable memory
3. Include session_key in all orchestration commands
4. Session provides context, reduces parameters

### 3. API Integration

**HTTP requests include session:**
```http
POST /api/orchestration/tasks/update
X-Session-Key: SESSION-001
X-Source: api

{
  "content": "Update text"
  // task_id inferred from session context
}
```

### 4. GUI Integration (Future)

**Chat session automatically creates work session:**
```php
// When chat session starts
$chatSession = ChatSession::create([...]);

// Create linked work session
$workSession = WorkSession::create([
    'session_key' => 'SESSION-' . $chatSession->id,
    'chat_session_id' => $chatSession->id,
    'source' => 'gui',
    'agent_id' => $agent->id,
    'context_stack' => [],
]);
```

---

## 🔄 Instruction System & Agent Recovery

### Core Principle: Always Return Instructions

**Every orchestration command should return actionable guidance** when appropriate. This enables agents to maintain context and recover from interruptions reliably.

#### Instruction Response Pattern

```json
{
  "success": true,
  "data": { /* command result */ },
  "instructions": {
    "next_actions": [
      "Update task context with progress",
      "Run tests to verify changes",
      "Complete task when done"
    ],
    "suggested_commands": [
      "orchestration update \"Progress details\"",
      "orchestration log \"Running tests\"",
      "orchestration done \"Summary\""
    ],
    "context_reminder": {
      "active_sprint": "SPRINT-UNIFY-1",
      "active_task": "T-UNIFY-01",
      "time_elapsed": "45 minutes"
    }
  }
}
```

#### Examples by Command Type

**After `session:start`:**
```json
{
  "instructions": {
    "next_actions": [
      "Activate sprint to set working context",
      "Start or resume a task"
    ],
    "suggested_commands": [
      "orchestration sprint:activate SPRINT-UNIFY-1",
      "orchestration task:activate T-UNIFY-01"
    ],
    "suggestions": [
      {
        "type": "resume_task",
        "task": "T-UNIFY-01",
        "reason": "In progress, last worked 2h ago"
      }
    ]
  }
}
```

**After `task:activate`:**
```json
{
  "instructions": {
    "next_actions": [
      "Begin implementation work",
      "Log progress with /update or /note commands",
      "Complete task with /done when finished"
    ],
    "context_reminder": {
      "task": "T-UNIFY-01",
      "description": "Implement session-based orchestration",
      "estimated_hours": 4,
      "dependencies": []
    },
    "validation_requirements": [
      "Add context updates as you work",
      "Provide summary on completion",
      "Track time spent"
    ]
  }
}
```

**After `update`:**
```json
{
  "instructions": {
    "next_actions": [
      "Continue working on T-UNIFY-01",
      "Add more updates as progress is made",
      "Complete when ready with /done"
    ],
    "progress": {
      "context_updates": 3,
      "time_elapsed": "1h 15m",
      "estimated_remaining": "2h 45m"
    }
  }
}
```

**After failed validation:**
```json
{
  "success": false,
  "error": "Task cannot be completed - missing required information",
  "instructions": {
    "required_actions": [
      "Add summary with /done \"summary text\"",
      "Or add context update first with /update \"details\""
    ],
    "validation_errors": [
      "No summary_content provided",
      "Only 1 context update (minimum 2 recommended)"
    ],
    "suggested_commands": [
      "orchestration update \"Additional context\"",
      "orchestration done \"Final summary\""
    ]
  }
}
```

### Agent Recovery Strategy

**Goal:** Minimize data loss and enable reliable agent reconstruction after failures.

#### The INIT Pattern

1. **Agent starts or resumes work:**
   ```bash
   orchestration session:start
   ```

2. **System creates/resumes session:**
   - Generates or reloads `SESSION-XXX` key
   - Agent stores in durable memory
   - Returns instructions with context

3. **All work logged in session:**
   - Every command → `session_activities`
   - Every context switch → `session_context_history`
   - Every update → linked to session

4. **On failure/interruption:**
   - Session remains in database with full history
   - Last successful turn logged with timestamp
   - Context stack preserved

5. **Agent reconstruction:**
   ```bash
   # New agent instance
   orchestration session:resume SESSION-XXX
   
   # System returns full context:
   {
     "session_key": "SESSION-XXX",
     "context_stack": [
       {"type": "sprint", "id": "SPRINT-UNIFY-1"},
       {"type": "task", "id": "T-UNIFY-01"}
     ],
     "last_activity": "2025-10-10T14:30:00Z",
     "activities_since_start": 15,
     "instructions": {
       "status": "Session interrupted 5 minutes ago",
       "last_actions": [
         "14:30 - /update \"Created migration files\"",
         "14:28 - /note \"Found performance issue\"",
         "14:25 - /log \"Running tests\""
       ],
       "suggested_next_actions": [
         "Review last context update",
         "Continue from where you left off",
         "Update with current progress"
       ]
     }
   }
   ```

#### Data Loss Minimization

**Before INIT (traditional approach):**
- Agent loses all context on failure
- Must reconstruct from chat history
- No structured recovery mechanism
- Data loss: entire conversation context

**After INIT (session-based approach):**
- Agent loses only: last turn → failure point
- Structured recovery: session key + history
- System reconstructs: context stack, activities, timing
- Data loss: **5-30 seconds of work** (minimal)

#### Recovery Scenarios

**Scenario 1: Agent Crash Mid-Task**
```bash
# Agent was working on T-UNIFY-01, crashed after 2 hours

# New agent resumes:
orchestration session:resume SESSION-001

# System returns:
# - Active task: T-UNIFY-01
# - Time spent: 2h 5m
# - Last update: "Fixed index performance"
# - Next actions: Continue or complete task

# Agent continues seamlessly
orchestration update "Completed testing"
orchestration done "Task complete"
```

**Scenario 2: Network Interruption**
```bash
# Agent sent command, network dropped before response

# Agent reconnects:
orchestration session:status SESSION-001

# System returns:
# - Last successful command: /update "Progress"
# - Command completed successfully
# - Next suggested: Continue working

# Agent knows exactly where it left off
```

**Scenario 3: System Restart**
```bash
# Entire system restarted mid-work

# Agent queries:
orchestration session:list --status=active

# System returns:
# - SESSION-001 (active, 2h ago)
# - SESSION-002 (paused, 1d ago)

# Agent resumes most recent:
orchestration session:resume SESSION-001

# Full context restored, minimal loss
```

### Implementation Requirements

**All command handlers MUST:**
1. Log activity to `session_activities` table
2. Return `instructions` object when logical
3. Include context reminders for stateful operations
4. Provide next-action suggestions
5. Handle session recovery gracefully

**Instruction guidelines:**
- **Be specific:** "Add context update" not "Continue working"
- **Include commands:** Show exact command syntax
- **Remind context:** What sprint/task is active
- **Validate progress:** Show what's complete, what's missing
- **Suggest next steps:** 2-3 concrete actions

**When to include instructions:**
- ✅ After session start/resume
- ✅ After context changes (sprint/task activation)
- ✅ After validation failures
- ✅ After bulk operations
- ✅ When state changes significantly
- ❌ For simple queries (status, list)
- ❌ For pure reads with no action needed

---

## 🎯 Implementation Plan

### Phase 1: Core Session System (4-5 hours)

**Database & Models:**
- [ ] Create `work_sessions` migration
- [ ] Create `session_context_history` migration
- [ ] Create `session_activities` migration
- [ ] Create `WorkSession` model
- [ ] Create `SessionContextHistory` model
- [ ] Create `SessionActivity` model

**Services:**
- [ ] Create `SessionManager` service
  - startSession()
  - endSession()
  - pauseSession()
  - resumeSession()
  - pushContext() / popContext()
  - getActiveContext()
  - validateCompletion()

- [ ] Create `SessionContextStack` service
  - push(type, id)
  - pop(type)
  - getCurrent(type)
  - getStack()
  - clear()

---

### Phase 2: Context Commands (3-4 hours)

**New Commands:**
- [ ] `SessionStartCommand` (/session-start, orchestration session:start)
- [ ] `SessionEndCommand` (/session-end)
- [ ] `SessionStatusCommand` (/status)
- [ ] `SessionHistoryCommand` (/history)
- [ ] `SprintActivateCommand` (/sprint-start)
- [ ] `TaskActivateCommand` (/task-start)
- [ ] `TaskDeactivateCommand` (/task-end)
- [ ] `ContextualUpdateCommand` (/update)
- [ ] `ContextualNoteCommand` (/note)
- [ ] `ContextualLogCommand` (/log)
- [ ] `ContextualCompleteCommand` (/done)

**Update Existing Commands:**
- [ ] Modify all task/sprint commands to check for active context
- [ ] Add `--session` flag to override context
- [ ] Add validation for session-aware operations

---

### Phase 3: Smart Validation (2-3 hours)

**Validators:**
- [ ] Create `TaskCompletionValidator`
  - checkSummary()
  - checkContext()
  - checkTimeTracking()
  - checkSubtasks()
  - generateChecklist()

- [ ] Create `SprintCompletionValidator`
  - checkAllTasksComplete()
  - checkRetrospective()
  - checkDeliverables()

- [ ] Create `ValidationPrompt` system
  - Prompt for missing data
  - Allow skip/force
  - Record validation results

---

### Phase 4: MCP Tools (2-3 hours)

**New MCP Tools:**
- [ ] `SessionStartTool`
- [ ] `SessionEndTool`
- [ ] `SessionStatusTool`
- [ ] `SprintActivateTool`
- [ ] `TaskActivateTool`
- [ ] `TaskDeactivateTool`
- [ ] `ContextualUpdateTool`
- [ ] `ContextualNoteTool`
- [ ] `ContextualCompleteTool`

**Update Existing Tools:**
- [ ] Add session_key parameter to all task/sprint tools
- [ ] Make ID parameters optional when session context exists
- [ ] Add context inference logic

---

### Phase 5: Time Tracking (2-3 hours)

**Features:**
- [ ] Auto-start timer on task activate
- [ ] Auto-stop timer on task deactivate
- [ ] Handle pause/resume
- [ ] Store in `session_activities` and `work_items`
- [ ] Compare actual vs estimated time
- [ ] Prompt for estimate updates

---

### Phase 6: CLI Integration (3-4 hours)

**CLI Enhancements:**
- [ ] Session persistence (~/.fragments/session)
- [ ] Auto-resume on startup
- [ ] Session key in prompt (optional)
- [ ] Graceful shutdown (auto-save state)
- [ ] Interactive suggestions on start
- [ ] Session summary on exit

---

### Phase 7: API Middleware (2-3 hours)

**HTTP Integration:**
- [ ] Create `SessionAwareMiddleware`
  - Extract session_key from header
  - Load session into request context
  - Inject active context into commands
  
- [ ] Add session endpoints
  - `POST /api/orchestration/sessions/start`
  - `POST /api/orchestration/sessions/end`
  - `GET /api/orchestration/sessions/{key}`
  - `POST /api/orchestration/sessions/{key}/pause`
  - `POST /api/orchestration/sessions/{key}/resume`

---

### Phase 8: Instruction System (2-3 hours)

**Instruction Response System:**
- [ ] Create `InstructionBuilder` service
  - buildNextActions()
  - buildSuggestedCommands()
  - buildContextReminder()
  - buildValidationGuidance()
  
- [ ] Update all command handlers to return instructions
  - Session commands (start/end/resume)
  - Context commands (activate/deactivate)
  - Update commands (update/note/log)
  - Completion commands (done/complete)
  
- [ ] Add instruction formatting for different outputs
  - JSON for API/MCP
  - Markdown for CLI
  - Structured arrays for GUI

**Instruction Templates:**
- [ ] Session lifecycle templates
- [ ] Context activation templates
- [ ] Validation failure templates
- [ ] Progress tracking templates
- [ ] Recovery/resume templates

---

## 📊 Total Estimate

| Phase | Description | Hours |
|-------|-------------|-------|
| 1 | Core Session System | 4-5 |
| 2 | Context Commands | 3-4 |
| 3 | Smart Validation | 2-3 |
| 4 | MCP Tools | 2-3 |
| 5 | Time Tracking | 2-3 |
| 6 | CLI Integration | 3-4 |
| 7 | API Middleware | 2-3 |
| 8 | Instruction System | 2-3 |
| **TOTAL** | **Full Implementation** | **20-28 hours** |

---

## 🚀 Success Metrics

After implementation, we should be able to:

1. **Start a work session in < 5 seconds**
   - Auto-suggest context from recent work
   - Resume interrupted sessions automatically

2. **Work without repeating context**
   - Set task once, all commands inherit
   - No task IDs in 80% of commands

3. **Complete tasks with confidence**
   - Smart validation catches missing info
   - Prompts guide complete documentation

4. **Recover from any failure**
   - Session persists all work
   - Loss limited to 5-30 seconds max
   - Agent reconstructs from session key

5. **Track time accurately**
   - Auto-start/stop on context switch
   - Compare actual vs estimated
   - Improve estimates over time

6. **Get actionable guidance**
   - Every command returns next steps
   - Instructions prevent context loss
   - Agents stay on track reliably

---

## 📝 Notes

**Deferred to v1.1:**
- GUI integration (depends on type/command system rebuild)
- Advanced analytics dashboard
- Multi-agent session coordination
- AI-powered time estimation
- Pattern learning from session history

**Dependencies:**
- ✅ Existing orchestration tables (work_items, sprints, agents)
- ✅ MCP server infrastructure
- ✅ CLI framework
- ⏳ Command/type system rebuild (other agent)

**Order of Implementation:**
1. Core session system (foundation)
2. Context management (scoping)
3. Validation system (quality)
4. Instruction system (guidance)
5. Time tracking (metrics)
6. CLI integration (UX)
7. API/MCP integration (tooling)

---

*This plan enables reliable, context-aware orchestration with minimal data loss and maximum agent resilience.*
|