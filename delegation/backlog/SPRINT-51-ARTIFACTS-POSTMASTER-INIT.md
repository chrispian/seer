# SPRINT-51: Artifacts Store + Postmaster + Agent INIT Phase

**Sprint ID:** `0199b648-7017-7022-883a-be2ac760a175`  
**Duration:** Oct 7-14, 2025 (7 days)  
**Priority:** High  
**Status:** Planned

## Overview

Implement a shared CAS-backed artifacts store + Postmaster delivery worker, inbox messaging for PM→Agent bootstraps, and a formal Agent INIT phase (resume memory, load profile/settings/rules) in Fragments Engine Orchestration. Keep context files OUT of repos. All parcels archived via Postmaster and referenced by stable fe:// URIs.

## Key Deliverables

1. **Content-Addressable Storage (CAS)** - SHA256-based artifact store with fe:// URIs
2. **Postmaster Queue Worker** - Parcel ingestion, rewriting, and delivery
3. **Inbox Messaging System** - PM→Agent messages, broadcasts, never delete
4. **Agent INIT Phase** - Memory resume, profile loading, healthcheck, plan reconciliation
5. **Artifact & Message APIs** - RESTful endpoints for accessing artifacts and inbox
6. **External Agent Commands** - Slash commands for agent shims
7. **Memory Policy** - Durable vs ephemeral keys with compaction
8. **Secret Redaction** - Prevent credential leakage in archives

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         PROJECT MANAGER                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ Context Pack + Attachments
                             ▼
                    ┌────────────────────┐
                    │  POSTMASTER JOB    │
                    │  ProcessParcel     │
                    └────────┬───────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
              ▼              ▼              ▼
      ┌─────────────┐  ┌─────────┐  ┌─────────────┐
      │ ContentStore│  │ Message │  │  Artifact   │
      │   (CAS)     │  │  Model  │  │   Model     │
      └─────────────┘  └─────────┘  └─────────────┘
              │              │              │
              │ SHA256 Hash  │ Inbox Entry  │ fe:// URI
              ▼              ▼              ▼
       by-hash/{h2}/{hash}   ┌─────────────────────┐
       by-task/{id}/files    │   AGENT INBOX       │
                              └─────────┬───────────┘
                                        │
                                        │ /check-messages
                                        ▼
                              ┌─────────────────────┐
                              │   AGENT (External)  │
                              │   INIT PHASE        │
                              └─────────┬───────────┘
                                        │
                    ┌───────────────────┼────────────────────┐
                    ▼                   ▼                    ▼
            init.resume_memory  init.load_profile   init.healthcheck
                    │                   │                    │
                    └───────────────────┴────────────────────┘
                                        │
                                        ▼
                              ┌─────────────────────┐
                              │  CLAIM TASK & START │
                              └─────────────────────┘
```

## Task Breakdown (18 Tasks)

### Phase 1: Foundation (Parallel - No Dependencies)

**T-ART-01-CONFIG** (1h) - Add orchestration config
- FE_ARTIFACTS_DISK, FE_ARTIFACTS_ROOT, FE_MSG_RETENTION_DAYS
- Update .env.example
- **Can start immediately**

**T-ART-03-MSG-MIGRATION** (1-2h) - Create messages table
- Schema: stream, type, task_id, to_agent, from_agent, headers, envelope, read_at
- Indexes and foreign keys
- **Can start immediately**

**T-ART-04-ART-MIGRATION** (1-2h) - Create artifacts table
- Schema: task_id, hash, filename, mime_type, size_bytes, fe_uri, storage_path
- Indexes and constraints
- **Can start immediately**

### Phase 2: Core Services (Depends on Phase 1)

**T-ART-02-CAS** (3-4h) - ContentStore service
- put/get/exists methods
- SHA256 deduplication
- by-hash and by-task layouts
- fe:// URI formatter
- **Depends on:** T-ART-01-CONFIG

**T-ART-05-MSG-MODEL** (2h) - Message model
- Relationships, scopes, markAsRead()
- **Depends on:** T-ART-03-MSG-MIGRATION

**T-ART-06-ART-MODEL** (2h) - Artifact model
- Relationships, content accessor
- **Depends on:** T-ART-04-ART-MIGRATION, T-ART-02-CAS

### Phase 3: Postmaster & APIs (Depends on Phase 2)

**T-ART-07-POSTMASTER-JOB** (4-5h) - ProcessParcel job
- Extract large blobs, store via CAS
- Rewrite envelope with fe:// URIs
- Create Artifact records
- Dispatch postmaster.delivery event
- **Depends on:** T-ART-02-CAS, T-ART-06-ART-MODEL

**T-ART-08-POSTMASTER-CMD** (2h) - postmaster:run command
- Queue worker for postmaster channel
- **Depends on:** T-ART-07-POSTMASTER-JOB

**T-ART-09-INBOX-API** (4-5h) - Inbox endpoints
- POST /api/agents/{id}/inbox
- GET /api/agents/{id}/inbox
- POST /api/messages/{id}/read
- POST /api/projects/{id}/broadcast
- **Depends on:** T-ART-05-MSG-MODEL

**T-ART-10-ARTIFACTS-API** (3-4h) - Artifacts endpoints
- POST /api/tasks/{id}/artifacts (Postmaster-only)
- GET /api/tasks/{id}/artifacts
- GET /api/artifacts/{id}/download
- **Depends on:** T-ART-06-ART-MODEL

### Phase 4: Agent Features (Depends on Phase 3)

**T-ART-11-SLASH-COMMANDS** (3-4h) - External agent commands
- /check-messages, /ack, /pull-artifacts, /handoff
- **Depends on:** T-ART-09-INBOX-API, T-ART-10-ARTIFACTS-API

**T-ART-12-MEMORY-POLICY** (3-4h) - Memory lifecycle
- Durable vs ephemeral keys
- Compaction on task completion
- **Depends on:** T-ART-02-CAS, T-ART-07-POSTMASTER-JOB

**T-ART-13-INIT-PHASE** (5-6h) - Agent INIT service
- init.resume_memory, init.load_profile, init.healthcheck, init.plan_confirm
- **Depends on:** T-ART-05-MSG-MODEL, T-ART-12-MEMORY-POLICY

**T-ART-14-SECRET-REDACTION** (3-4h) - Secret filters
- Pattern-based redaction
- AWS keys, APP_KEY, Bearer tokens
- **Depends on:** T-ART-07-POSTMASTER-JOB

### Phase 5: Quality & Documentation (Depends on All)

**T-ART-15-TESTS** (6-8h) - Comprehensive testing
- Unit tests for all services/models
- Feature tests for all APIs
- Integration test for full workflow
- **Depends on:** All implementation tasks

**T-ART-16-DOCS** (4-5h) - Documentation
- docs/orchestration/postmaster-and-init.md
- Architecture, API reference, examples
- **Depends on:** All implementation tasks

**T-ART-17-MCP-TOOLS** (4-5h) - MCP integration (Optional)
- orchestration_messages_list, orchestration_messages_read
- orchestration_artifacts_list, orchestration_artifacts_get
- **Depends on:** T-ART-09-INBOX-API, T-ART-10-ARTIFACTS-API

**T-ART-18-INTEGRATION** (4-5h) - E2E integration
- Send test parcel with >5MB attachment
- Verify full workflow from PM to Agent
- QA report generation
- **Depends on:** T-ART-15-TESTS

## Parallelization Strategy

### Day 1-2: Foundation + Core Services
**Parallel Stream A:** T-ART-01 → T-ART-02  
**Parallel Stream B:** T-ART-03 → T-ART-05  
**Parallel Stream C:** T-ART-04 → T-ART-06

### Day 3-4: Postmaster + APIs
**Parallel Stream A:** T-ART-07 → T-ART-08  
**Parallel Stream B:** T-ART-09  
**Parallel Stream C:** T-ART-10

### Day 5: Agent Features
**Parallel Stream A:** T-ART-11  
**Parallel Stream B:** T-ART-12 → T-ART-13  
**Parallel Stream C:** T-ART-14

### Day 6-7: Quality & Polish
**Serial:** T-ART-15 → T-ART-18  
**Parallel with above:** T-ART-16, T-ART-17

## Time Estimates

| Phase | Tasks | Min Hours | Max Hours | Days |
|-------|-------|-----------|-----------|------|
| Foundation | 3 | 3 | 5 | 0.5-1 |
| Core Services | 3 | 7 | 10 | 1-1.5 |
| Postmaster & APIs | 4 | 13.5 | 18 | 2-2.5 |
| Agent Features | 4 | 14 | 18 | 2-2.5 |
| Quality & Docs | 4 | 18 | 23 | 2.5-3 |
| **TOTAL** | **18** | **55.5** | **74** | **7-9** |

**Sprint Estimate:** 5-7 days (with parallelization and 8hr work days)

## Critical Path

```
T-ART-01 → T-ART-02 → T-ART-07 → T-ART-12 → T-ART-13 → T-ART-15 → T-ART-18
```

This is the longest dependency chain (≈30 hours). All other tasks can be parallelized around it.

## Acceptance Criteria (from Context Pack)

- ✅ Agents can /check-messages, receive context pack, run INIT, and claim task
- ✅ Postmaster rewrites parcel blobs to fe://artifacts and emits postmaster.delivery
- ✅ Artifacts visible at GET /api/tasks/{id}/artifacts with by-task pointers
- ✅ Messages persist forever; read markers visible; search by task/agent
- ✅ Unit + feature tests pass
- ✅ Final summary includes Intent Plan, deliverables, and next steps

## Configuration Required

Add to `.env`:

```bash
# Artifacts Storage
FE_ARTIFACTS_DISK=local
FE_ARTIFACTS_ROOT=orchestration/artifacts

# Message Retention
FE_MSG_RETENTION_DAYS=365

# Secret Redaction (optional patterns)
FE_REDACT_PATTERNS="AWS_(ACCESS|SECRET)_KEY,APP_KEY,Bearer [A-Za-z0-9._-]+"
```

## API Endpoints Summary

### Inbox API
- `POST /api/agents/{id}/inbox` - Send message to agent
- `GET /api/agents/{id}/inbox?status=unread|all` - List inbox
- `POST /api/messages/{id}/read` - Mark as read
- `POST /api/projects/{id}/broadcast` - Broadcast to all agents

### Artifacts API
- `POST /api/tasks/{id}/artifacts` - Create artifact (Postmaster-only)
- `GET /api/tasks/{id}/artifacts` - List task artifacts
- `GET /api/artifacts/{id}/download` - Download artifact

### Slash Commands
- `/check-messages {agent_id}` - Check inbox
- `/ack {message_id}` - Mark message read
- `/pull-artifacts {task_id}` - List/download artifacts
- `/handoff {target} --with {pack|auto}` - Handoff task

## Memory Keys

### Durable (persist forever)
- `mem:task:{task_id}:boot` - Boot summary
- `mem:task:{task_id}:notes` - Agent notes
- `mem:task:{task_id}:postop` - Post-operation capsule

### Ephemeral (TTL 86400s)
- `mem:task:{task_id}:scratch:*` - Working memory

## Secret Redaction Patterns

Default patterns:
- `AWS_(ACCESS|SECRET)_KEY`
- `APP_KEY`
- `Bearer [A-Za-z0-9._-]+`

Custom patterns configurable in `config/orchestration.php`.

## Risk & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| CAS storage disk fills up | High | Add disk monitoring, implement cleanup policy |
| Secrets leak in artifacts | Critical | Multi-layer redaction, audit logging |
| Message table growth | Medium | Implement archival after retention period |
| INIT phase fails | High | Graceful degradation, detailed error logging |
| Large file uploads slow | Medium | Streaming upload, chunk processing |

## Future Enhancements (Backlog)

From context pack:
- S3/Glacier policy for long-term artifact retention (med priority)
- NATS transport adapter (swap from Redis Streams) (med priority)
- Secret redaction filters in Postmaster (high priority) ← **IN THIS SPRINT**
- MCP mappings for inbox and bus (med priority) ← **PARTIAL - T-ART-17**

## Related Documentation

- `docs/orchestration/settings.md` - Orchestration settings sketch
- `docs/orchestration/streams.md` - Messaging streams spec (draft)
- `docs/orchestration/postmaster-and-init.md` - **TO BE CREATED (T-ART-16)**

## Sprint Success Metrics

1. **All 18 tasks completed** with acceptance criteria met
2. **Test coverage >80%** for new code
3. **Full workflow tested** PM→Postmaster→Agent→INIT→Claim
4. **Documentation complete** with examples and diagrams
5. **Zero secrets leaked** in test artifacts
6. **Performance acceptable** - parcel processing <5s for typical pack

## How to Run (from Context Pack)

```bash
# Send parcel to agent
fe pm:send --agent=A-fe-builder --task=T-ARTIFACTS-POSTMASTER-INIT \
  --file=./packs/T-ARTIFACTS-POSTMASTER-INIT.yaml

# Start Postmaster worker
fe postmaster:run

# Check messages (external agent)
fe agent:check-messages

# Pull artifacts
fe agent:pull-artifacts T-ARTIFACTS-POSTMASTER-INIT
```

## Sprint Planning Notes

- **Start Date:** Monday, Oct 7, 2025
- **End Date:** Monday, Oct 14, 2025
- **Review:** Daily standups, check critical path progress
- **Blockers:** Escalate immediately if CAS implementation struggles
- **Pair Programming:** Recommended for T-ART-07 (Postmaster) and T-ART-13 (INIT)
- **Code Review:** All tasks before merging, focus on secret redaction logic

---

**Created:** 2025-10-06  
**Sprint Code:** SPRINT-51  
**Project:** P-FRAGMENTS-ENGINE  
**Agent Profile:** A-fe-builder
