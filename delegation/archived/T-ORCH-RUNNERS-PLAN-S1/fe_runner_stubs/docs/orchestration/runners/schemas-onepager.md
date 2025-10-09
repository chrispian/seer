# Runner Schemas v0 (One-Pager)

**Goal:** Standardize emissions across all agents/runners.

- `PlanSpec` — checklist-as-mini-queue (nodes, budgets, view_signatures).
- `StepSpec` — per-node contract (inputs, preconditions, emits).
- `StepResult` — events/outputs/checkpoint/yield.
- `Artifact` — fe:// URIs and manifest.
- `EventEnvelope` — OTel-style telemetry wrapper.

All emitted JSON MUST include `schema_version` and `task_id`, `run_id`, and `step_id` where applicable.
