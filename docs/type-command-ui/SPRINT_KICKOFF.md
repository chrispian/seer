# SPRINT-FE-UI-1 Kickoff

**Sprint:** SPRINT-FE-UI-1 - Config-Driven Component Routing  
**Status:** In Progress  
**Start Date:** October 11, 2025  
**End Date:** October 15, 2025  
**Estimated Time:** 8-9 hours  
**Tasks:** 19 total

---

## 🎯 Sprint Goal

Refactor CommandResultModal to use config-driven component routing, eliminating the 400+ line hardcoded switch statement and fully leveraging the backend's unified Type + Command system.

---

## 📊 Sprint Overview

### Quick Stats
- **Total Tasks:** 19
- **Estimated Hours:** ~9 hours
- **Risk Level:** Low-Medium
- **Breaking Changes:** Yes (config-only, no legacy support)

### Phase Breakdown
1. **Phase 1 - Foundation** (4 tasks, 1.5h) - Helper functions, component map
2. **Phase 2 - Switch Replacement** (3 tasks, 1h) - Remove hardcoded logic  
3. **Phase 3 - Components** (5 tasks, 4h) - Update all components
4. **Phase 4 - Testing** (4 tasks, 1.5h) - Comprehensive validation
5. **Phase 5 - Documentation** (3 tasks, 1h) - JSDoc + guides

---

## 📋 All Tasks (In Order)

### Phase 1: Foundation (1.5h)
1. **T-FE-UI-01-MAP** - Create Component Registry Map (15min)
2. **T-FE-UI-02-CFG** - Implement Config Resolution (30min)
3. **T-FE-UI-03-PROPS** - Implement Props Builder (30min)
4. **T-FE-UI-04-RENDER** - Implement Component Renderer (30min)

### Phase 2: Switch Replacement (1h)
5. **T-FE-UI-05-SWITCH** - Replace Switch Statement (30min) ⚠️ Breaking
6. **T-FE-UI-06-DETAIL** - Update Detail View Handling (20min)
7. **T-FE-UI-07-CLEANUP** - Code Cleanup and Verification (10min)

### Phase 3: Component Updates (4h)
8. **T-FE-UI-08-UTILS** - Create Shared Utilities (30min)
9. **T-FE-UI-09-PROTO** - Prototype with SprintListModal (45min) ⚠️ Critical
10. **T-FE-UI-10-COMPS** - Update All List Components (2h)
11. **T-FE-UI-11-DASH** - Rename and Update Dashboard Components (45min)
12. **T-FE-UI-12-MIGRATE** - Create and Run Migration Script (30min)

### Phase 4: Testing (1.5h)
13. **T-FE-UI-13-TEST** - Manual Testing All Commands (45min) ⚠️ Critical
14. **T-FE-UI-14-BUILD** - TypeScript and Build Validation (15min)
15. **T-FE-UI-15-BACKEND** - Backend Config Validation (15min)
16. **T-FE-UI-16-LOGS** - Console Log Review (10min)

### Phase 5: Documentation (1h)
17. **T-FE-UI-17-INLINE** - Update Inline Documentation (20min)
18. **T-FE-UI-18-GUIDE** - Create Component Development Guide (20min)
19. **T-FE-UI-19-README** - Update README with Decisions (20min)

---

## 🔑 Key Decisions (Approved)

### ✅ Config-Only (No Legacy)
- No `component` field fallback
- Backend must provide config
- Forces proper architecture

### ✅ Generic Data Prop
- All components use `data` prop
- No type-specific props (sprints, tasks, agents)
- Single consistent pattern

### ✅ Self-Contained Components
- Components handle their own wrappers
- `useFullScreenModal` hook for dashboards
- No special cases in routing

### ✅ Component Naming
- Pattern: `[Type][View][Container]`
- Dashboard renames: AgentDashboard → AgentListDashboard
- Consistent across codebase

### ✅ Phased Implementation
- Prototype with SprintListModal first
- Then update all 11 components at once
- Migration script to find legacy usages

---

## 📚 Documentation Resources

### Main Planning Docs
All in `docs/type-command-ui/`:

1. **05-FINAL_IMPLEMENTATION_PLAN.md** ⭐ **Start here**
   - Complete implementation guide
   - Code examples for every task
   - Acceptance criteria
   - Testing checklists

2. **00-EXECUTIVE_SUMMARY.md**
   - Quick overview
   - Problem/solution summary
   - Key benefits

3. **01-SYSTEM_ANALYSIS.md**
   - Deep dive into current state
   - Component inventory
   - Problems identified

4. **02-PROPOSED_SOLUTION.md**
   - Architecture design
   - Flow diagrams
   - Config priority system

5. **ARCHITECTURE_DIAGRAM.md**
   - Visual guides
   - Before/after comparisons
   - Flow diagrams

6. **README.md**
   - Navigation guide
   - Decisions log
   - Quick reference

### Orchestration Guide
- `docs/FRONTEND_AGENT_ORCHESTRATION_GUIDE.md`
  - How to update task status
  - How to add context
  - Command examples

---

## 🚀 Getting Started

### For Implementation Agent

**1. Read the docs** (30 minutes)
- Start with `05-FINAL_IMPLEMENTATION_PLAN.md`
- Review approved decisions
- Understand phase breakdown

**2. Set up environment**
```bash
cd /Users/chrispian/Projects/seer
composer run dev  # Start local dev
```

**3. View your tasks**
```bash
php artisan orchestration:sprint:detail SPRINT-FE-UI-1
php artisan orchestration:tasks --sprint=SPRINT-FE-UI-1 --status=todo
```

**4. Start Phase 1, Task 1**
```bash
# Mark task in progress
php artisan orchestration:task:status T-FE-UI-01-MAP in_progress \
  --note="Starting component registry map creation"

# Do the work (see task details in orchestration system)

# Update with context as you work
php artisan orchestration:task:save T-FE-UI-01-MAP \
  --agent-content="Created COMPONENT_MAP with 20+ components. All imports verified."

# Mark complete
php artisan orchestration:task:status T-FE-UI-01-MAP completed \
  --note="Component map complete with JSDoc, ready for config resolution"
```

**5. Continue through phases**
- Follow dependencies (each task lists what it depends on)
- Test after critical tasks
- Update task context frequently

---

## ⚠️ Critical Checkpoints

### After Task 4 (Phase 1 Complete)
✅ Verify: All helper functions created, TypeScript compiles, no errors

### After Task 7 (Phase 2 Complete)  
⚠️ **MAJOR MILESTONE** - Switch statement removed
✅ Test: Run `/sprints` and `/tasks` commands, verify they work
✅ Verify: Detail views work, back button works

### After Task 9 (Prototype Complete)
⚠️ **CRITICAL** - Prototype must work before updating all components
✅ Test: `/sprints` command thoroughly
✅ Verify: Data displays, detail view works, config used

### After Task 10 (All Components Updated)
⚠️ **BULK CHANGE** - All 9 components updated at once
✅ Test: All 12+ commands
✅ Verify: No regressions

### After Task 13 (Testing Complete)
✅ All commands tested and documented
✅ Ready for deployment

---

## 🧪 Testing Strategy

### Manual Testing Checklist
After Phase 2 and 3, test these commands:

**List Commands:**
- [ ] `/sprints` - Displays table, click → detail
- [ ] `/tasks` - Displays table, click → detail
- [ ] `/agents` - Displays grid
- [ ] `/backlog` - Displays table
- [ ] `/projects` - Displays table
- [ ] `/vaults` - Displays table
- [ ] `/bookmarks` - Displays table
- [ ] `/fragments` - Displays table
- [ ] `/channels` - Displays table

**Special Commands:**
- [ ] `/todos` - Management modal
- [ ] `/types` - Management modal
- [ ] `/routing-info` - Info modal

**Detail Views:**
- [ ] Sprint → detail → back
- [ ] Task → detail → back

**Console Checks:**
- [ ] Config-driven logs visible
- [ ] Component resolution logged
- [ ] No errors

### Build Validation
```bash
npm run build  # Must succeed with 0 errors
```

---

## 📝 Task Workflow

### Standard Task Flow
1. **Mark in progress**
   ```bash
   php artisan orchestration:task:status T-FE-UI-XX-YYY in_progress
   ```

2. **Do the work**
   - Read task description in orchestration system
   - Reference implementation plan (Task X.Y in 05-FINAL_IMPLEMENTATION_PLAN.md)
   - Write code
   - Test locally

3. **Update context**
   ```bash
   php artisan orchestration:task:save T-FE-UI-XX-YYY \
     --agent-content="Implemented X. Did Y. Tested Z. Results: ..."
   ```

4. **Mark complete**
   ```bash
   php artisan orchestration:task:status T-FE-UI-XX-YYY completed \
     --note="Task complete, tests passing, ready for next task"
   ```

5. **Move to next task** (check dependencies)

---

## 🎓 Learning Resources

### New to the Codebase?

**Backend Context:**
- Backend Type + Command unification is **complete**
- Backend sends config with every command response
- Config structure documented in task context

**Frontend Context:**
- React + TypeScript + Shadcn components
- Vite for bundling
- Running under Laravel Valet locally

**Current Problem:**
- CommandResultModal has 400+ line switch statement (lines 136-423)
- Hardcoded component routing
- Doesn't use backend config
- Adding new command requires code changes

**What We're Building:**
- Config-driven routing (backend controls UI)
- Generic component props (data, config)
- Self-contained components (handle own wrappers)
- Zero code changes to add commands

---

## 🐛 Troubleshooting

### TypeScript Errors
```bash
# Check compilation
npm run build

# If errors, fix types first
# All types should be in resources/js/types/modal.ts
```

### Component Not Rendering
1. Check console logs (should show component resolution)
2. Verify component in COMPONENT_MAP
3. Check backend config (browser console: fetch /api/commands/execute)
4. Verify props are correct (check buildComponentProps logs)

### Test Failures
1. Clear browser cache
2. Check console for errors
3. Verify backend seeder has config
4. Check component props match interface

### Build Fails
1. Run `npm install` (dependencies might be missing)
2. Check TypeScript errors
3. Verify all imports resolve
4. Check for circular dependencies

---

## 📞 Getting Help

### Questions About Implementation
- Read `05-FINAL_IMPLEMENTATION_PLAN.md` - most answers are there
- Check task `agent_content` field - has context for each task
- Review architecture diagrams in `ARCHITECTURE_DIAGRAM.md`

### Stuck on a Task
- Review acceptance criteria
- Check dependencies (is previous task complete?)
- Test incrementally (don't save all changes at once)
- Add console logs for debugging

### Need Clarification
- Document questions in task context
- Continue with what you know
- Mark task as blocked if truly stuck

---

## ✅ Definition of Done (Sprint Level)

### Code
- [x] Switch statement removed
- [x] Config-only resolution
- [x] Generic `data` prop on all components
- [x] Self-contained components
- [x] Dashboard components renamed
- [x] TypeScript compilation succeeds

### Testing
- [x] All 12+ commands tested manually
- [x] Detail views work (drill-down + back)
- [x] TypeScript builds with 0 errors
- [x] No console errors
- [x] Config validation passes

### Documentation
- [x] JSDoc comments on helpers
- [x] Component development guide
- [x] README updated
- [x] Task context documented

---

## 🎉 Sprint Success Metrics

### Quantitative
- Lines of code: 590 → 350 (40% reduction)
- Switch cases: 20+ → 0
- Components updated: 11
- Commands tested: 12+

### Qualitative
- Clear, consistent patterns
- Agent-friendly architecture
- Zero technical debt
- Config-driven flexibility
- Self-documenting code

---

## 📅 Timeline

**Day 1 (Oct 11):**
- Phase 1: Foundation (1.5h)
- Phase 2: Switch Replacement (1h)
- Total: 2.5h

**Day 2 (Oct 12-13):**
- Phase 3: Component Updates (4h)
- Prototype + all components
- Total: 4h

**Day 3 (Oct 14):**
- Phase 4: Testing (1.5h)
- Phase 5: Documentation (1h)
- Total: 2.5h

**Total: 9 hours over 3 days**

---

## 🚦 Sprint Readiness

✅ Planning complete  
✅ Tasks created (19)  
✅ Dependencies defined  
✅ Context provided  
✅ Documentation comprehensive  
✅ Decisions approved  
✅ Sprint status: **In Progress**

**Ready to begin implementation!**

---

**View sprint details:**
```bash
php artisan orchestration:sprint:detail SPRINT-FE-UI-1
```

**View all tasks:**
```bash
php artisan orchestration:tasks --sprint=SPRINT-FE-UI-1
```

**Start first task:**
```bash
php artisan orchestration:task:status T-FE-UI-01-MAP in_progress
```

---

🚀 **Let's build something great!**
