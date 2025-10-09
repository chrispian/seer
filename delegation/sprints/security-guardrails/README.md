# Security Guardrails Sprint

## Mission
Harden Fragments Engine for production use with defense-in-depth security guardrails.

## Status: Ready to Start

## Timeline: 4-5 Weeks (2 engineers)

## Priority: **CRITICAL** - Required before production deployment

## Documents

- **SPRINT-PLAN.md** - Complete 5-sprint breakdown with deliverables (350+ lines)
- **GUARD-001-policy-registry.md** - First task: PolicyRegistry implementation (600+ lines)
- **STATUS.md** - In `delegation/tasks/fe-guardrails-pack-v0.1/STATUS.md` (263 lines)

## Quick Start

1. **Read**: `SPRINT-PLAN.md` - Understand the 5-sprint approach
2. **Review**: Current security status in `../tasks/fe-guardrails-pack-v0.1/STATUS.md`
3. **Start**: `GUARD-001-policy-registry.md` - Begin with PolicyRegistry

## What We're Building

### Sprint 1: Foundation (Week 1)
- PolicyRegistry (YAML-driven policies)
- Risk scoring engine
- Dry-run mode

### Sprint 2: Approval Workflow (Week 2)
- Backend approval system
- React UI for approvals
- Audit trail integration

### Sprint 3: Shell Hardening (Week 3)
- Enhanced command validation
- Resource limits
- Improved audit logging

### Sprint 4: Guards (Week 4)
- FilesystemGuard (path restrictions)
- NetworkGuard (domain allowlists)

### Sprint 5: Integration (Week 5)
- ToolCallMiddleware wrapper
- End-to-end testing
- Documentation

## Current State

**Already Complete:**
- ✅ Comprehensive audit logging (TASK-0002)
- ✅ Basic PermissionGate
- ✅ ShellTool with basic checks
- ✅ Tool-Aware Turn pipeline

**To Build:**
- PolicyRegistry (YAML-driven)
- Risk scoring
- Approval workflow + UI
- Enhanced guards
- Middleware integration

## Success Metrics

- 100% of tool calls protected by policies
- 100% of high-risk operations require approval
- 0 security vulnerabilities
- < 10ms policy evaluation overhead
- >= 80% test coverage

## Team

- **Backend Engineer**: Sprints 1, 3, 4, 5
- **Full-Stack Engineer**: Sprint 2, testing

## Next Action

Start with **GUARD-001: PolicyRegistry** - 2 days estimated
