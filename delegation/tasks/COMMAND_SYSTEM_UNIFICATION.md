# Task: Command System Unification

**Task ID**: T-CMD-UNIFY-001  
**Created**: 2025-10-09  
**Priority**: HIGH  
**Estimated Effort**: 9-15 days (depending on scope)  
**Status**: Planning - Awaiting Strategic Decisions

---

## Problem Statement

Fragments Engine has two separate command systems causing confusion, regressions, and maintenance burden:

1. **User Commands** - `/slash` commands in web UI (TipTap composer)
2. **Agent Commands** - `artisan orchestration:*` CLI commands for AI agents/MCP

Both systems have overlapping functionality (sprints, tasks, agents) but exist in different namespaces with different invocation patterns. This creates:

- Agent confusion leading to regressions
- Duplicate maintenance burden
- Limited MCP exposure (only 3 tools vs 14 commands)
- Inconsistent capabilities (user commands simplified, agent commands have filters)
- Legacy YAML files (52 directories) causing confusion

---

## Objective

Unify command systems into single source of truth that serves both users and agents effectively, with:

- One command implementation per functionality
- Clear MCP exposure for agents
- Clean namespace organization
- Comprehensive documentation
- Zero confusion about which system to use

---

## Strategic Questions (Need Answers)

### 1. Namespace Strategy

Which structure do you prefer?

- [ ] **Option A**: Subnamespaces (`App\Commands\Orchestration\Sprint\ListCommand`)
- [ ] **Option B**: Flat with prefixes (`App\Commands\OrchestrationSprintListCommand`)
- [ ] **Option C**: Domain-driven (`App\Orchestration\Commands\SprintListCommand`)

**Recommendation**: Option A (subnamespaces) - clearest organization

### 2. MCP Syntax

Which tool naming for agent invocations?

- [ ] **Option A**: Snake case (`sprint_list`, `task_detail`)
- [ ] **Option B**: Dot notation (`sprint.list`, `task.detail`)
- [ ] **Option C**: Slash notation (`sprint/list`, `task/detail`)

**Recommendation**: Option A (snake_case) - most MCP-compatible

### 3. Scope Priority

Can we defer UI enhancements to compress timeline?

- [ ] **Full Scope**: Backend unification + UI filter enhancements (13.5 days)
- [ ] **Backend First**: Unification + MCP, defer UI filters (9 days)
- [ ] **Phased**: Do backend now (9 days), UI separately later

**Recommendation**: Backend First (9 days) - get foundation right, enhance UI later

### 4. Breaking Changes

Can we break agent command interfaces?

- [ ] **Yes**: Clean break, faster migration, update agent docs
- [ ] **No**: Maintain backwards compat, slower migration, more complexity

**Recommendation**: Yes - do it right, document clearly

### 5. YAML Cleanup

What to do with legacy YAML command files (52 directories)?

- [ ] **Delete**: Remove entirely after verification
- [ ] **Archive**: Move to `fragments/commands-legacy/`
- [ ] **Selective**: Keep unmigrated, delete migrated

**Recommendation**: Archive - safe approach, keep for reference

---

## Proposed Solution

### Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Command Layer                         │
│                  App\Commands\*                          │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ Orchestration│  │   Content    │  │  Navigation  │ │
│  │              │  │              │  │              │ │
│  │ • Sprints    │  │ • Search     │  │ • Inbox      │ │
│  │ • Tasks      │  │ • Notes      │  │ • Recall     │ │
│  │ • Agents     │  │ • Todo       │  │ • Channels   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
                          │
         ┌────────────────┼────────────────┐
         │                │                │
         ▼                ▼                ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   Web UI    │  │  MCP Server │  │ Artisan CLI │
│ /commands   │  │ sprint_list │  │ orch:sprints│
│ Returns:    │  │ Returns:    │  │ Returns:    │
│ Component   │  │ JSON        │  │ Table/JSON  │
└─────────────┘  └─────────────┘  └─────────────┘
```

### Key Principles

1. **Single Source of Truth** - One command class per functionality
2. **Context-Aware Response** - Commands detect web/MCP/CLI context and format accordingly
3. **MCP First** - Agents use clean MCP tools, not CLI hacks
4. **Backwards Compatible** - Phased migration, no breaking changes during transition
5. **Documentation Driven** - Comprehensive docs prevent future regressions

---

## Implementation Phases

### Phase 1: Foundation (1.5 days)

- Create subnamespace structure (`App\Commands\Orchestration\*`, etc.)
- Update `CommandRegistry` for new namespaces
- Enhance `BaseCommand` with context detection and smart response handling
- Test existing commands still work

**Deliverables**:
- Organized namespace structure
- Context-aware base command
- All existing functionality working

### Phase 2: Consolidation (3 days)

- Merge duplicate commands (Sprint, Task, Agent list/detail)
- Keep best features from both user and agent versions
- Update artisan commands to be thin wrappers
- Comprehensive testing

**Deliverables**:
- 5 unified command implementations
- No duplicate logic
- Feature parity maintained

### Phase 3: MCP Enhancement (2.5 days)

- Build MCP command bridge
- Add input schemas to all commands
- Expose 20+ tools via MCP (vs current 3)
- Update `.mcp.json` configuration
- Test with agents

**Deliverables**:
- Clean MCP tool exposure
- Schema-validated inputs
- Agents can invoke all commands easily

### Phase 4: CLI Enhancement (2 days, backend only)

- Add write operations (save, assign, update status)
- Add filters to user commands (backend only, no UI)
- Ensure CLI commands are thin wrappers
- Testing

**Deliverables**:
- Write operations working
- Advanced filters available
- CLI fully functional

### Phase 5: YAML Cleanup (1 day)

- Audit all YAML commands
- Archive to `fragments/commands-legacy/`
- Remove fallback logic
- Update documentation
- Testing

**Deliverables**:
- YAML system archived
- No legacy fallback
- Clean codebase

### Phase 6: Documentation & Hardening (3.5 days)

- Command development guide
- Agent guidelines (what NEVER to do)
- Type hints and validation
- Test suite for commands
- Update all documentation

**Deliverables**:
- Comprehensive guides
- Test coverage
- Zero ambiguity

---

## Timeline Estimates

### Conservative (Full Implementation)

- **Phase 1**: 1.5 days
- **Phase 2**: 3 days
- **Phase 3**: 2.5 days
- **Phase 4**: 3.5 days (includes UI work)
- **Phase 5**: 1 day
- **Phase 6**: 3.5 days
- **Total**: 13.5 days

### Recommended (Backend Focus)

- **Phase 1**: 1 day
- **Phase 2**: 2 days
- **Phase 3**: 2 days
- **Phase 4**: 1.5 days (backend only)
- **Phase 5**: 0.5 days
- **Phase 6**: 2 days (essential docs)
- **Total**: 9 days

### With Buffer

Add 2-3 days for unexpected issues, reviews, iterations.

**Realistic Timeline**: **3 weeks (15 days)**

---

## Success Criteria

- [ ] All commands in unified namespace
- [ ] No duplicate command logic
- [ ] MCP exposure with clean syntax (20+ tools)
- [ ] Agents can easily invoke all commands
- [ ] YAML system archived/removed
- [ ] Zero confusion about which system to use
- [ ] Comprehensive documentation published
- [ ] All tests passing
- [ ] No breaking changes to existing functionality

---

## Risks & Mitigations

### Risk 1: Breaking Changes
**Mitigation**: Keep old commands during transition, mark as deprecated, phased rollout

### Risk 2: Agent Confusion During Migration
**Mitigation**: Update docs BEFORE migration, clear warnings, test with agents

### Risk 3: Missing Use Cases
**Mitigation**: Audit current usage, review agent logs, user feedback

### Risk 4: Performance Regression
**Mitigation**: Benchmark current performance, optimize queries, load testing

### Risk 5: Incomplete YAML Migration
**Mitigation**: Thorough audit, archive (don't delete), keep fallback initially

---

## Dependencies

- PHP 8.2+ (for attributes, match expressions)
- Laravel 12
- MCP server infrastructure
- TipTap composer (web UI)
- Existing test suite

---

## Rollback Plan

### Emergency Rollback (< 1 hour)
```bash
git revert HEAD --no-edit
composer dump-autoload
npm run build
php artisan config:clear
```

### Partial Rollback
- Keep namespace structure
- Revert CommandRegistry changes
- Revert MCP changes
- Keep documentation

---

## Files Impacted

### Core Files to Modify
- `app/Services/CommandRegistry.php`
- `app/Commands/BaseCommand.php`
- `app/Console/Commands/Orchestration*.php` (14 files)
- `app/Commands/*.php` (32 files)

### New Files to Create
- `app/Mcp/OrchestrationMcpServer.php`
- `app/Commands/Orchestration/Sprint/*.php` (4 files)
- `app/Commands/Orchestration/Task/*.php` (5 files)
- `app/Commands/Orchestration/Agent/*.php` (3 files)
- `docs/command systems/COMMAND_DEVELOPMENT_GUIDE.md`
- `docs/command systems/AGENT_COMMAND_GUIDELINES.md`
- `tests/Feature/Commands/*.php` (20+ test files)

### Files to Archive
- `fragments/commands/*` (52 directories) → `fragments/commands-legacy/`

---

## Post-Migration Monitoring

### Week 1
- Monitor error logs
- Track agent command invocations
- Gather user feedback
- Fix critical issues

### Week 2-4
- Analyze usage patterns
- Optimize slow commands
- Plan UI enhancements (Phase 2)

### Metrics to Track
- Command invocation count (by command, by context)
- Command error rate
- Command execution time
- Agent success rate
- User feedback scores

---

## Related Documents

- `docs/command systems/COMMAND_SYSTEM_CURRENT_STATE_ANALYSIS.md` - Detailed analysis
- `docs/command systems/COMMAND_SYSTEM_MIGRATION_PLAN.md` - Full implementation plan
- `docs/command systems/COMMAND_QUICK_REFERENCE.md` - Current user command reference
- `docs/command systems/COMMAND_SYSTEM_FIX_SUMMARY.md` - Previous YAML→PHP migration

---

## Next Steps

1. **Review analysis and plan** - Read both comprehensive docs
2. **Answer strategic questions** - Provide decisions on 5 key questions above
3. **Approve scope** - Backend-only or full implementation?
4. **Set timeline** - When to start, what's the deadline?
5. **Begin Phase 1** - Start with foundation once approved

---

**Status**: AWAITING DECISIONS

Please review analysis document and provide answers to strategic questions to proceed.
