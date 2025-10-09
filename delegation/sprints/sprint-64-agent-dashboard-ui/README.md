# Sprint 64: Agent Management Dashboard UI

## Quick Start

This sprint delivers a focused Agent Management Dashboard with grid-based card layout, profile editor, and full CRUD operations.

### Sprint Structure

```
sprint-64-agent-dashboard-ui/
├── SPRINT.md                         # Sprint overview and goals
├── README.md                          # This file
├── UI-00-terminology-migration/    
│   └── TASK.md                        # "Agents" → "Agent Profiles" migration
├── UI-01-agent-dashboard-grid/    
│   └── TASK.md                        # Dashboard grid layout
├── UI-02-agent-mini-card/
│   └── TASK.md                        # Agent profile card component
├── UI-03-agent-profile-editor/
│   └── TASK.md                        # Profile editor modal
└── UI-04-crud-api-integration/
    └── TASK.md                        # API integration & CRUD
```

### Task Execution Order

**Sequential Dependencies:**
1. **UI-00**: Terminology Migration (1-2h) - **DO THIS FIRST**
2. **UI-01**: Agent Profile Dashboard Grid Layout (3-4h) - Requires UI-00
3. **UI-02**: Agent Profile Mini-Card Component (2-3h) - Requires UI-01
4. **UI-03**: Agent Profile Editor Modal (4-5h) - Requires UI-02
5. **UI-04**: CRUD Operations & API Integration (3-4h) - Requires UI-03

**Total Estimated**: 14-18 hours (2-3 days)

### Deliverables

**Terminology Migration:**
- Rename: `AgentListCommand.php` → `AgentProfileListCommand.php`
- Rename: `AgentListModal.tsx` → `AgentProfileListModal.tsx`
- Update: Command registry, modal routing, YAML definitions
- New commands: `/agent-profiles`, `/ap` (with deprecation for old `/agents`)

**Frontend Components:**
- `/pages/AgentProfileDashboard.tsx` - Main dashboard page
- `/components/agents/AgentProfileMiniCard.tsx` - Grid card component
- `/components/agents/AgentProfileEditor.tsx` - Edit modal
- `/components/ui/tag-input.tsx` - Tag input utility
- `/hooks/useAgentProfiles.ts` - API hooks
- `/lib/api/agent-profiles.ts` - API client

**Backend (if needed):**
- `AgentController.php` - API endpoints
- Routes for agent CRUD operations

### Features

✅ Grid-based dashboard (responsive: 4/2/1 columns)  
✅ Agent mini-cards with status indicators  
✅ Three-dot menu on cards (Edit/Duplicate/Delete)  
✅ Full profile editor modal  
✅ Create/Read/Update/Delete operations  
✅ Duplicate agent functionality  
✅ Form validation  
✅ Toast notifications  
✅ Optimistic updates  
✅ Confirmation dialogs  

### Out of Scope

❌ Avatar upload/generation  
❌ Chat integration  
❌ Agent history/versioning  
❌ Real agent status logic  
❌ Performance metrics  
❌ Advanced filtering  

## Backend Status Check

Before starting, verify backend components exist:

```bash
# Check if AgentProfile model exists
ls app/Models/AgentProfile.php

# Check if AgentProfileService exists
ls app/Services/AgentProfileService.php

# Check if routes exist (may need to create)
grep -r "agents" routes/api.php routes/internal.php
```

**Required Backend:**
- ✅ AgentProfile model (exists)
- ✅ AgentProfileService (exists)
- ❓ AgentController (may need creation)
- ❓ API routes (may need creation)

## Development Workflow

### Task UI-00: Terminology Migration

```bash
# Rename command classes
mv app/Commands/AgentListCommand.php app/Commands/AgentProfileListCommand.php
mv app/Commands/AgentDetailCommand.php app/Commands/AgentProfileDetailCommand.php

# Rename React components
mv resources/js/components/orchestration/AgentListModal.tsx \
   resources/js/components/orchestration/AgentProfileListModal.tsx

# Rename YAML directory
mv fragments/commands/agents fragments/commands/agent-profiles

# Update imports and references
# Edit: app/Services/CommandRegistry.php
# Edit: resources/js/islands/chat/CommandResultModal.tsx
# Edit: fragments/commands/agent-profiles/command.yaml
```

### Task UI-01: Dashboard Grid

```bash
# Create dashboard page
touch resources/js/pages/AgentProfileDashboard.tsx

# Update command routing
# Edit: app/Commands/AgentProfileListCommand.php
```

### Task UI-02: Mini-Card

```bash
# Create card component
mkdir -p resources/js/components/agents
touch resources/js/components/agents/AgentProfileMiniCard.tsx

# Import in dashboard
# Edit: resources/js/pages/AgentProfileDashboard.tsx
```

### Task UI-03: Profile Editor

```bash
# Create editor modal
touch resources/js/components/agents/AgentProfileEditor.tsx

# Create tag input utility
touch resources/js/components/ui/tag-input.tsx

# Wire up in dashboard
# Edit: resources/js/pages/AgentDashboard.tsx
```

### Task UI-04: API Integration

```bash
# Create API client
touch resources/js/lib/api/agent-profiles.ts

# Create hooks
touch resources/js/hooks/useAgentProfiles.ts

# Create backend controller (if needed)
php artisan make:controller AgentProfileController

# Add routes
# Edit: routes/api.php or routes/internal.php

# Wire everything up
# Edit: resources/js/pages/AgentProfileDashboard.tsx
```

## Testing Checklist

### After UI-00
- [ ] New commands work: `/agent-profiles`, `/ap`
- [ ] Old commands redirect: `/agents`, `/agent-list`
- [ ] Deprecation notices show for old commands
- [ ] Modal renamed to AgentProfileListModal
- [ ] No broken imports or TypeScript errors

### After UI-01
- [ ] Dashboard renders
- [ ] Grid is responsive
- [ ] Empty state shows
- [ ] Loading state shows
- [ ] Create button exists

### After UI-02
- [ ] Cards display in grid
- [ ] Status indicators show (random colors)
- [ ] Three-dot menu works
- [ ] Card hover effects work

### After UI-03
- [ ] Modal opens on card click
- [ ] Form fields render
- [ ] Type/mode/status dropdowns work
- [ ] Tag inputs work (add/remove)
- [ ] Validation shows errors
- [ ] Save/cancel buttons work

### After UI-04
- [ ] Create agent works
- [ ] Edit agent works
- [ ] Delete agent works (with confirmation)
- [ ] Duplicate agent works
- [ ] Success toasts appear
- [ ] Error handling works
- [ ] Optimistic updates work

## Common Issues & Solutions

### Issue: Backend routes not found
**Solution**: Add routes to `routes/api.php` or `routes/internal.php`

### Issue: Type/Mode/Status dropdowns empty
**Solution**: Ensure backend endpoints return data (`/api/agents/meta/*`)

### Issue: CORS errors
**Solution**: Check CORS configuration in `config/cors.php`

### Issue: Form validation not working
**Solution**: Check React Hook Form setup and validation rules

### Issue: Optimistic updates not rolling back
**Solution**: Check React Query mutation error handlers

## Terminology Migration (UI-00)

**Key Changes:**
- "Agents" → "Agent Profiles" throughout UI/commands
- Clarifies: Agent Profiles = configurations, Agents = runtime instances
- Maintains backward compatibility with deprecation notices

**File Renames:**
- `AgentListCommand` → `AgentProfileListCommand`
- `AgentListModal` → `AgentProfileListModal`
- `fragments/commands/agents/` → `fragments/commands/agent-profiles/`

**New Commands:**
- Primary: `/agent-profiles`, `/ap`
- Deprecated: `/agents`, `/agent-list` (still work, show warning)

## Next Steps After Completion

**Immediate Follow-ups:**
1. User testing and feedback
2. Fix any bugs discovered
3. Update documentation

**Future Sprints:**
- Sprint 65: Agent Avatar System
- Sprint 66: Agent Chat Integration  
- Sprint 67: Agent Status Logic
- Sprint 68: Agent Memory Visualization

## Reference Documentation

- Backend service: `app/Services/AgentProfileService.php`
- Agent model: `app/Models/AgentProfile.php`
- Existing modal: `resources/js/components/orchestration/AgentListModal.tsx`
- Original planning: `delegation/imported/sprint-43/UX-04-02-agent-manager-system/`

## Questions?

If you encounter issues or need clarification:
1. Check the individual TASK.md files for detailed implementation notes
2. Review existing components for patterns
3. Consult backend service for available methods
4. Check SPRINT.md for overall context
