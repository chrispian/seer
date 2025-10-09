# Fragments Engine — Guardrails & Sandbox Task Pack (v0.1)

**Goal:** Harden tool execution and agent behaviors without requiring Docker, while allowing optional OS-level sandboxes.
This pack is designed for a **local agent** to plan sprints/tasks and generate detailed implementation work items.

**Key principles**
- Deny by default; allow by explicit, minimal allowlists.
- Prefer in-process controls (middleware, wrappers) first; add OS-level hardening as optional.
- All actions are loggable, explainable, and—when risky—require approval.
- Secrets never leave the guardrail boundary in plaintext.
- Everything is testable with deterministic fixtures.

**Deliverables in this pack**
- Sprint/backlog YAMLs under `tasks/`.
- Implementation stubs in `stubs/` for the local agent to expand.
- Example configs and policy templates in `stubs/config/`.
- Docs outlines in `docs/`.
