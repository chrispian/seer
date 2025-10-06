# Command System Fix Task

## **DEBUGGING SESSION CONCLUSIONS**

### **Root Cause: YAML System is Overly Complex and Fragile**
After debugging the sprints command, we discovered the real issues:

1. **Template Engine Issues**: Complex Twig templates with multi-step JSON encoding/decoding
2. **Data Flow Complexity**: `Step 1 → JSON string → Step 2 → parse JSON → modify → JSON encode → Step 3`
3. **Wrong Architecture**: Commands should return clean data objects, not processed display messages
4. **Template Processing Failures**: Seeing raw template strings like `{% set count = steps.query-sprints.output.count %}` instead of processed values

### **Original vs New System Comparison**

**Original System (Simple & Deterministic):**
```php
class SprintListCommand {
    public function handle(): CommandResponse {
        $sprints = Sprint::query()->get();
        return new CommandResponse(
            type: 'sprint',
            panelData: ['sprints' => $sprintData]
        );
    }
}
```

**Current YAML System (Overly Complex):**
```yaml
- query-sprints → format-sprint-data → query-task-counts → format-success-message → show-sprint-list
# With JSON encode/decode chains and complex template processing
```

## **NEW SIMPLE DETERMINISTIC APPROACH**

### **Core Principles**
1. **Each command knows**: What data it needs, how to route, what component to use
2. **No complex templates**: Direct PHP classes with simple data fetching
3. **Clean data objects**: Return arrays/objects, not processed display strings
4. **Direct component routing**: Command specifies exact component to use
5. **Deterministic behavior**: No guessing, no multi-step processing chains

### **Root Issues (Updated)**
1. **YAML System Too Complex**: Multi-step template processing with JSON chains
2. **Template Engine Fragility**: Twig processing failing silently
3. **Wrong Data Flow**: Should return data objects, not display messages
4. **No Direct Component Routing**: Commands don't specify which modal to use
5. **Premature Optimization**: Complex templating before basic functionality works

### **Current Broken Flow**
```
Command → YAML Backend → Generic Response → CommandResultModal Guessing → Wrong Modal
```

### **Desired Flow**
```
Command → YAML Backend → Component-Specific Response → Direct Modal Launch
```

## **Specific Issues to Fix**

### **1. Sprints Command**
- **Current**: Returns generic response, CommandResultModal guesses wrong component
- **Should**: Directly launch SprintListModal
- **Fix**: Add component specification to YAML response, direct frontend routing

### **2. Help Command**
- **Current**: Hardcoded command list, missing many commands
- **Should**: Dynamic content from command registry
- **Fix**: Make help registry-driven, scan all YAML files for help content

### **3. Todo Commands**
- **Current**: `/todo list` creates todo named "list", `/todos` shows empty table
- **Should**: `/todo` = create, `/todos` = list (existing behavior), `todos-ui` = alias
- **Fix**: Clarify command routing and behavior

### **4. Settings Command**
- **Current**: Shows modal briefly then triggers settings UI (double execution)
- **Should**: Either YAML command OR old action, not both
- **Fix**: Remove dual triggering

## **NEW SIMPLE COMMAND ARCHITECTURE**

### **1. Simple PHP Command Classes**
```php
// app/Commands/SprintListCommand.php
class SprintListCommand {
    public function handle(): array {
        $sprints = Sprint::query()->orderBy('code')->get();
        
        return [
            'type' => 'sprint',
            'component' => 'SprintListModal',
            'data' => $sprints->map(fn($sprint) => [
                'id' => $sprint->id,
                'code' => $sprint->code,
                'title' => $sprint->meta['title'] ?? $sprint->code,
                'status' => $sprint->meta['status'] ?? 'active',
            ])->all()
        ];
    }
}
```

### **2. Command Registry (Simple Mapping)**
```php
// app/Services/CommandRegistry.php
return [
    'sprints' => SprintListCommand::class,
    'help' => HelpCommand::class,
    'tasks' => TaskListCommand::class,
];
```

### **3. Frontend Routing (Direct)**
```typescript
const componentMap = {
    'SprintListModal': SprintListModal,
    'TaskListModal': TaskListModal,
    'HelpModal': HelpModal,
}

// Simple routing - no guessing
const Component = componentMap[result.component]
return <Component data={result.data} isOpen={true} onClose={onClose} />
```

### **4. Help Command (Registry-Driven)**
```php
class HelpCommand {
    public function handle(): array {
        $commands = $this->registry->getAllCommands();
        return [
            'component' => 'HelpModal',
            'data' => $commands
        ];
    }
}
```

## **REVISED IMPLEMENTATION PLAN**

### **Phase 1: Create Simple PHP Command System ✅ COMPLETED**
1. ✅ **Create PHP command classes** for Help and Sprints
2. ✅ **Update CommandController** to route to PHP classes instead of YAML
3. ✅ **Update frontend** to use component field for direct routing
4. ✅ **Test Help and Sprints** commands work with clean data flow

#### **Phase 1 Results:**
- ✅ **Help command** - Shows dynamic command list from PHP registry
- ✅ **Sprints command** - Opens SprintListModal with 23 sprints, all interactions work
- ✅ **Modal functionality** - Close (ESC/click), refresh button, row clicks work
- ✅ **Response format** - Matches frontend expectations perfectly
- ✅ **Clean data flow** - No template processing, direct PHP → JSON → Frontend

#### **Phase 1 Issues Fixed:**
- ✅ Response format mismatch (wrapped vs flat structure)
- ✅ Missing event handlers (onSprintSelect, onRefresh)
- ✅ Modal closing issue (hardcoded isOpen={true})
- ✅ Component routing working correctly

### **Phase 2: Handle Settings Command ✅ COMPLETED**
1. ✅ **Investigate settings** - Determined UI command, not data command
2. ✅ **Remove settings command** - Not suitable for current system
3. ✅ **Create backlog tasks** - UI commands and keyboard shortcuts

### **Phase 3: Convert Core Data Commands ✅ COMPLETED**
Converted 16 critical commands to PHP classes with proper modal routing.

## **COMMAND CONVERSION TRACKING**

### **✅ COMPLETED (18/36 commands) - CORE SYSTEM WORKING**

**Command Infrastructure:**
- ✅ `/help` - HelpCommand.php (enhanced with aliases, markdown formatting, categories)
- ✅ `/sprints` - SprintListCommand.php (SprintListModal working)
- ✅ `/tasks` - TaskListCommand.php (TaskListModal with filtering)
- ✅ `/agents` - AgentListCommand.php (Agent management)
- ✅ `/todo` - TodoCommand.php **NEWLY FIXED** - loads 50 todos in TodoManagementModal

**Fragment & Search Commands:**
- ✅ `/search` - SearchCommand.php (backend ready, UI next)
- ✅ `/vault` - VaultCommand.php
- ✅ `/project` - ProjectCommand.php
- ✅ `/recall` - RecallCommand.php

**Detail/Management Commands:**
- ✅ `/sprint-detail` - SprintDetailCommand.php
- ✅ `/task-detail` - TaskDetailCommand.php
- ✅ `/task-create` - TaskCreateCommand.php
- ✅ `/task-assign` - TaskAssignCommand.php

**Utility Commands:**
- ✅ `/bookmark` - BookmarkCommand.php
- ✅ `/clear` - ClearChatCommand.php
- ✅ `/session` - SessionCommand.php
- ✅ `/backlog-list` - BacklogListCommand.php

### **🎯 CURRENT WORK - Phase 4: Search Command Enhancement**
- ⏳ `/search` - Enhancing SearchCommand with full-featured modal
  - Backend complete, needs UI improvements
  - Add search bar, filter chips, sort options
  - Implement fragment click → navigate to chat session with context (±5 fragments)
  - Requires new chat view feature: focused fragment with lazy loading
  - **Dependency:** Fragment navigation task (T-FRAG-NAV-01) - needed for bookmarks too

### **📋 HIGH PRIORITY QUEUE (4 commands)**
- ⬜ `/agents` - Agent listing (orchestration core)
- ⬜ `/backlog-list` - Backlog management (workflow) **NOTE: Discuss todo/backlog relationship**
- ⬜ `/bookmark` - Bookmark functionality (user feature)

### **📝 REMAINING COMMANDS (30 commands)**
**Fragment & Content Commands:**
- ⬜ `/accept` - Accept Inbox Fragment
- ⬜ `/bookmark` - Bookmark Management *(already listed above)*
- ⬜ `/channels` - List Channels  
- ⬜ `/clear` - Clear Chat
- ⬜ `/frag` - Create Fragment
- ⬜ `/frag-simple` - Create Fragment Simple
- ⬜ `/inbox` - Inbox Management (Unified)
- ⬜ `/join` - Join Channel
- ⬜ `/link` - Link
- ⬜ `/name` - Set Channel Name
- ⬜ `/note` - Create Note
- ⬜ `/recall` - Recall fragments
- ⬜ `/routing` - Routing Management
- ⬜ `/search` - Fragment Search (Unified)
- ⬜ `/session` - Session Management

**Task & Project Management:**
- ⬜ `/agents` - Agent List *(already listed above)*
- ⬜ `/backlog-list` - Backlog List *(already listed above)*
- ⬜ `/sprint-detail` - Sprint Detail
- ⬜ `/task-assign` - Task Assign (might be complex)
- ⬜ `/task-create` - Task Create
- ⬜ `/task-detail` - Task Detail
- ⬜ `/tasks` - Task List *(already listed above)*

**Scheduling Commands:**
- ⬜ `/schedule:create` - Create Scheduled Task
- ⬜ `/schedule:delete` - Delete Scheduled Task  
- ⬜ `/schedule:detail` - Show Schedule Details
- ⬜ `/schedule:list` - List Scheduled Tasks
- ⬜ `/schedule:pause` - Pause Scheduled Task
- ⬜ `/schedule:resume` - Resume Scheduled Task
- ⬜ `/scheduler` - Open Scheduler Management Interface

**Utility Commands:**
- ⬜ `/news-digest` - Generate News Digest
- ⬜ `/remind` - Create Reminder
- ⬜ `/setup` - Open Setup Wizard
- ⬜ `/todo` - Todo Management (Deterministic) **NOTE: Discuss relationship with backlog-list**
- ⬜ `/types` - Open Type System Management

### **🗑️ REMOVED COMMANDS**
- ❌ `/settings` - Removed (UI command, not data command)

### **📝 DISCUSSION NOTES**
- **Todo vs Backlog**: Need to clarify relationship between `/todo`, `/backlog-list`, and task management
- **Complex Commands**: Some task commands (`/task-assign`) may need special handling
- **Schedule Commands**: Large group - consider if these should be converted or deprecated

### **Key Changes From Original Plan**
- **Dump YAML entirely** for PHP classes
- **No complex templates** - simple data fetching
- **Direct component routing** - no guessing logic
- **Focus on deterministic behavior** over flexibility

## **Requirements**
- All commands that were working should work (backend and frontend)
- Commands should use specific components, not default modals
- Command palette autocomplete should update automatically
- Commands should register their own help content
- Simple, predictable architecture

## **FILES TO CREATE/MODIFY**

### **New Files**
- `app/Commands/HelpCommand.php` - Simple help command
- `app/Commands/SprintListCommand.php` - Simple sprints command
- `app/Commands/BaseCommand.php` - Base class for commands

### **Modified Files**
- `app/Http/Controllers/CommandController.php` - Route to PHP classes
- `app/Services/CommandRegistry.php` - Simple command mapping
- `resources/js/islands/chat/CommandResultModal.tsx` - Direct component routing

### **Files to Remove Later**
- `fragments/commands/*/command.yaml` - Complex YAML commands
- `app/Services/Commands/DSL/*` - Template engine system

## **SUCCESS CRITERIA**
- ✅ `/help` shows dynamic command list from registry
- ✅ `/sprints` launches SprintListModal with clean data
- ⏳ Settings command works without double-execution
- ✅ Command routing is predictable and deterministic
- ✅ No more complex template processing or JSON encode/decode chains

## **DEBUGGING NOTES**
- ✅ **FIXED**: Template engine failing: Raw `{% set count = %}` strings in output
- ✅ **FIXED**: Modal receiving message strings instead of data objects
- ✅ **FIXED**: `from_json` filters not working due to template processing failures
- ✅ **SOLUTION**: Replaced complex multi-step YAML processing with simple PHP classes

## **PHASE 1 COMPLETE - LESSONS LEARNED**
1. **Simple PHP classes work better** than complex YAML templates
2. **Direct component routing** eliminates guessing and errors
3. **Response format consistency** is critical for frontend integration
4. **Event handlers must be provided** for modal interactions to work
5. **Modal state management** - use parent `isOpen` prop, not hardcoded values

## **BACKLOG ITEMS CREATED**

### **TASK-CMD-UI-ELEMENTS: Expand Command System for UI Elements**
- **Priority**: Medium  
- **Estimate**: 8 hours
- **Description**: Enhance command system to support UI navigation commands like `/settings`, `/preferences`, etc. Commands should trigger UI overlays, modal dialogs, and navigation actions instead of just data display.
- **Tags**: command-system, ui-navigation, enhancement
- **Rationale**: Current system works great for data commands, but UI commands need different handling pattern

### **TASK-KB-SHORTCUT-STRATEGY: Design Keyboard Shortcut Strategy**
- **Priority**: Medium
- **Estimate**: 12 hours
- **Description**: Design and implement a comprehensive keyboard shortcut system with user-configurable shortcuts. Create a settings/registry solution for shortcut management with consideration for NativePHP deployment capabilities.
- **Tags**: keyboard-shortcuts, user-settings, native-app, accessibility
- **Key Requirements**:
  - User-configurable shortcuts in settings
  - Global shortcut registration system
  - Conflict detection and resolution
  - Settings storage (localStorage + backend sync)
  - NativePHP integration for system-level shortcuts
  - Accessibility compliance
- **First Use Case**: Settings overlay shortcut (e.g., Cmd+Shift+S)
- **Future Use Cases**: Command palette, navigation, modal shortcuts
- **NativePHP Note**: Investigate if NativePHP provides system-level shortcut registration beyond web keyboard events