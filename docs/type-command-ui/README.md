# Type + Command UI Refactor - Planning Documentation

**Project:** Config-Driven Component Routing  
**Status:** Planning Phase  
**Last Updated:** October 10, 2025

---

## Overview

This directory contains comprehensive planning documentation for refactoring the command result UI system to be fully config-driven, eliminating hardcoded switch statements and leveraging the backend's unified Type + Command architecture.

---

## Documentation Structure

### 01-SYSTEM_ANALYSIS.md ⭐ Start Here
**Purpose:** Understand the current state  
**Contents:**
- Backend architecture overview
- Frontend implementation status
- Component inventory
- Problems identified
- Key insights and opportunities

**Read this first** to understand what we're working with and why we need changes.

---

### 02-PROPOSED_SOLUTION.md
**Purpose:** Detailed technical design  
**Contents:**
- Architecture design
- Component resolution flow
- Config priority system
- Props standardization
- Implementation phases
- Testing strategy
- Success criteria

**Use this** as the technical blueprint for implementation.

---

### 03-TASK_BREAKDOWN.md
**Purpose:** Actionable work items  
**Contents:**
- 18 discrete tasks across 5 phases
- Time estimates (6-9 hours total)
- Acceptance criteria per task
- File changes per task
- Risk mitigation strategies
- Definition of done

**Use this** to create orchestration sprint/tasks and track progress.

---

### 04-RECOMMENDATIONS.md
**Purpose:** Strategic improvements  
**Contents:**
- Immediate improvements (must-have)
- Medium-term improvements (should-have)
- Long-term improvements (nice-to-have)
- Architecture patterns
- Developer experience enhancements
- Monitoring strategies

**Use this** for future planning and continuous improvement.

---

## Quick Links

### Current Implementation
- `resources/js/islands/chat/CommandResultModal.tsx` - Main file to refactor (590 lines, 400+ switch statement)
- `resources/js/components/unified/UnifiedListModal.tsx` - Config-aware fallback component
- `resources/js/components/orchestration/SprintListModal.tsx` - Example type-specific modal

### Backend References
- `docs/TYPE_COMMAND_UNIFICATION_COMPLETE.md` - Backend unification summary (complete ✅)
- `docs/FRONTEND_CONFIG_DRIVEN_ROUTING_TASK.md` - Original task specification
- `database/seeders/TypesSeeder.php` - Type definitions
- `database/seeders/CommandsSeeder.php` - Command definitions with UI config

---

## Key Problems Being Solved

### 1. Maintainability 🔴
- 400+ lines of hardcoded switch cases
- Adding new command requires frontend code changes
- Easy to introduce bugs

### 2. Inflexibility 🟡
- Backend config not used by frontend
- No runtime component selection
- Hardcoded prop names

### 3. Inconsistency 🟡
- Different prop patterns across components
- No standardized interfaces
- Confusing for developers and agents

---

## Proposed Solution Summary

### Replace This:
```typescript
switch (currentResult.component) {
  case 'SprintListModal': 
    return <SprintListModal sprints={data} ... />
  case 'TaskListModal':
    return <TaskListModal tasks={data} ... />
  // ... 20+ more cases
}
```

### With This:
```typescript
// 1. Component registry
const COMPONENT_MAP = {
  'SprintListModal': SprintListModal,
  'TaskListModal': TaskListModal,
  // ...
}

// 2. Config-driven resolution
const componentName = getComponentName(result)  // Uses config priority
const Component = COMPONENT_MAP[componentName] || UnifiedListModal
const props = buildComponentProps(result, componentName, handlers)

// 3. Render
return <Component {...props} />
```

**Result:** 400+ lines → ~200 lines, fully config-driven, no code changes for new commands.

---

## Implementation Phases

### Phase 1: Foundation (2h)
Create helper functions, component map, no breaking changes.

### Phase 2: Switch Replacement (1h)
Remove hardcoded switch, use new system.

### Phase 3: Child Components (3h)
Update 3+ components to use config for rendering decisions.

### Phase 4: Testing (2h)
Manual testing, TypeScript validation, browser console verification.

### Phase 5: Documentation (1h)
JSDoc comments, guides for adding commands/components.

**Total:** 9 hours (conservative), 6-7 hours (optimistic)

---

## Success Criteria

### Code Quality ✅
- [ ] Lines of code reduced by 50%+
- [ ] TypeScript strict mode passes
- [ ] No console errors
- [ ] Helper functions well-documented

### Functionality ✅
- [ ] All 12+ commands work (no regressions)
- [ ] Detail views work with back button
- [ ] Fallback system works
- [ ] Config priority system implemented

### Developer Experience ✅
- [ ] Adding new command = 0 frontend changes
- [ ] Clear, documented resolution logic
- [ ] Helpful console logs
- [ ] Agents understand the system

---

## Getting Started

### For Implementers
1. Read `01-SYSTEM_ANALYSIS.md` (15 min)
2. Read `02-PROPOSED_SOLUTION.md` (30 min)
3. Review `03-TASK_BREAKDOWN.md` (15 min)
4. Set up dev environment: `composer run dev`
5. Start with Phase 1, Task 1.1

### For Reviewers
1. Read `01-SYSTEM_ANALYSIS.md` (context)
2. Read `02-PROPOSED_SOLUTION.md` (design)
3. Review concerns in "Open Questions" sections
4. Provide feedback on approach

### For Project Managers
1. Read this README
2. Scan `03-TASK_BREAKDOWN.md` for time estimates
3. Review "Risk Mitigation" section
4. Create sprint/tasks in orchestration system

---

## Questions & Decisions

### Open Questions

**Q1: Should we standardize all component props to `data` vs type-specific names?**
- Recommendation: Support both, prefer generic `data`
- Decision: TBD

**Q2: Should we use UnifiedListModal as the primary component for simple lists?**
- Recommendation: Yes for simple lists, keep specialized for complex UIs
- Decision: TBD

**Q3: Config priority: `component` field first or `config` first?**
- Recommendation: Config first, component as legacy fallback
- Decision: TBD

**Q4: Should dashboard components handle their own Dialog wrapper?**
- Recommendation: Keep wrapping in CommandResultModal for now
- Decision: TBD

### Decisions Log

**Decision 1:** Use helper functions over class-based approach
- **Rationale:** Simpler, more functional, easier to test
- **Date:** Oct 10, 2025

**Decision 2:** Keep backward compatibility with legacy props
- **Rationale:** Gradual migration, no breaking changes
- **Date:** Oct 10, 2025

**Decision 3:** Fallback to UnifiedListModal for unknown components
- **Rationale:** Better UX than error, helps with development
- **Date:** Oct 10, 2025

---

## Related Documentation

### Project Context
- `docs/CLAUDE.md` - Repository guidelines
- `docs/README.md` - Project documentation index
- `docs/frontend/FRONTEND_COMPONENTIZATION_PLAN.md` - Frontend architecture

### Backend Context
- `docs/TYPE_COMMAND_UNIFICATION_COMPLETE.md` - Backend work (complete)
- `docs/UNIFIED_ARCHITECTURE.md` - Overall system architecture

### Command System
- `docs/command systems/COMMAND_DEVELOPMENT_GUIDE.md` - Creating new commands
- `docs/command systems/COMMAND_QUICK_REFERENCE.md` - Command reference

---

## Contributing

### Adding Documentation
1. Follow existing numbering scheme (05-YOUR_DOC.md)
2. Add link to this README
3. Keep documents focused and actionable

### Updating Documentation
1. Update "Last Updated" date in this README
2. Add entry to "Change Log" below
3. Notify relevant stakeholders

---

## Change Log

### 2025-10-10
- **Created:** Initial planning documentation
- **Files:** 01-04 analysis, solution, tasks, recommendations
- **Next:** User review and approval

---

## Contact & Support

### Questions During Implementation
- Check `02-PROPOSED_SOLUTION.md` for technical details
- Check `03-TASK_BREAKDOWN.md` for acceptance criteria
- Review `04-RECOMMENDATIONS.md` for best practices

### Blocked or Stuck
- Review "Risk Mitigation" in `03-TASK_BREAKDOWN.md`
- Check console logs for helpful debugging info
- Rollback plan: Revert CommandResultModal.tsx to previous commit

---

## Decisions Made (October 10, 2025)

### ✅ Config-Only Architecture
- **No legacy support** - No `component` field fallback
- **Backend must provide config** - Forces proper architecture
- **Eliminates technical debt** - Clean from day one

### ✅ Generic Data Prop
- **All components use `data` prop** - No type-specific props
- **No sprints/tasks/agents props** - Single consistent pattern
- **Better TypeScript** - `data: Sprint[]` for type safety

### ✅ Self-Contained Components
- **Components handle their own wrappers** - No special cases in routing
- **useFullScreenModal hook** - Shared utility for dashboards
- **Fully modular** - Components are portable

### ✅ Component Naming Convention
- **Standardized pattern** - `[Type][View][Container]`
- **Dashboard components renamed** - AgentDashboard → AgentListDashboard
- **Consistent across codebase** - Easy to understand

### ✅ Phased Implementation
- **Prototype first** - Get SprintListModal working
- **Then update all at once** - Atomic change, no partial state
- **Migration script** - Find and fix legacy usages

---

## Status & Next Actions

### Current Status
✅ **Planning Complete & Approved**
- [x] System analyzed
- [x] Solution designed
- [x] Tasks broken down
- [x] Recommendations documented
- [x] Decisions finalized
- [x] Final implementation plan created

### Next Actions
1. ⏳ **Wait for orchestration system** - User updating orchestration docs
2. ⏳ **Create sprint/tasks** - Once orchestration ready
3. ⏳ **Begin Phase 1** - Foundation (1.5h)
4. ⏳ **Phase 2** - Switch replacement (1h)
5. ⏳ **Phase 3** - Prototype + all components (4h)
6. ⏳ **Phase 4** - Testing (1.5h)
7. ⏳ **Phase 5** - Documentation (1h)
8. ⏳ **Deploy** - Production release

### Ready to Begin
All planning complete, waiting for orchestration system to create sprint/tasks.

---

## Appendix

### Glossary

**Config Object** - Backend-provided JSON with type, ui, and command metadata  
**Component Map** - Registry of component name → React component  
**Component Resolution** - Process of determining which component to render  
**Config Priority** - Order of checking config fields (ui.modal_container > ui.card_component > type.default_card_component > fallback)  
**Legacy Field** - The `component` field, used for backward compatibility  
**UnifiedListModal** - Generic fallback component that works with any data type  
**BaseModalProps** - Standardized prop interface for all modal components  

### Conventions

**Component Naming:** `[Type][View][Container]` - e.g., SprintListModal  
**File Naming:** `01-TITLE.md` - Numbered for reading order  
**Config Priority:** Backend > Type > Fallback  
**Prop Pattern:** Generic `data` preferred, type-specific for legacy  

---

**End of README** - Scroll up for navigation or start with `01-SYSTEM_ANALYSIS.md`
