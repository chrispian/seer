# UI Builder v2 - Sprint 2 Kickoff

**Date**: 2025-10-15  
**Status**: Ready for Execution  
**Branch Strategy**: Create feature branches per task, merge to `develop`, then to `main`

---

## 📋 What We Just Created

### Sprint Plan
**File**: `/Users/chrispian/Projects/seer/delegation/sprints/SPRINT-UIB-V2-02.md`

### Task Files (5 Total)
All located in `/Users/chrispian/Projects/seer/delegation/tasks/`:

1. **T-UIB-SPRINT2-01-TYPES.md** - FE Types System
2. **T-UIB-SPRINT2-02-REGISTRY.md** - FE UI Registry + Feature Flags
3. **T-UIB-SPRINT2-03-SCHEMA.md** - New Database Schema
4. **T-UIB-SPRINT2-04-COMPONENTS.md** - 60 Shadcn-Parity Components
5. **T-UIB-SPRINT2-05-DATASOURCES.md** - Config-Based Data Sources

---

## 🚀 Execution Plan

### Round 1: Foundation Systems (Start Now, Parallel)

**Task 1** and **Task 2** can run in parallel - no dependencies.

```bash
# Create branches
git checkout -b feature/types-system
git checkout -b feature/registry-flags
```

**BE-Kernel Agent 1**: Execute T-UIB-SPRINT2-01-TYPES
- Migrate files from `/delegation/tasks/ui-builder/fe_types_min_pack_20251015_152612`
- Run migrations, test endpoints
- Estimated: 2-3 hours

**BE-Kernel Agent 2**: Execute T-UIB-SPRINT2-02-REGISTRY
- Migrate files from `/delegation/tasks/ui-builder/fe_ui_registry_flags_pack_20251015_152026`
- Run migrations, test feature flags
- Estimated: 2-3 hours

### Round 2: Schema Expansion (After Round 1)

```bash
git checkout -b feature/schema-modules-themes
```

**BE-Kernel Agent**: Execute T-UIB-SPRINT2-03-SCHEMA
- Create `fe_ui_modules` and `fe_ui_themes` tables
- Alter existing tables (pages, components, datasources, actions)
- Create models and seeders
- Estimated: 3-4 hours

### Round 3: Parallel Development (After Round 2)

```bash
git checkout -b feature/components-phase1
git checkout -b feature/datasources-generic
```

**FE-Core Agent**: Execute T-UIB-SPRINT2-04-COMPONENTS (Phase 1 - Primitives)
- Build Tier 1A: Button, Input, Label, Badge, Avatar, Skeleton (critical)
- Estimated: 4-5 hours

**BE-Kernel Agent**: Execute T-UIB-SPRINT2-05-DATASOURCES
- Create GenericDataSourceResolver
- Migrate Agent and Model to config
- Estimated: 4-6 hours

### Round 4: Component Library Completion (After Primitives Done)

```bash
# Multiple branches for parallel work
git checkout -b feature/components-tier1b
git checkout -b feature/components-tier1c
git checkout -b feature/components-tier2a
git checkout -b feature/components-tier2b
```

**FE Agent 1**: Tier 1B (Form Elements)  
**FE Agent 2**: Tier 1C (Feedback)  
**FE Agent 3**: Tier 2A (Structural)  
**FE Agent 4**: Tier 2B (Navigation)

Estimated: 6-8 hours parallel (2-3 hours each)

### Round 5: Composite Components (After Layouts Done)

```bash
git checkout -b feature/components-tier3a
git checkout -b feature/components-tier3b
git checkout -b feature/components-tier3c
```

**FE Agent 1**: Tier 3A (Interactive Patterns)  
**FE Agent 2**: Tier 3B (Complex Forms)  
**FE Agent 3**: Tier 3C (Advanced)

Estimated: 12-16 hours parallel (4-5 hours each)

---

## 📊 Timeline

| Round | Tasks | Parallel? | Time Estimate |
|-------|-------|-----------|---------------|
| 1 | Types + Registry | Yes | 2-3 hours |
| 2 | Schema | No | 3-4 hours |
| 3 | Components Phase 1 + Datasources | Yes | 4-6 hours |
| 4 | Components Phase 2 | Yes (4 agents) | 2-3 hours |
| 5 | Components Phase 3 | Yes (3 agents) | 4-5 hours |

**Total Real-Time**: 18-24 hours  
**Total Sequential**: 50-66 hours  
**Efficiency Gain**: ~60% time savings

---

## ✅ Success Metrics

### After Round 1
- [ ] Types API works: `GET /api/v2/ui/types/Invoice/query`
- [ ] Registry table populated with sample data
- [ ] Feature flags evaluate correctly in code
- [ ] `fe_ui_components.kind` column exists

### After Round 2
- [ ] `fe_ui_modules` and `fe_ui_themes` tables exist
- [ ] Agent page links to module
- [ ] Theme tokens can be queried
- [ ] All new fields present on tables

### After Round 3
- [ ] 10 primitive components registered and working
- [ ] Generic datasource resolver works for Agent
- [ ] Agent modal still functions (no regression)
- [ ] Old resolver classes removed

### After Round 4
- [ ] 26+ components registered (all primitives)
- [ ] Form elements working
- [ ] Feedback components working
- [ ] Layout components working

### After Round 5
- [ ] All 60 components registered
- [ ] Component showcase page works
- [ ] Documentation complete
- [ ] Build has no errors

---

## 🔧 Commands to Run

### Setup (After Each Round)

```bash
# Run new migrations
php artisan migrate

# Seed new data
php artisan db:seed --class=TypesDemoSeeder
php artisan db:seed --class=UiRegistrySeeder
php artisan db:seed --class=ModulesThemesSeeder

# Clear caches
php artisan optimize:clear

# Build frontend
npm run build
```

### Testing Endpoints

```bash
# Test types
curl http://localhost/api/v2/ui/types/Invoice/query

# Test datasource (generic)
curl http://localhost/api/v2/ui/datasource/Agent/query

# Test feature flag (in tinker)
php artisan tinker
> app(\App\Services\V2\FeatureFlagService::class)->isEnabled('ui.modal_v2');

# Test UI
# Visit: http://localhost/v2/pages/page.agent.table.modal
# Visit: http://localhost/v2/pages/component.showcase (after components done)
```

---

## 📁 File Organization

### Context Packs (Reference)
- `/delegation/tasks/ui-builder/fe_types_min_pack_20251015_152612/`
- `/delegation/tasks/ui-builder/fe_ui_registry_flags_pack_20251015_152026/`

### Task Files (Instructions)
- `/delegation/tasks/T-UIB-SPRINT2-01-TYPES.md`
- `/delegation/tasks/T-UIB-SPRINT2-02-REGISTRY.md`
- `/delegation/tasks/T-UIB-SPRINT2-03-SCHEMA.md`
- `/delegation/tasks/T-UIB-SPRINT2-04-COMPONENTS.md`
- `/delegation/tasks/T-UIB-SPRINT2-05-DATASOURCES.md`

### Sprint Plan
- `/delegation/sprints/SPRINT-UIB-V2-02.md`

### Documentation Output
- `/delegation/tasks/ui-builder/docs/FE_TYPES_SYSTEM.md`
- `/delegation/tasks/ui-builder/docs/FE_UI_REGISTRY.md`
- `/delegation/tasks/ui-builder/docs/FEATURE_FLAGS.md`
- `/delegation/tasks/ui-builder/docs/MODULES_THEMES.md`
- `/delegation/tasks/ui-builder/components/` (60 component docs)
- `/delegation/tasks/ui-builder/docs/GENERIC_DATASOURCES.md`

---

## 🎯 Priority Order (If Time Constrained)

### Must Have (Core Systems)
1. ✅ Types System (Task 1)
2. ✅ Registry + Flags (Task 2)
3. ✅ Schema Expansion (Task 3)
4. ✅ Generic Datasources (Task 5)

### Should Have (Foundation Components)
5. Components Tier 1A (10 primitives)
6. Components Tier 1B (Form elements)

### Nice to Have (Full Component Library)
7. Components Tier 1C (Feedback)
8. Components Tier 2 (Layouts)
9. Components Tier 3 (Composites)

---

## 🚨 Known Risks

1. **Context Pack Namespaces** - May need adjustment during migration
   - Mitigation: Carefully review each file, update `use` statements

2. **Migration Conflicts** - Timestamps may overlap
   - Mitigation: Ensure proper sequence, test rollback

3. **Component Complexity** - 60 components is a lot
   - Mitigation: Phased approach, can ship with partial set

4. **Breaking Changes** - New schema may break existing pages
   - Mitigation: Test Agent modal after each change

5. **Database Seeder Order** - Must run in correct sequence
   - Mitigation: Document dependencies, test clean install

---

## 📝 Notes for Tomorrow

- All task files are detailed and ready for agent execution
- Context packs contain all necessary code (just needs migration)
- Sprint plan provides high-level overview
- This file (SPRINT2_KICKOFF.md) is your guide

**Start with**: T-UIB-SPRINT2-01-TYPES and T-UIB-SPRINT2-02-REGISTRY in parallel

---

**Status**: 🟢 Ready to Execute  
**Next Action**: Delegate Task 1 and Task 2 to BE-Kernel agents  
**Expected Completion**: 2-3 days with proper delegation
