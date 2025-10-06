# ToolCrate MCP — Agent Integration Guide

**Package**: `hollis-labs/laravel-tool-crate`  
**Server (MCP)**: `ToolCrate` (local/stdio via Laravel MCP)  
**Version**: `0.2.0`  
**Purpose**: Opinionated, local-first dev toolbox with a **help layer** to keep discovery/context small.

---

## Why agents should like it
- **Minimal context bloat**: only enabled tools are registered; names/descriptions are terse. Agents query `help.index` → one small shortlist, then drill into a single tool via `help.tool`.
- **Strong defaults**: predictable tools for common dev ops (JSON, search, file read, diffs, light refactors, git, CSV/TSV queries).
- **Local-workspace bias**: prefers `gh` where it helps (PR diff) but everything works with plain `git`.

---

## At-a-glance
**Core tools**:  
`help.index`, `help.tool`, `json.query`, `text.search`, `file.read`, `text.replace`,
`orchestration.agents.list`, `orchestration.tasks.list`, `orchestration.tasks.detail`,
`orchestration.tasks.assign`, `orchestration.tasks.status`, `orchestration.sprints.list`,
`git.status`, `git.diff`, `git.apply_patch`, `table.query`

**Environment requirements**
- PHP 8.2+, Laravel 10/11/12
- `jq` → for `json.query`
- `git` (and optionally `gh`) → for `git.*`
- `ext-pdo_sqlite` → for `table.query`

**Server identifier**: `tool-crate` (Laravel MCP registers it locally; communicate via stdio).

---

## Agent policy (discovery flow)
1. Call **`help.index`** with a small limit (e.g., 5–6).
2. If a tool looks right, call **`help.tool`** *once* to confirm its schema (only when needed).
3. Invoke the tool. Do **not** dump/expand every tool at startup.

### Discovery tools
- **`help.index { limit?: number }`**  
  Returns:
  - `recommended`: top tools (ordered from config)  
  - `categories`: `[ { category, tools[] } ]` (each tool has name/title/1‑liner/schema hint)  
  - `note`: follow-up hint (use `help.tool`)

- **`help.tool { name: string }`**  
  Returns concise details for one tool: `name`, `title`, `description`, `schema`, `hint`.

---

## Tool reference

### JSON / Text / Files
- **`json.query`** — jq wrapper  
  **Args**: `program` (jq), `json?`, `file?`, `raw?`, `slurp?`, `cwd?`  
  **Use** for extraction/transforms; add `cwd` if using `file`.

- **`orchestration.tasks.list`** — filter work items  
  **Args**: `sprint?[]`, `delegation_status?[]`, `status?[]`, `agent?`, `search?`, `limit?`  
  **Use** to surface task queues for agents or CLI prompts.

- **`orchestration.tasks.detail`** — inspect one work item  
  **Args**: `task`, `assignments_limit?`, `include_history?`  
  **Use** before high-touch operations to fetch history and assignment timeline.

- **`orchestration.tasks.assign`** — create assignment + status update  
  **Args**: `task`, `agent`, `status?`, `assignment_status?`, `note?`, `context?`  
  **Use** when routing a task to an agent; defaults to delegation status `assigned`.

- **`orchestration.tasks.status`** — change delegation status  
  **Args**: `task`, `status`, `assignment_status?`, `note?`, `assignments_limit?`, `include_history?`  
  **Use** to move tasks through `in_progress`, `blocked`, `completed`, etc., keeping the active assignment in sync.

- **`orchestration.agents.list`** — agent directory  
  **Args**: `status?[]`, `type?[]`, `mode?[]`, `search?`, `limit?`, `include?[]`  
  **Use** to select available agents/capabilities.

- **`orchestration.agents.detail`** — deep agent profile  
  **Args**: `agent`, `assignments_limit?`, `include_history?`  
  **Use** to review capabilities, metadata, and recent assignments before routing work.

- **`orchestration.agents.save`** — upsert agent profile  
  **Args**: `name?`, `slug?`, `type?`, `mode?`, `status?`, `capabilities?[]`, `constraints?[]`, `tools?[]`, `metadata?`, `upsert?`  
  **Use** when adding new templates or tweaking capabilities.

- **`orchestration.agents.status`** — toggle agent availability  
  **Args**: `agent`, `status` (`active|inactive|archived`)  
  **Use** to archive/activate agents without editing spreadsheets.

- **`orchestration.sprints.list`** — sprint rollups  
  **Args**: `code?[]`, `limit?`, `details?`, `tasks_limit?`  
  **Use** for dashboard-style summaries or when picking priority work.

- **`orchestration.sprints.detail`** — deep sprint snapshot  
  **Args**: `sprint`, `tasks_limit?`, `include_assignments?`  
  **Use** to review sprint meta, stats, and recent tasks before planning.

- **`orchestration.sprints.save`** — create/update sprint metadata  
  **Args**: `code`, `title?`, `priority?`, `status?`, `notes?[]`, `starts_on?`, `ends_on?`, `meta?`, `upsert?`  
  **Use** to seed new sprints or adjust cadence without editing files.

- **`orchestration.sprints.status`** — update sprint status label  
  **Args**: `sprint`, `status`, `note?`  
  **Use** to mark sprints as Planned/In Progress/Completed and capture context notes.

- **`orchestration.sprints.attach_tasks`** — add tasks to sprint  
  **Args**: `sprint`, `tasks[]`, `tasks_limit?`, `include_assignments?`  
  **Use** to re-group work items into the sprint backlog while keeping metadata consistent.

- **`text.search`** — grep-like search  
  **Args**: `pattern`, `fixed?`, `ignore_case?`, `paths?[]`, `text?`, `include_globs?[]`, `exclude_globs?[]`, `before_context?`, `after_context?`, `max_matches?`  
  Searches inline `text` or files under `paths`. Returns matches with optional context lines.

- **`file.read`** — safe file read  
  **Args**: `path`, `max_bytes?` (default 256 KB), `start?`, `end?`  
  Reads with a size cap; optionally returns a slice.

- **`text.replace`** — preview-only transform  
  **Args**: `pattern`, `replacement`, `text`, `regex?`, `global?`, `ignore_case?`  
  Returns **preview diff** and `updated_text`. (No file write — use your editor/patch flow.)

### Git
- **`git.status`** — working tree snapshot  
  **Args**: `cwd?`, `porcelain?` (v2), `include_untracked?`, `show_ignored?`  
  Returns `{ gh_detected: bool, output: string }` (porcelain v2 with `-z`).

- **`git.diff`** — unified diff  
  **Args**: `cwd?`, `range?` (e.g., `"HEAD~1..HEAD"`), `staged?`, `paths?[]`, `unified?`, `pr_number?`  
  Behavior:
  - If `pr_number` **and** `gh` exists → `gh pr diff {number}`.
  - Else → `git diff` with `range`/`staged`/`paths`.  
  Returns `{ source: "gh"|"git", diff: string }`.

- **`git.apply_patch`** — apply unified diffs (safe by default)  
  **Args**: `cwd?`, `patch`, `check_only?=true`, `three_way?=true`, `index?=false`  
  Defaults to dry‑run; set `check_only=false` to actually apply.

### Tables (CSV/TSV)
- **`table.query`** — in‑memory SQLite over CSV/TSV then `SELECT`  
  **Args**: `file`, `sql`, `delimiter?` (`auto|csv|tsv|char`), `header?`, `table?='t'`, `max_rows?`, `limit_output?`  
  Returns `{ table, columns[], rows[][], loaded_rows, delimiter }`.

---

## Agent execution heuristics
- **JSON** → `json.query` for structured reads/transforms. Prefer `raw=true` for scalars.
- **Search** → `text.search` with `paths` and context lines when preparing edits.
- **Read** → `file.read` for precise, capped fetches; increase `max_bytes` only if necessary.
- **Replace** → preview with `text.replace`; then convert to a patch and apply with `git.apply_patch`.
- **Git** →
  - Local changes: `git.status`, `git.diff` (`staged` or `paths`).
  - PR reviews: `git.diff { pr_number }` (auto‑uses `gh` if present).
  - Patch application: `git.apply_patch` with `check_only=true` first; only apply when clean.
- **Tables** → `table.query` for quick analysis; stick to `SELECT` (read‑only).

---

## Safety & idempotency
- Treat all tools as **read‑only** except `git.apply_patch` (which defaults to **check‑only**).
- Prefer patch‑based changes (`text.replace` → make diff → `git.apply_patch`) over ad‑hoc writes.
- Always include **`cwd`** for repo/file operations (monorepo safety).

---

## Error handling & retries
1. If a tool errors with "not found" (`jq`, `git`, `gh`), **downgrade gracefully** (e.g., skip `gh`, fall back to `git`).
2. Trim/correct arguments (bad `range`, invalid `paths`) and **retry once**.
3. For large outputs, **narrow scope** (`paths`, `before/after_context`, `limit_output`, smaller `unified`).

---

## Config knobs that affect agents
- `config/tool-crate.php`
  - `enabled_tools`: Only these are discoverable/usable (keeps discovery small).
  - `priority_tools`: Ranks tools for `help.index` → steers default choices.
  - `categories`: Presentation order & grouping in `help.index`.

---

## Example calls (copy/paste)

**Quick JSON slice from file**
```json
{ "name": "json.query", "arguments": { "program": ".packages[].name", "file": "composer.lock", "raw": true } }
```

**Search in app code**
```json
{ "name": "text.search", "arguments": { "pattern": "Route::", "paths": ["app","routes"], "ignore_case": true, "before_context": 1, "after_context": 1 } }
```

**PR diff via gh**
```json
{ "name": "git.diff", "arguments": { "cwd": "/repo", "pr_number": 42, "unified": 3 } }
```

**Patch (dry‑run first)**
```json
{ "name": "git.apply_patch", "arguments": { "cwd": "/repo", "patch": "<unified diff>", "check_only": true } }
```

**CSV query**
```json
{ "name": "table.query", "arguments": { "file": "storage/app/users.csv", "sql": "select name, count(*) c from t group by name order by c desc limit 20" } }
```

---

## Dev ergonomics (for humans)
- CLI parity for quick repro/debug:
  - `php artisan tool:jq …`
  - `php artisan tool:search …`
- Same code paths as MCP, so CLI behavior == agent behavior.

---

## Roadmap hooks for agents
- If plugins are added later (auto‑registration/attributes), keep the **help‑first** policy:
  `help.index` (small limit) → `help.tool` (one tool) → invoke.
- Consider adding a `domain?` filter to `help.index` so agents can request focused discovery (e.g., only `git` tools).
