# Sprint 40 Planning – Fragments Engine

## Team & Cadence
- Coordinator: Codex (planning/sprint-40 branch)
- Type System Lead: Priya
- Slash Command Lead: Malik
- Scheduler Lead: Jordan
- Inbox Lead: Sofia
- Observability Lead: Eli
- Check-ins: async updates as tasks complete or blockers appear; surface cross-stream dependencies immediately.

## Working Notes
- Use unified diffs for all edits.
- Follow existing implementation patterns; request clarification on ambiguities.
- Honor precedence rules for shared loaders (types, command packs) and keep schema changes backwards compatible where feasible.

## Task Tracker
| ID | Workstream | Owner | Status | Notes / Next Step |
| --- | --- | --- | --- | --- |
| OPS-01 | Coordination | Codex | Pending | Confirmed leads; monitor hand-offs across streams. |
| OPS-02 | Coordination | Codex | Pending | Stand up shared tracker (this doc) and keep statuses current. |
| OPS-03 | Coordination | Codex | Pending | Establish async touchpoints; ping leads when dependencies emerge. |
| OPS-04 | Coordination | Codex | Pending | Align on testing expectations (Pest suites, artisan commands) before implementation proposals land. |
| TS-01 | Type Packs | Priya | Pending | Audit existing type usage (core models/migrations/service flows). |
| TS-02 | Type Packs | Priya | Pending | Draft registry/cache architecture with precedence strategy and loader flow. |
| TS-03 | Type Packs | Priya | Pending | Design migrations for registry + generated/partial indexes compatible with current fragment schema. |
| TS-04 | Type Packs | Priya | Pending | Plan schema validation integration within ingestion pipelines and outline Pest coverage. |
| TS-05 | Type Packs | Priya | Pending | Evaluate optional `fragment_states` split (relations, backfill, feature flag). |
| SC-01 | Slash Commands | Malik | Pending | Map existing PHP command flow and define parity checklist for DSL migration. |
| SC-02 | Slash Commands | Malik | Pending | Specify filesystem + override rules for command packs mirroring type precedence. |
| SC-03 | Slash Commands | Malik | Pending | Architect DSL runner lifecycle, templating, and capability gating. |
| SC-04 | Slash Commands | Malik | Pending | Plan migration of built-in commands into packs with helper services. |
| SC-05 | Slash Commands | Malik | Pending | Design artisan tooling (`frag:command:*`) and validation workflows. |
| SCH-01 | Scheduler | Jordan | Pending | Baseline queues/jobs to ensure compatibility with existing pipelines. |
| SCH-02 | Scheduler | Jordan | Pending | Produce schema plan for `schedules` / `schedule_runs` (timezone + idempotency). |
| SCH-03 | Scheduler | Jordan | Pending | Define `frag:scheduler:tick` command, locking, and deployment guidance. |
| SCH-04 | Scheduler | Jordan | Pending | Outline `RunCommandJob` execution path leveraging the DSL runner. |
| SCH-05 | Scheduler | Jordan | Pending | Scope scheduled command packs (`/news-digest-ai`, `/remind`) with payload templates. |
| INBX-01 | Inbox | Sofia | Pending | Analyze existing inbox command behavior vs deterministic review requirements. |
| INBX-02 | Inbox | Sofia | Pending | Propose schema/index updates for inbox lifecycle + audit trail. |
| INBX-03 | Inbox | Sofia | Pending | Map API surface for list/accept/edit flows with authorization. |
| INBX-04 | Inbox | Sofia | Pending | Design deterministic accept flow coordinating fragment updates and audit logging. |
| INBX-05 | Inbox | Sofia | Pending | Assess read-model/caching needs for inbox performance aligned with type indexing. |
| OBS-01 | Observers | Eli | Pending | Review scaffold provider vs current event ecosystem; adapt where needed. |
| OBS-02 | Observers | Eli | Pending | Tailor event/listener set for scheduler, commands, tools, fragments with minimal duplication. |
| OBS-03 | Observers | Eli | Pending | Refine migration plan for metrics tables to match repo conventions. |
| OBS-04 | Observers | Eli | Pending | Plan Pest coverage/backfill strategies for new observers and metrics. |
| XFN-01 | Cross-Functional | Codex | Pending | Document shared caching/config dependencies between type and command packs. |
| XFN-02 | Cross-Functional | Codex | Pending | Outline feature-flag + rollout sequencing for all streams. |
| XFN-03 | Cross-Functional | Codex | Pending | Coordinate documentation updates (docs/delegation) with final architecture decisions. |
| XFN-04 | Cross-Functional | Codex | Pending | Schedule security/design review for YAML execution, schema validation, job safety. |

