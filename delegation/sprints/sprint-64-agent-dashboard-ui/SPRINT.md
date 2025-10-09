# Sprint 64: Agent Management Dashboard UI

**Sprint Code**: `SPRINT-64`  
**Priority**: HIGH  
**Type**: Feature - User Interface  
**Status**: Planned  
**Estimated**: 14-18 hours (2-3 days)

## Sprint Overview

Build a focused Agent Management Dashboard with grid-based card layout, profile editor, and full CRUD operations. This sprint delivers a clean, functional UI for managing agent profiles without the complexity of chat integration, avatar AI generation, or advanced features.

## Business Goals

- ✅ Provide visual interface for agent profile management
- ✅ Enable create, read, update, delete operations via UI
- ✅ Display agent status and key information at-a-glance
- ✅ Support quick editing through modal editor
- ✅ Foundation for future agent system enhancements

## Sprint Tasks

| Task ID | Description | Status | Estimated | Dependencies |
|---------|-------------|--------|-----------|--------------|
| **UI-00** | Terminology Migration: "Agents" → "Agent Profiles" | `todo` | 1-2h | None |
| **UI-01** | Agent Profile Dashboard Grid Layout | `todo` | 3-4h | UI-00 |
| **UI-02** | Agent Profile Mini-Card Component | `todo` | 2-3h | UI-01 |
| **UI-03** | Agent Profile Editor Modal | `todo` | 4-5h | UI-02 |
| **UI-04** | Agent Profile CRUD Operations & API Integration | `todo` | 3-4h | UI-03 |

## Detailed Task Breakdown

### UI-00: Terminology Migration - "Agents" to "Agent Profiles" (1-2h)
**Description**: Migrate all references from "Agents" to "Agent Profiles" for clarity

**Deliverables**:
- Rename command classes: `AgentListCommand` → `AgentProfileListCommand`
- Rename React components: `AgentListModal` → `AgentProfileListModal`  
- Update command registry with new mappings (`/agent-profiles`, `/ap`)
- Keep deprecated aliases (`/agents`, `/agent-list`) with deprecation notices
- Rename YAML command directory: `agents/` → `agent-profiles/`
- Update all user-facing text to say "Agent Profile" or "Agent Profiles"
- Update modal routing in `CommandResultModal.tsx`

**Acceptance Criteria**:
- New commands work: `/agent-profiles`, `/ap`, `/agent-profile-detail`, `/apd`
- Old commands still work but show deprecation warning
- All UI text says "Agent Profile" not "Agent"
- No broken imports or TypeScript errors
- Backward compatibility maintained

**Why First**: Establishes clear terminology before building new UI. Prevents confusion between agent profiles (configurations) and agent instances (runtime).

---

### UI-01: Agent Profile Dashboard Grid Layout (3-4h)
**Description**: Create main dashboard page with responsive grid layout for agent profile cards

**Deliverables**:
- New route/page component: `AgentProfileDashboard.tsx`
- Responsive grid layout (3-4 columns on desktop, 2 on tablet, 1 on mobile)
- Header with "Create New Agent Profile" button
- Loading states and empty state
- Integration with new `/agent-profiles` command to launch dashboard

**Acceptance Criteria**:
- Dashboard displays in grid layout
- Responsive across screen sizes
- Empty state shows helpful message
- Create button positioned prominently

---

### UI-02: Agent Profile Mini-Card Component (2-3h)
**Description**: Create agent profile mini-card for grid display with status indicator and menu

**Deliverables**:
- `AgentProfileMiniCard.tsx` component
- Display: agent profile name, type badge, mode badge, description preview
- Random status indicator (3 colors: green/yellow/red) - temporary placeholder
- Three-dot menu (⋮) with options: Edit, Duplicate, Delete
- Click anywhere on card opens editor
- Hover states and animations

**Acceptance Criteria**:
- Card shows all key agent info
- Status indicator displays random color on each load
- Menu opens with three options
- Card is clickable to open editor
- Visual polish matches app design system

---

### UI-03: Agent Profile Editor Modal (4-5h)
**Description**: Full-screen or large modal for editing agent profiles

**Deliverables**:
- `AgentProfileEditor.tsx` modal component
- Form fields for all agent properties:
  - Name (required)
  - Slug (auto-generated, editable)
  - Type (dropdown from AgentType enum)
  - Mode (dropdown from AgentMode enum)
  - Description (textarea)
  - Status (dropdown from AgentStatus enum)
  - Capabilities (tag input, array)
  - Constraints (tag input, array)
  - Tools (tag input, array)
  - Metadata (JSON editor or key-value pairs)
- Form validation
- Save/Cancel buttons
- Three-dot menu in header (same options as mini-card)
- Works for both create and edit modes

**Acceptance Criteria**:
- All fields render and validate properly
- Type/Mode/Status dropdowns populated from backend enums
- Tag inputs work for arrays (capabilities, constraints, tools)
- Validation errors display clearly
- Modal can be closed without saving
- Changes persist on save

---

### UI-04: Agent CRUD Operations & API Integration (3-4h)
**Description**: Wire up all CRUD operations to backend API endpoints

**Deliverables**:
- API client methods for agent operations:
  - `GET /api/agents` - List all agents
  - `POST /api/agents` - Create agent
  - `PUT /api/agents/{id}` - Update agent
  - `DELETE /api/agents/{id}` - Delete agent
  - `GET /api/agents/types` - Get available types
  - `GET /api/agents/modes` - Get available modes
  - `GET /api/agents/statuses` - Get available statuses
- React hooks: `useAgents`, `useAgentMutations`
- Optimistic updates for better UX
- Error handling and toast notifications
- Confirmation dialog for delete operations
- Duplicate agent functionality (create from existing)

**Acceptance Criteria**:
- All CRUD operations work end-to-end
- Enum values loaded from backend
- Delete requires confirmation
- Success/error messages display
- Duplicate creates new agent with "-copy" suffix
- UI updates immediately with optimistic updates

## Technical Implementation Notes

### Backend API Endpoints Needed

Most endpoints already exist via `AgentProfileService`. May need to add:

```php
// routes/api.php or routes/internal.php
Route::prefix('agents')->group(function () {
    Route::get('/', [AgentController::class, 'index']);
    Route::post('/', [AgentController::class, 'store']);
    Route::get('/{agent}', [AgentController::class, 'show']);
    Route::put('/{agent}', [AgentController::class, 'update']);
    Route::delete('/{agent}', [AgentController::class, 'destroy']);
    Route::post('/{agent}/duplicate', [AgentController::class, 'duplicate']);
    
    // Metadata endpoints
    Route::get('/meta/types', [AgentController::class, 'types']);
    Route::get('/meta/modes', [AgentController::class, 'modes']);
    Route::get('/meta/statuses', [AgentController::class, 'statuses']);
});
```

### Component Structure

```
resources/js/
├── pages/
│   └── AgentDashboard.tsx           # Main dashboard page
├── components/
│   └── agents/
│       ├── AgentMiniCard.tsx        # Grid card component
│       ├── AgentProfileEditor.tsx   # Edit modal
│       └── AgentStatusIndicator.tsx # Status dot (random color)
├── hooks/
│   └── useAgents.ts                 # API hooks
└── lib/
    └── api/
        └── agents.ts                # API client methods
```

### Status Indicator Implementation

For now, status indicator should be a simple colored dot:
- Random color on each render: `['bg-green-500', 'bg-yellow-500', 'bg-red-500']`
- Position: top-right corner of mini-card
- Size: 8px diameter
- Later: will be replaced with real agent status logic

### Form Field Details

**Type Dropdown** (backend-engineer, frontend-engineer, etc.):
- Load from `GET /api/agents/meta/types`
- Display: label + description as tooltip
- Shows default mode for each type

**Mode Dropdown** (implementation, planning, review, etc.):
- Load from `GET /api/agents/meta/modes`
- Auto-populated based on type selection
- Can be overridden manually

**Tag Inputs** (capabilities, constraints, tools):
- Use shadcn/ui badge component
- Support adding/removing tags
- Press Enter to add new tag
- Click X to remove tag

## Dependencies

**Backend**:
- ✅ AgentProfile model exists
- ✅ AgentProfileService exists
- ❓ AgentController - needs creation/verification
- ✅ Enums: AgentType, AgentMode, AgentStatus

**Frontend**:
- ✅ Shadcn/ui components available
- ✅ React Router for navigation
- ✅ Existing modal patterns
- ✅ Form handling utilities

## Success Metrics

- [ ] Dashboard displays all existing agents
- [ ] Can create new agent from dashboard
- [ ] Can edit any agent inline
- [ ] Can delete agent with confirmation
- [ ] Can duplicate agent
- [ ] All form validations work
- [ ] UI is responsive and polished
- [ ] No regressions in existing `/agents` command

## Out of Scope (Future Sprints)

The following features are explicitly **NOT** included in this sprint:

- ❌ Avatar upload/generation system
- ❌ Agent selection in chat interface  
- ❌ Agent history/versioning UI
- ❌ Agent performance metrics
- ❌ Agent collaboration features
- ❌ Advanced filtering/sorting
- ❌ Real agent status logic (using random for now)
- ❌ Agent memory visualization
- ❌ Task assignment from agent dashboard
- ❌ Agent lineage/cloning visualization

## Migration from Existing Code

**AgentListModal.tsx → AgentProfileListModal.tsx** (UI-00):
- Rename component for clarity
- Update all references to use "Agent Profile" terminology
- Keep backward compatibility via export alias
- Show deprecation notice when accessed via old `/agents` command

**AgentListCommand.php → AgentProfileListCommand.php** (UI-00):
- Rename command class for clarity
- New primary command: `/agent-profiles`
- Old `/agents` command redirects with deprecation notice
- Update to route to new dashboard (UI-01)

## Post-Sprint Tasks

After completing this sprint:
1. User testing and feedback collection
2. Plan Sprint 65: Agent Avatar System
3. Plan Sprint 66: Agent Chat Integration
4. Consider agent status logic implementation
5. Analytics on agent usage patterns

## Risk Mitigation

**Risk**: Backend API endpoints don't exist  
**Mitigation**: Verify/create AgentController early in sprint

**Risk**: Form validation complexity  
**Mitigation**: Use existing form patterns from other modals

**Risk**: Performance with many agents  
**Mitigation**: Add pagination or virtual scrolling if needed

**Risk**: Breaking existing `/agents` command  
**Mitigation**: Create new route, keep modal as fallback

## Related Documentation

- Original planning: `delegation/imported/sprint-43/UX-04-02-agent-manager-system/`
- Backend service: `app/Services/AgentProfileService.php`
- Agent model: `app/Models/AgentProfile.php`
- Existing modal: `resources/js/components/orchestration/AgentListModal.tsx`
