# Guardrails Task Pack Status

## Overview
This task pack defines security hardening for tool execution in Fragments Engine. The goal is to implement defense-in-depth guardrails without requiring Docker, making the system safer for AI agents to execute tools.

## Current Status: **Partially Implemented**

### What Exists Today

#### ✅ Foundation (Partial)
**From Tool-Aware Turn Implementation (Completed Oct 2025)**

1. **PermissionGate** - `app/Services/Orchestration/ToolAware/Guards/PermissionGate.php`
   - ✅ Tool allow-list filtering with wildcards
   - ✅ User/agent-specific permissions (stub for DB lookup)
   - ✅ Write permission detection
   - ✅ Logging of blocked tools
   - ❌ Not policy-driven (hardcoded lists)
   - ❌ No risk scoring
   - ❌ No approval workflow

2. **Security Guards** (Basic)
   - ✅ `StepLimiter.php` - Max 10 steps per turn
   - ✅ `RateLimiter.php` - 60/min, 300/hour limits
   - ✅ `Redactor.php` - PII/secret scrubbing
   - ❌ No command argument validation
   - ❌ No filesystem path restrictions
   - ❌ No network egress controls

3. **Audit Logging** (Comprehensive)
   - ✅ Command execution logging (`command_audit_logs` table)
   - ✅ Destructive command detection (14 patterns)
   - ✅ Model event logging (Spatie Activity Log)
   - ✅ User attribution, IP tracking
   - ✅ 90-day retention with automated cleanup
   - ✅ Multi-channel notifications (mail/slack/database)
   - ✅ **TASK-0002 COMPLETE**

4. **Tool Execution Pipeline**
   - ✅ `ToolAwarePipeline.php` - Orchestrates tool selection → execution → summarization
   - ✅ `ToolRunner.php` - Executes tools with timing
   - ✅ `ToolSelector.php` - AI-driven tool selection
   - ✅ Streaming support for real-time feedback
   - ❌ No middleware wrapper around tool calls
   - ❌ No dry-run mode
   - ❌ No approval hooks

#### ❌ Not Implemented Yet

**From Guardrails Pack Requirements:**

1. **ToolCallMiddleware** (GF-1)
   - Centralized preflight checks for all tool calls
   - Policy evaluation before execution
   - Post-call audit hooks
   - **Status:** Not started

2. **PolicyRegistry** (GF-2)
   - Single source of truth for all security policies
   - Hot-reload without restart
   - YAML/config-driven rules
   - Command/argument/path/domain allowlists
   - **Status:** Not started (currently hardcoded in config)

3. **Risk Scoring & Approval Hook** (GF-3)
   - Score based on operation scope (writes, network, sudo)
   - Configurable thresholds for approval
   - UI preview of dry-run before execution
   - **Status:** Not started

4. **LimitedShell** (Sprint 02)
   - Whitelisted commands only
   - Argument validation
   - Resource caps (CPU, memory, timeout)
   - **Status:** Basic shell execution exists but not hardened

5. **Filesystem Guard** (Sprint 03)
   - VFS-like FileOps facade
   - PHP open_basedir restrictions
   - Path allowlists
   - **Status:** Not started

6. **Network Guard** (Sprint 04)
   - HTTP client wrapper with domain allowlists
   - Optional OS-level egress filters
   - **Status:** Not started

7. **Tamper-Evident Audit** (Sprint 05)
   - JSONL with rolling hash chain
   - Replay capability
   - **Status:** Audit logging exists but not hash-chained

8. **OS-Level Sandbox** (Sprint 06)
   - Optional Firejail/bwrap integration
   - Seccomp filters
   - **Status:** Not started

### Architecture Alignment

**Guardrails Pack Architecture (Planned):**
```
ToolCallMiddleware (wraps all tool calls)
  ↓
PolicyRegistry (evaluates allowlists + risk)
  ↓
Approval Hook (if risk > threshold)
  ↓
LimitedShell / FileOps / NetworkClient (hardened executors)
  ↓
Audit Log (tamper-evident JSONL)
```

**Current Architecture:**
```
ToolAwarePipeline
  ↓
PermissionGate (basic allow-list)
  ↓
ToolRunner (executes without preflight)
  ↓
CommandAuditLog (comprehensive but not tamper-evident)
```

**Gap:** Missing policy-driven middleware layer and approval workflow.

## Comparison: Guardrails Pack vs. Current Implementation

| Feature | Guardrails Pack | Current | Status |
|---------|----------------|---------|--------|
| Tool allow-lists | PolicyRegistry (YAML) | Config hardcoded | 🟡 Partial |
| Risk scoring | Configurable thresholds | None | ❌ Missing |
| Approval workflow | UI preview + confirm | None | ❌ Missing |
| Command validation | Arg parsing + caps | None | ❌ Missing |
| Filesystem guard | VFS facade + basedir | None | ❌ Missing |
| Network guard | Domain allowlist | None | ❌ Missing |
| Audit logging | Hash-chained JSONL | Comprehensive DB logs | 🟢 Better than spec |
| Dry-run mode | Built-in | None | ❌ Missing |
| Middleware wrapper | All tool calls | None | ❌ Missing |
| Secrets redaction | Policy-driven | Basic PII redactor | 🟡 Partial |

## Recommendation: Integration Strategy

### Option 1: Gradual Enhancement (Recommended)
Build on existing code rather than replacing:

1. **Phase 1: Policy Layer** (2 weeks)
   - Create `PolicyRegistry` (wraps existing config)
   - Add risk scoring to `PermissionGate`
   - Implement dry-run mode in `ToolRunner`
   - Add approval hook to `ToolAwarePipeline`

2. **Phase 2: Shell Hardening** (1 week)
   - Harden existing shell tool with `LimitedShell` wrapper
   - Add argument validation
   - Implement resource caps

3. **Phase 3: Guards** (2 weeks)
   - Add `FilesystemGuard` for fs operations
   - Add `NetworkGuard` for HTTP clients
   - Integrate with existing tools

4. **Phase 4: Advanced Audit** (1 week)
   - Add hash-chaining to `CommandAuditLog`
   - Build replay capability
   - Optional: JSONL export

**Total: 6 weeks**

### Option 2: Full Rewrite
Implement all 8 sprints from scratch:
- **Total: 12-16 weeks**
- **Risk:** May duplicate existing audit system

### Option 3: Hybrid Approach (Most Pragmatic)
- Keep existing audit logging (TASK-0002) - it's comprehensive
- Implement missing pieces from guardrails pack:
  - PolicyRegistry + risk scoring
  - Approval workflow UI
  - LimitedShell hardening
  - FileOps + NetworkGuard
- **Total: 4-5 weeks**

## Next Steps

### Immediate Actions
1. **Decision:** Choose integration strategy (recommend Option 3)
2. **Inventory:** Map existing code to guardrails requirements
3. **Refactor:** Extract policy logic from `PermissionGate` → `PolicyRegistry`
4. **Implement:** Start with highest-risk gap (shell command validation)

### Quick Wins
1. **Add dry-run mode** to `ToolRunner` (1 day)
2. **Extract PolicyRegistry** from config (2 days)
3. **Add risk scoring** to permission gate (2 days)
4. **Create approval UI component** (3 days)

### Blockers
- None identified - can start immediately

### Dependencies
- Existing tool-aware turn implementation ✅
- Audit logging system (TASK-0002) ✅
- Dashboard feature (for approval UI) - in planning

## Files to Review

**Existing Security Code:**
```
app/Services/Orchestration/ToolAware/Guards/
├── PermissionGate.php       # Allow-list filtering
├── StepLimiter.php          # Step count limits
├── RateLimiter.php          # Rate limiting
└── Redactor.php             # PII scrubbing

app/Listeners/
└── CommandLoggingListener.php  # Command audit logging

app/Models/
└── CommandAuditLog.php      # Audit log model

config/
└── fragments.php            # Tool configuration
```

**Guardrails Pack Specs:**
```
delegation/tasks/fe-guardrails-pack-v0.1/
├── docs/architecture.md     # Architecture overview
├── tasks/
│   ├── 01-foundation/       # ToolCallMiddleware + PolicyRegistry
│   ├── 02-limited-shell/    # Shell hardening
│   ├── 03-filesystem-guard/ # File ops security
│   ├── 04-network-guard/    # Network security
│   ├── 05-approvals-audit/  # Approval workflow
│   ├── 06-optional-os-sandbox/  # Firejail/bwrap
│   ├── 07-ci-and-validation/    # Testing
│   └── 08-docs-and-ux/      # Documentation
└── stubs/config/
    └── guardrails.policy.yaml  # Policy template
```

## Metrics

**Code Coverage:**
- Foundation components: ~40% complete
- Shell hardening: ~10% complete
- Filesystem guard: 0% complete
- Network guard: 0% complete
- Approval workflow: 0% complete
- Audit logging: **100% complete** (exceeds spec)

**Estimated Completion:**
- Option 1 (Gradual): 6 weeks
- Option 2 (Full): 12-16 weeks
- Option 3 (Hybrid): 4-5 weeks

## Questions for Stakeholder

1. **Priority:** Which guardrails are most critical? (Shell? Filesystem? Network?)
2. **Timeline:** What's the target completion date?
3. **Scope:** Keep existing audit system or implement hash-chained JSONL?
4. **Resources:** Single developer or team?
5. **Integration:** Build on existing code or start fresh?
