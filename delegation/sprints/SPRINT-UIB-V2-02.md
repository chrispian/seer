# Sprint: UI Builder v2 - Sprint 2

**Sprint Code**: SPRINT-UIB-V2-02  
**Start Date**: 2025-10-15  
**Status**: PLANNING  
**Goal**: Expand UI Builder v2 with type system, registry, enhanced schema, config-driven components, and generic data sources

---

## Sprint Overview

Building on the successful MVP (Agents Modal PoC), Sprint 2 focuses on:

1. **Foundation Systems** - Types and Registry for better organization
2. **Enhanced Schema** - Modules, Themes, and expanded table capabilities
3. **Component Library** - 60 Shadcn-parity components (config-driven)
4. **Generic Data Sources** - Eliminate hard-coded resolver classes

---

## Sprint Tasks

### Phase 1: Foundation (Parallel, HIGH Priority)

**T-UIB-SPRINT2-01-TYPES** - Implement FE Types System  
- Migrate context pack: `fe_types_min_pack_20251015_152612`
- Tables: `fe_types`, `fe_type_fields`, `fe_type_relations`
- Models, Services, DTOs, Controller
- Provides config-first, strongly-typed schemas
- **Assigned To**: BE-Kernel Agent
- **Estimated**: 2-3 hours
- **Status**: TODO

**T-UIB-SPRINT2-02-REGISTRY** - Implement FE UI Registry + Feature Flags  
- Migrate context pack: `fe_ui_registry_flags_pack_20251015_152026`
- Tables: `fe_ui_registry`, `fe_ui_feature_flags`
- Alter: `fe_ui_components` add `kind` enum
- Feature flag service with % rollouts
- **Assigned To**: BE-Kernel Agent
- **Estimated**: 2-3 hours
- **Status**: TODO
- **Can Run in Parallel**: with T-UIB-SPRINT2-01

---

### Phase 2: Schema Expansion (HIGH Priority)

**T-UIB-SPRINT2-03-SCHEMA** - Create New Database Schema  
- New tables: `fe_ui_modules`, `fe_ui_themes`
- Update tables: Add fields to pages, components, datasources, actions
- Models for modules/themes
- Seeder with sample data
- **Assigned To**: BE-Kernel Agent
- **Estimated**: 3-4 hours
- **Status**: TODO
- **Depends On**: T-UIB-SPRINT2-02 (for `kind` field)

---

### Phase 3: Component Library (MEDIUM Priority)

**T-UIB-SPRINT2-04-COMPONENTS** - Create Shadcn-Parity Components  
- 60 components organized in 3 tiers
- **Tier 1**: Primitives (Button, Input, Badge, etc.) - 26 components
- **Tier 2**: Layouts (Card, Tabs, Sidebar, etc.) - 10 components
- **Tier 3**: Composites (Form, DataTable, Dialog, etc.) - 24 components
- All config-driven with registry entries
- Documentation for each component
- **Assigned To**: FE-Core Agent (with sub-delegation)
- **Estimated**: 36-46 hours (12-15 hours with parallel agents)
- **Status**: TODO
- **Depends On**: T-UIB-SPRINT2-03 (for component schema fields)

---

### Phase 4: Generic Data Sources (MEDIUM Priority)

**T-UIB-SPRINT2-05-DATASOURCES** - Make DataSource Resolvers Config-Based  
- Create `GenericDataSourceResolver`
- Migrate Agent and Model datasources to config
- Remove hard-coded resolver classes
- Create `fe:make:datasource` artisan command
- **Assigned To**: BE-Kernel Agent
- **Estimated**: 4-6 hours
- **Status**: TODO
- **Depends On**: T-UIB-SPRINT2-01, T-UIB-SPRINT2-03

---

## Parallel Execution Strategy

### Round 1 (Start Immediately)
- T-UIB-SPRINT2-01-TYPES (BE-Kernel Agent)
- T-UIB-SPRINT2-02-REGISTRY (BE-Kernel Agent, separate agent instance)

### Round 2 (After Round 1)
- T-UIB-SPRINT2-03-SCHEMA (BE-Kernel Agent)

### Round 3 (After Round 2)
- T-UIB-SPRINT2-04-COMPONENTS Phase 1 (FE-Core Agent)
- T-UIB-SPRINT2-05-DATASOURCES (BE-Kernel Agent)

### Round 4 (After Phase 1 Primitives Done)
- T-UIB-SPRINT2-04-COMPONENTS Phase 2 (3-4 FE agents in parallel)

### Round 5 (After Phase 2 Layouts Done)
- T-UIB-SPRINT2-04-COMPONENTS Phase 3 (3-4 FE agents in parallel)

---

## Success Criteria

- [ ] Types system integrated and working
- [ ] Registry and feature flags operational
- [ ] Modules and Themes tables created
- [ ] All existing tables updated with new fields
- [ ] 60 components implemented and registered
- [ ] Generic data source resolver replaces all hard-coded resolvers
- [ ] Agent and Model datasources work via config
- [ ] Existing v2 pages still work (no regressions)
- [ ] Documentation complete for all new systems
- [ ] Migrations run cleanly
- [ ] Build succeeds with no errors

---

## Risk & Mitigation

**Risk**: Component library is large (60 components)  
**Mitigation**: Phased approach, build primitives first, then parallelize

**Risk**: Breaking existing v2 functionality  
**Mitigation**: Test Agent modal after each task, keep old resolvers until verified

**Risk**: Database schema changes conflict  
**Mitigation**: Careful migration ordering, test rollback scenarios

**Risk**: Context pack files may need adjustment  
**Mitigation**: Review each file during migration, update namespaces

---

## Deliverables

### Code
- [ ] 3 new tables (modules, themes, types tables)
- [ ] 6 table alterations (pages, components, datasources, actions)
- [ ] 10+ new models
- [ ] 5+ new services
- [ ] 60 React components
- [ ] 1 generic data source resolver
- [ ] 2+ artisan commands
- [ ] 5+ seeders

### Documentation
- [ ] Types system guide
- [ ] Registry system guide
- [ ] Feature flags guide
- [ ] Module creation guide
- [ ] Theme creation guide
- [ ] Component documentation (60 files)
- [ ] Data source configuration guide
- [ ] Migration guide for existing projects

### Testing
- [ ] All migrations tested
- [ ] All components smoke tested
- [ ] Agent modal still works
- [ ] Generic resolver matches old output
- [ ] Feature flags work correctly

---

## Task Files Created

1. `/Users/chrispian/Projects/seer/delegation/tasks/T-UIB-SPRINT2-01-TYPES.md`
2. `/Users/chrispian/Projects/seer/delegation/tasks/T-UIB-SPRINT2-02-REGISTRY.md`
3. `/Users/chrispian/Projects/seer/delegation/tasks/T-UIB-SPRINT2-03-SCHEMA.md`
4. `/Users/chrispian/Projects/seer/delegation/tasks/T-UIB-SPRINT2-04-COMPONENTS.md`
5. `/Users/chrispian/Projects/seer/delegation/tasks/T-UIB-SPRINT2-05-DATASOURCES.md`

---

## Timeline Estimate

**With Sequential Execution**: 50-66 hours  
**With Parallel Execution**: 18-24 hours (real-time)  
**Target Completion**: 2-3 days with proper delegation

---

## Notes

- Types and Registry can start immediately (no dependencies)
- Components task is the longest; maximize parallelization
- Generic datasource is critical for long-term maintainability
- Test frequently to catch issues early
- Keep old code until new systems proven

---

**Status**: Ready for delegation  
**Next Action**: Start T-UIB-SPRINT2-01 and T-UIB-SPRINT2-02 in parallel
