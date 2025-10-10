# Orchestration System v1.0 - Production Readiness Plan

**Date:** 2025-10-10  
**Status:** Planning  
**Goal:** Make orchestration system the single source of truth, remove file dependencies, integrate all subsystems

---

## 🎯 Vision

**The orchestration system is the foundation** - all agent work, all tasks, all communication flows through it.

### Core Principles
1. **Database is truth** - No file-based tracking (except temporary scratch)
2. **Chat-first interaction** - Browser ↔ Agent communication through chat system
3. **Artifact storage** - All documentation, assets, files in artifact store
4. **Unified workflow** - Same patterns for humans and agents

---

## 📊 Current State Assessment

### ✅ What Works (Core System - 90% Complete)

| Component | Status | Details |
|-----------|--------|---------|
| Database Schema | ✅ Complete | Sprints, Tasks, Agents, Messages, Artifacts |
| MCP Tools | ✅ Complete | 19 tools covering all CRUD operations |
| CLI Commands | ✅ Complete | Full orchestration:* namespace |
| Postmaster | ✅ Complete | Message routing, CAS storage, redaction |
| Messaging API | ✅ Complete | Inbox, send, read, broadcast |
| Memory Service | ✅ Complete | Durable/ephemeral storage |
| Agent INIT | ✅ Complete | 4-step protocol |

### ⚠️ What Needs Work (Integration - 40% Complete)

| Component | Status | Gap |
|-----------|--------|-----|
| Documentation | ⚠️ Scattered | Multiple locations, file-based references |
| Quick Commands | ⚠️ Missing | No fast context/note/log updates |
| UI Dashboard | ⚠️ Missing | No orchestration overview |
| Artifacts UI | ⚠️ Missing | No browsing/downloading UI |
| Chat Integration | ❌ Not Started | PostMaster init not hooked to chat |
| OpenHands Runner | ❌ Not Started | Browser ↔ Shell communication |
| Agent Activity Log | ⚠️ Hidden | Exists but not visible |

---

## 🚀 V1.0 Roadmap

### Sprint A: Quick Operations & UX (3-4 hours)

**Goal:** Make common operations fast and intuitive

#### A1: Quick Update Commands
Create fast operations for agents:

```bash
# Quick context append
/task-note T-TASK-01 "Completed migration, found performance issue with indexes"

# Quick context update  
/task-context T-TASK-01 --append "## Dependencies\n- Requires Redis for caching"

# Quick status + note
/task-done T-TASK-01 "All tests passing, PR created"

# Bulk status
/tasks-status T-TASK-01,T-TASK-02,T-TASK-03 --status=completed

# Agent activity log (visible)
/agent-log "Starting task T-TASK-01, reviewing codebase"
```

**Deliverables:**
- [ ] `app/Commands/Orchestration/Task/QuickNoteCommand.php`
- [ ] `app/Commands/Orchestration/Task/QuickContextCommand.php`
- [ ] `app/Commands/Orchestration/Task/QuickDoneCommand.php`
- [ ] `app/Commands/Orchestration/Task/BulkStatusCommand.php`
- [ ] `app/Commands/Orchestration/Agent/LogActivityCommand.php`
- [ ] MCP tools for above (5 new tools)

---

### Sprint B: Artifacts UI & Commands (3-4 hours)

**Goal:** Make artifact storage accessible and useful

#### B1: Artifacts Dashboard
```bash
# List artifacts for task
/artifacts T-TASK-01

# List all artifacts for sprint
/artifacts SPRINT-UNIFY-1

# Upload artifact
/artifact-upload --file=path/to/doc.pdf --task=T-TASK-01

# Download artifact
/artifact-download fe://artifacts/by-task/.../doc.pdf
```

#### B2: Artifacts UI Component
- `ArtifactsListModal.tsx` - Browse artifacts by task/sprint
- Inline upload/download
- Preview for text/markdown files
- Link artifacts to tasks

**Deliverables:**
- [ ] `app/Commands/Orchestration/Artifacts/ListCommand.php`
- [ ] `app/Commands/Orchestration/Artifacts/UploadCommand.php`
- [ ] `app/Commands/Orchestration/Artifacts/DownloadCommand.php`
- [ ] `resources/js/components/orchestration/ArtifactsListModal.tsx`
- [ ] MCP tools: `artifacts_list`, `artifacts_upload`

---

### Sprint C: Documentation Consolidation (2-3 hours)

**Goal:** Single source of truth for orchestration docs

#### C1: Migrate to Database-First
Move all documentation from files → artifact storage:

**Current (File-based):**
```
delegation/sprints/*.md
docs/orchestration/*.md
docs/orchestration system/*.md
```

**New (Artifact-based):**
```
Artifacts linked to:
- Project (global docs)
- Sprint (sprint-specific docs)
- Task (task-specific docs)
```

#### C2: Create Single Getting Started Guide
```markdown
# Orchestration System - Getting Started

## For Agents

### 1. Initialization
When you start, run INIT protocol:
- Resume memory
- Load profile
- Healthcheck
- Confirm plan

### 2. Check Inbox
/messages-check --status=unread

### 3. Start Task
/task-status T-TASK-01 --status=in_progress

### 4. Work & Update
/task-note T-TASK-01 "Working on X, found Y issue"

### 5. Complete
/task-done T-TASK-01 "Completed successfully. Files: X, Y, Z"

## For Humans

### Create Sprint
/sprints-create SPRINT-CODE --title="Sprint Title"

### Create Tasks
/task-create T-CODE --title="Task Title" --sprint=SPRINT-CODE

### Monitor Progress
/sprint-detail SPRINT-CODE
/tasks --sprint=SPRINT-CODE --status=in_progress
```

**Deliverables:**
- [ ] `docs/ORCHESTRATION_GETTING_STARTED.md`
- [ ] Archive old docs to `docs/archived/`
- [ ] Update CLAUDE.md with orchestration-first workflow
- [ ] Create artifact: "Orchestration Quick Reference"

---

### Sprint D: Chat Integration (4-5 hours)

**Goal:** Agent ↔ Human communication through chat system

#### D1: PostMaster Chat Bridge
When PostMaster sends parcel → create chat message:

```php
// ProcessParcel job
$parcel = [...];

// 1. Store artifacts in CAS
$artifacts = $this->storeAttachments($parcel);

// 2. Create orchestration message (existing)
$message = Message::create([...]);

// 3. NEW: Create chat message
$chatMessage = ChatMessage::create([
    'conversation_id' => $task->conversation_id,
    'role' => 'system',
    'content' => "New task assigned: {$task->title}",
    'metadata' => [
        'orchestration_message_id' => $message->id,
        'artifacts' => $artifacts,
    ]
]);
```

#### D2: Agent Init on Chat Start
When new chat session starts → run Agent INIT:

```php
// ChatSessionController@create
$session = ChatSession::create([...]);

// NEW: Initialize agent for this session
$initResult = app(AgentInitService::class)->initialize($agent, $session);

// Store init result in session metadata
$session->update([
    'metadata->init_result' => $initResult,
    'metadata->agent_ready' => $initResult['status'] === 'ready',
]);
```

#### D3: Chat Commands for Orchestration
Enable orchestration commands in chat:

```
User: /sprint-detail SPRINT-UNIFY-1
Bot: [Shows sprint modal with tasks]

User: /task-status T-UNIFY-01 --status=in_progress
Bot: ✅ Task T-UNIFY-01 marked as in_progress

User: /task-note T-UNIFY-01 "Found issue with indexes"
Bot: ✅ Note added to T-UNIFY-01
```

**Deliverables:**
- [ ] Update `ProcessParcel` job to create chat messages
- [ ] Update `ChatSessionController` to run Agent INIT
- [ ] Add orchestration commands to chat command router
- [ ] Update chat UI to show orchestration context
- [ ] Test end-to-end: Task created → Chat message → Agent responds

---

### Sprint E: Orchestration Dashboard (3-4 hours)

**Goal:** Single view of all orchestration activity

#### E1: Dashboard Command & UI
```bash
/orchestration  # or /dashboard
```

Shows:
- **Active Sprints** (in progress, with progress bars)
- **My Tasks** (assigned to current agent/user)
- **Recent Activity** (task updates, messages, artifacts)
- **Agent Status** (online, busy, idle)
- **Messages** (unread count, recent)

#### E2: Dashboard Component
```typescript
<OrchestrationDashboard>
  <StatsCards>
    <StatCard title="Active Sprints" value={3} />
    <StatCard title="My Tasks" value={5} />
    <StatCard title="Unread Messages" value={2} />
  </StatsCards>
  
  <ActiveSprintsGrid>
    <SprintCard sprint={...} progress={60%} />
  </ActiveSprintsGrid>
  
  <MyTasksList>
    <TaskRow task={...} status="in_progress" />
  </MyTasksList>
  
  <RecentActivity>
    <ActivityItem type="task_update" ... />
    <ActivityItem type="message_received" ... />
  </RecentActivity>
</OrchestrationDashboard>
```

**Deliverables:**
- [ ] `app/Commands/Orchestration/DashboardCommand.php`
- [ ] `resources/js/components/orchestration/OrchestrationDashboard.tsx`
- [ ] `resources/js/components/orchestration/SprintCard.tsx`
- [ ] `resources/js/components/orchestration/ActivityFeed.tsx`
- [ ] API endpoint: `/api/orchestration/dashboard`

---

### Sprint F: OpenHands Runner Integration (Future - 6-8 hours)

**Goal:** Browser ↔ Shell communication for agent execution

**Note:** This is a larger feature that will need its own sprint planning. Outline below for context.

#### Architecture
```
Browser (User)
    ↓ WebSocket
Chat System
    ↓ Message Queue
PostMaster
    ↓ Parcel
Agent Runner (OpenHands)
    ↓ Shell Commands
System
    ↓ Results
Agent Runner
    ↓ Artifact Upload
Artifact Store
    ↓ fe:// URI
Chat System
    ↓ Message
Browser (User sees result)
```

#### Components Needed
1. **Runner Service** - Manages OpenHands instances
2. **Execution API** - Submit commands, get results
3. **Streaming Bridge** - Real-time output to chat
4. **Sandbox Manager** - Isolated execution environments
5. **Result Handler** - Parse output, create artifacts

**Defer to Future Sprint** - Core orchestration must be solid first

---

## 📋 Detailed Task Breakdown

### Sprint A: Quick Operations

#### A1.1: QuickNoteCommand
```php
// app/Commands/Orchestration/Task/QuickNoteCommand.php
class QuickNoteCommand extends BaseCommand
{
    public function handle(string $taskCode, string $note): array
    {
        $task = WorkItem::where('task_code', $taskCode)->firstOrFail();
        
        // Append to context_content
        $existing = $task->context_content ?? '';
        $task->update([
            'context_content' => $existing . "\n\n" . now()->format('Y-m-d H:i') . ": " . $note
        ]);
        
        // Log activity
        event(new TaskNoteAdded($task, $note));
        
        return [
            'type' => 'success',
            'component' => 'Toast',
            'data' => [
                'message' => "Note added to {$taskCode}",
                'task' => $task
            ]
        ];
    }
    
    public static function getUsage(): string
    {
        return '/task-note {task_code} {note}';
    }
}
```

#### A1.2: QuickContextCommand
```php
// app/Commands/Orchestration/Task/QuickContextCommand.php
class QuickContextCommand extends BaseCommand
{
    public function handle(string $taskCode, array $options = []): array
    {
        $task = WorkItem::where('task_code', $taskCode)->firstOrFail();
        
        if (isset($options['append'])) {
            $existing = $task->context_content ?? '';
            $task->update(['context_content' => $existing . "\n\n" . $options['append']]);
        } elseif (isset($options['replace'])) {
            $task->update(['context_content' => $options['replace']]);
        }
        
        return ['type' => 'success', 'data' => ['task' => $task]];
    }
    
    public static function getUsage(): string
    {
        return '/task-context {task_code} --append="text" | --replace="text"';
    }
}
```

#### A1.3: QuickDoneCommand
```php
// app/Commands/Orchestration/Task/QuickDoneCommand.php
class QuickDoneCommand extends BaseCommand
{
    public function handle(string $taskCode, string $summary): array
    {
        $task = WorkItem::where('task_code', $taskCode)->firstOrFail();
        
        $task->update([
            'status' => 'completed',
            'summary_content' => $summary,
            'completed_at' => now(),
        ]);
        
        event(new TaskCompleted($task));
        
        return [
            'type' => 'success',
            'component' => 'Toast',
            'data' => [
                'message' => "Task {$taskCode} marked complete ✅",
                'task' => $task
            ]
        ];
    }
    
    public static function getUsage(): string
    {
        return '/task-done {task_code} {summary}';
    }
}
```

---

## 🎯 Success Criteria for V1.0

After completing Sprints A-E, the system should:

### For Agents
✅ Initialize via chat with Agent INIT protocol  
✅ Receive task assignments via chat messages  
✅ Update task status with quick commands  
✅ Add notes/context without ceremony  
✅ Upload/download artifacts easily  
✅ View orchestration dashboard  
✅ Work entirely through chat + MCP tools  

### For Humans
✅ Create sprints & tasks via chat commands  
✅ Monitor progress in dashboard  
✅ See agent activity in real-time  
✅ Access artifacts through UI  
✅ No file-based workflow dependencies  
✅ Single "Getting Started" guide  

### For System
✅ Database is single source of truth  
✅ All docs/assets in artifact store  
✅ Chat system integrated with PostMaster  
✅ Agent initialization automatic  
✅ Activity logging visible  
✅ Clean, consolidated documentation  

---

## 📅 Implementation Timeline

### Phase 1: Quick Wins (Week 1)
- Sprint A: Quick Operations (3-4 hours)
- Sprint B: Artifacts UI (3-4 hours)

**Deliverable:** Fast, ergonomic agent workflow

### Phase 2: Integration (Week 1-2)
- Sprint C: Documentation (2-3 hours)
- Sprint D: Chat Integration (4-5 hours)

**Deliverable:** Chat-first orchestration, no file dependencies

### Phase 3: Polish (Week 2)
- Sprint E: Dashboard (3-4 hours)

**Deliverable:** Beautiful, functional orchestration UI

### Phase 4: Advanced (Future)
- Sprint F: OpenHands Runner (dedicated sprint)

**Deliverable:** Browser-to-shell execution

**Total Time:** 15-20 hours for Phases 1-3 (v1.0)

---

## 🔧 Technical Decisions

### 1. Artifact Storage Strategy
**Decision:** All non-code documentation goes in artifact store  
**Rationale:** Content-addressable, deduplicated, durable  
**Implementation:** Create artifacts linked to project/sprint/task  

### 2. Chat as Primary Interface
**Decision:** All agent ↔ human communication via chat  
**Rationale:** Familiar UX, already built, supports streaming  
**Implementation:** PostMaster creates chat messages, commands work in chat  

### 3. Quick Commands Pattern
**Decision:** Create convenience commands for common operations  
**Rationale:** Reduce friction, improve agent efficiency  
**Pattern:** `/task-verb TASK-CODE params` (verb: note, context, done, etc.)  

### 4. Dashboard as Hub
**Decision:** Single orchestration dashboard, not scattered views  
**Rationale:** Centralized visibility, better UX  
**Implementation:** DataManagementModal + custom cards  

### 5. Activity Logging
**Decision:** Agent activity log visible in UI  
**Rationale:** Transparency, debugging, progress tracking  
**Implementation:** Event-driven, stored in messages table  

---

## 🚨 Migration from File-Based System

### Current State (Files)
```
delegation/sprints/SPRINT-X/*.md
delegation/tasks/TASK-X/*.md
delegation/backlog/*.md
docs/orchestration/*.md
```

### Target State (Database + Artifacts)
```
Database:
- Sprints (orchestration_sprints)
- Tasks (orchestration_work_items)
- Notes (tasks.context_content)

Artifacts:
- Sprint documentation
- Task attachments
- Reference materials
- Generated reports
```

### Migration Steps
1. ✅ **Already migrated:** Sprint/task tracking to database
2. **To migrate:** Move active documentation to artifacts
3. **Archive:** Old markdown files to `delegation/archived/`
4. **Update:** CLAUDE.md and guides to reference new system

---

## 📖 Documentation Structure (Post-Migration)

### Single Entry Point
```
docs/ORCHESTRATION_GETTING_STARTED.md  ← YOU ARE HERE
```

### Deep Dives (As Needed)
```
docs/orchestration/
├── README.md                          # Overview
├── postmaster-and-init.md             # Messaging system
├── task-context-and-activity-logging.md
└── runners/                            # OpenHands (future)
```

### Archived (Reference Only)
```
docs/archived/
├── orchestration-v0-files/
└── old-sprint-workflow/
```

---

## 🎉 V1.0 Launch Checklist

### Code
- [ ] Sprint A complete (quick commands)
- [ ] Sprint B complete (artifacts UI)
- [ ] Sprint C complete (docs consolidated)
- [ ] Sprint D complete (chat integration)
- [ ] Sprint E complete (dashboard)

### Documentation
- [ ] ORCHESTRATION_GETTING_STARTED.md written
- [ ] CLAUDE.md updated (orchestration-first)
- [ ] Old docs archived
- [ ] API endpoints documented

### Testing
- [ ] Quick commands tested (agents + humans)
- [ ] Artifacts upload/download tested
- [ ] Chat integration tested end-to-end
- [ ] Dashboard loads and displays correctly
- [ ] Agent INIT protocol works

### Polish
- [ ] All commands have help text
- [ ] All MCP tools have descriptions
- [ ] Error messages are helpful
- [ ] Success toasts are encouraging

---

## 🔮 Post-V1.0 Roadmap

### Phase 5: Advanced Features
- Agent collaboration (multi-agent tasks)
- Approval workflows (human-in-loop)
- Scheduled tasks (cron-like)
- Task templates
- Sprint templates

### Phase 6: OpenHands Integration
- Runner service
- Sandbox management
- Streaming output
- Result artifacts

### Phase 7: Analytics & Insights
- Sprint velocity tracking
- Agent performance metrics
- Task completion predictions
- Bottleneck detection

---

## 📊 Metrics to Track

### Usage Metrics
- Commands/day (agents vs humans)
- Artifacts created/day
- Messages sent/day
- Average task completion time

### Quality Metrics
- Task success rate
- Agent init success rate
- Artifact storage usage
- Message delivery latency

### Adoption Metrics
- Active agents/day
- Active sprints/week
- Documentation access (artifact pulls)
- Dashboard views/day

---

**Document Version:** 1.0  
**Last Updated:** 2025-10-10  
**Status:** Ready for Sprint A  
**Estimated Completion:** 2-3 weeks for v1.0

