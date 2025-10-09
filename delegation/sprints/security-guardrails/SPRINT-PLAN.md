# Security Guardrails Sprint Plan

## Mission
Harden Fragments Engine tool execution with defense-in-depth guardrails before production use. Build on existing tool-aware infrastructure rather than starting from scratch.

## Timeline: 4-5 Weeks

## Approach: Hybrid Integration
- Keep existing audit logging (TASK-0002) - it exceeds requirements ✅
- Enhance existing PermissionGate and ShellTool
- Add missing critical layers: PolicyRegistry, ApprovalWorkflow, Guards

## Sprint Breakdown

### Sprint 1: Foundation & Policy Layer (Week 1)
**Goal:** Centralized policy-driven security decisions

**Tasks:**
1. **GUARD-001: PolicyRegistry Service** (2 days)
   - Extract policy logic from config into dedicated service
   - YAML-based policy configuration
   - Hot-reload support (no restart required)
   - Policy schema validation

2. **GUARD-002: Risk Scoring Engine** (1.5 days)
   - Score tool calls based on scope (writes, network, sudo)
   - Configurable thresholds (low/medium/high/critical)
   - Integration with PermissionGate

3. **GUARD-003: Dry-Run Mode** (1.5 days)
   - Add `--dry-run` flag to ToolRunner
   - Simulate execution without side effects
   - Return predicted changes/impact
   - Log dry-run attempts

**Deliverables:**
- `app/Services/Security/PolicyRegistry.php`
- `app/Services/Security/RiskScorer.php`
- `config/security/policies.yaml`
- Policy schema documentation
- Unit tests (80% coverage)

**Acceptance:**
- [ ] All tool calls evaluated by PolicyRegistry
- [ ] Risk scores calculated and logged
- [ ] Dry-run mode prevents actual execution
- [ ] Policies hot-reload without restart

---

### Sprint 2: Approval Workflow (Week 2)
**Goal:** Human-in-the-loop for high-risk operations

**Tasks:**
1. **GUARD-004: Approval Hook System** (2 days)
   - Add approval checkpoint to ToolAwarePipeline
   - Backend API for approval requests
   - Approval state management (pending/approved/rejected)
   - Timeout handling (auto-reject after 5min)

2. **GUARD-005: Approval UI Component** (2 days)
   - React component for approval dialog
   - Show: command preview, risk score, dry-run results
   - Actions: Approve, Reject, Always Allow, Never Allow
   - Real-time updates (polling or WebSocket)

3. **GUARD-006: Approval Audit Trail** (1 day)
   - Log all approval decisions
   - Track approver identity
   - Include justification/notes
   - Integrate with existing audit system

**Deliverables:**
- `app/Services/Security/ApprovalManager.php`
- `app/Http/Controllers/Api/ApprovalController.php`
- `resources/js/components/ApprovalDialog.tsx`
- `approval_requests` migration + model
- API documentation

**Acceptance:**
- [ ] High-risk commands pause for approval
- [ ] UI shows clear preview of what will execute
- [ ] Approval/rejection is logged
- [ ] Timeout auto-rejects pending requests
- [ ] "Always allow" updates policy registry

---

### Sprint 3: Shell Hardening (Week 3)
**Goal:** Lock down shell command execution

**Tasks:**
1. **GUARD-007: Enhanced ShellTool** (2 days)
   - Strengthen existing ShellTool.php
   - Argument validation and escaping
   - Command parsing (separate binary from args)
   - Prevent argument injection attacks
   - Enforce allowlist at argument level

2. **GUARD-008: Resource Limits** (1.5 days)
   - CPU time limits (via `ulimit` or process groups)
   - Memory limits
   - File descriptor limits
   - Process count limits
   - Configurable per-command

3. **GUARD-009: Shell Audit Enhancement** (1.5 days)
   - Enhanced logging for shell commands
   - Capture environment variables (sanitized)
   - Record working directory
   - Track process tree (parent/child processes)
   - Add to existing CommandAuditLog

**Deliverables:**
- Enhanced `ShellTool.php`
- `app/Services/Security/Guards/ShellGuard.php`
- Resource limit configuration
- Shell execution tests

**Acceptance:**
- [ ] Only allowlisted commands execute
- [ ] Arguments validated and escaped
- [ ] Resource limits enforced
- [ ] All shell execution audited
- [ ] No argument injection possible

---

### Sprint 4: Filesystem & Network Guards (Week 4)
**Goal:** Secure file and network operations

**Tasks:**
1. **GUARD-010: FilesystemGuard** (2.5 days)
   - FileOps facade for all file operations
   - Path allowlist (workspace only by default)
   - Prevent path traversal (../../ attacks)
   - PHP open_basedir enforcement
   - Symlink attack prevention
   - File size limits

2. **GUARD-011: NetworkGuard** (2.5 days)
   - HTTP client wrapper with domain allowlist
   - SSL/TLS enforcement
   - Request size limits
   - Response size limits
   - Rate limiting per domain
   - Block private IP ranges (SSRF prevention)

**Deliverables:**
- `app/Services/Security/Guards/FilesystemGuard.php`
- `app/Services/Security/Guards/NetworkGuard.php`
- Guard configuration files
- Integration with existing tools
- Unit + integration tests

**Acceptance:**
- [ ] File operations restricted to allowed paths
- [ ] No path traversal possible
- [ ] HTTP requests limited to allowed domains
- [ ] Private IPs blocked
- [ ] All operations logged

---

### Sprint 5: Integration & Polish (Week 5)
**Goal:** End-to-end testing, documentation, production readiness

**Tasks:**
1. **GUARD-012: Middleware Integration** (1.5 days)
   - Create ToolCallMiddleware wrapper
   - Integrate all guards into single pipeline
   - Ensure all tool calls flow through middleware
   - Performance optimization (caching policy lookups)

2. **GUARD-013: Testing & Validation** (2 days)
   - End-to-end security tests
   - Penetration testing scenarios
   - Performance testing
   - Edge case validation
   - CI/CD integration

3. **GUARD-014: Documentation** (1.5 days)
   - Security architecture documentation
   - Policy configuration guide
   - Threat model documentation
   - Admin guide for managing policies
   - Developer guide for adding new tools

**Deliverables:**
- `app/Services/Security/ToolCallMiddleware.php`
- Comprehensive test suite
- `docs/SECURITY.md`
- `docs/POLICY_GUIDE.md`
- Production deployment checklist

**Acceptance:**
- [ ] All tool calls protected by full guard stack
- [ ] Test coverage >= 80%
- [ ] Documentation complete
- [ ] No security vulnerabilities in tests
- [ ] Ready for production use

---

## Success Metrics

### Security Metrics
- 100% of tool calls go through PolicyRegistry
- 100% of high-risk operations require approval
- 0 command injection vulnerabilities
- 0 path traversal vulnerabilities
- 0 SSRF vulnerabilities

### Performance Metrics
- Policy evaluation: < 10ms
- Dry-run overhead: < 50ms
- No degradation to existing tool execution times

### Code Quality
- Test coverage: >= 80%
- All guards have unit tests
- Integration tests for full pipeline
- Security tests pass

## Dependencies

### Required (Already Complete)
- ✅ Tool-Aware Turn implementation
- ✅ Audit logging system (TASK-0002)
- ✅ Basic PermissionGate
- ✅ ShellTool with basic checks

### Required (To Build)
- React/shadcn UI framework (for approval dialog)
- Database migration for approval_requests
- Policy configuration system

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Performance overhead | Medium | Cache policy lookups, async logging |
| False positives blocking valid operations | High | Careful allowlist design, dry-run testing |
| Approval timeout UX | Medium | Clear timeout warnings, auto-save state |
| Policy complexity | Medium | Good documentation, examples, validation |
| Breaking existing tools | High | Comprehensive testing, feature flags |

## Rollout Strategy

### Phase 1: Dark Launch (Day 1-7)
- Deploy with all guards in "log-only" mode
- Collect metrics on what would be blocked
- Tune policies based on actual usage

### Phase 2: Gradual Enforcement (Day 8-14)
- Enable guards one at a time
- Start with filesystem (lowest risk)
- Then network, then shell
- Monitor for issues

### Phase 3: Full Production (Day 15+)
- All guards active
- Approval workflow required for high-risk ops
- Continuous monitoring

## Configuration Structure

```yaml
# config/security/policies.yaml
version: 1.0

# Risk thresholds
risk_thresholds:
  low: 0-25      # Auto-approve
  medium: 26-50  # Log + approve
  high: 51-75    # Require approval
  critical: 76-100  # Require approval + justification

# Tool allowlists
tools:
  allowed:
    - shell
    - fs.*
    - mcp.*
  denied:
    - dangerous.*

# Shell commands
shell:
  allowlist:
    - ls
    - pwd
    - echo
    - cat
    - grep
    - find
    - git
  resource_limits:
    timeout: 30s
    memory: 512M
    cpu_time: 10s

# Filesystem
filesystem:
  allowed_paths:
    - /workspace
    - /tmp
  denied_paths:
    - /etc
    - /var
    - ~/.ssh
  max_file_size: 10M

# Network
network:
  allowed_domains:
    - "*.github.com"
    - "api.openai.com"
    - "api.anthropic.com"
  denied_ips:
    - 127.0.0.0/8    # Localhost
    - 10.0.0.0/8     # Private
    - 172.16.0.0/12  # Private
    - 192.168.0.0/16 # Private
  max_request_size: 1M
  max_response_size: 10M
```

## Team Assignment

**Backend Engineer:**
- Sprint 1: PolicyRegistry, RiskScorer, DryRun
- Sprint 3: ShellGuard enhancements
- Sprint 4: FilesystemGuard, NetworkGuard
- Sprint 5: Middleware integration

**Full-Stack Engineer:**
- Sprint 2: Approval system (backend + frontend)
- Sprint 5: Testing & documentation

**Total:** 2 engineers, 5 weeks

## Deliverables Checklist

- [ ] PolicyRegistry service
- [ ] Risk scoring engine
- [ ] Dry-run mode
- [ ] Approval workflow (backend)
- [ ] Approval UI (React component)
- [ ] Enhanced ShellTool with guards
- [ ] FilesystemGuard
- [ ] NetworkGuard
- [ ] ToolCallMiddleware
- [ ] Comprehensive test suite
- [ ] Security documentation
- [ ] Policy configuration guide
- [ ] Production deployment plan

## Next Steps

1. **Review this plan** - Adjust timeline/scope as needed
2. **Create task tickets** - Break down into GitHub issues
3. **Set up sprint board** - Track progress
4. **Start Sprint 1** - Begin with GUARD-001 (PolicyRegistry)
