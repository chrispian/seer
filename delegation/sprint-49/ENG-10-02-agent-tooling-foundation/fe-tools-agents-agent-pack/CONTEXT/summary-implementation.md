# Summary & Suggested Implementation

## Principles
- Deterministic-first, generative-second
- Guardrails everywhere; scopes & allowlists
- Everything observable (telemetry, provenance)
- One mental model: tools as versioned contracts

## Architecture (Laravel + Prism + optional MCP)
- Tool Facade Layer (JSON schemas)
- Execution Layer (Laravel Services/Actions)
- Exposure Layer (Prism local, optional REST, optional MCP)
- Policy Layer (scopes, quotas, approvals, audit)

## Core Capabilities
1) **DB.Query tool** (deterministic, explainable)
2) **Deterministic export tooling** (md/txt/pdf/csv/xlsx/json, artifacts registry)
3) **Agent memory** (short-term notes/vectors + long-term decisions/rollups)
4) **Prompt Orchestrator** (assemble system prompt on the fly + telemetry/A-B)
5) **Project management** (work_items + events + sprints + kanban)

## Foundational Tools
- `shell.exec` (safe, allowlist, dry-run)
- `fs.read` / `fs.write` / `fs.diff.apply` (deterministic patching)
- `repo.git` (status/commit/diff/PR)
- `scheduler.runLater/runEvery`
- `notify.push`
- `web.fetch` (read-only, cached)
- `secrets.get` (masked in logs)
- `metrics.emit`

## Governance
- Per-agent capability tokens (scopes+quotas)
- Human-in-the-loop for high-risk operations
- Plan → Approve → Apply for mutations
- Immutable audit + artifacts hashing
