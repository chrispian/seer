# Fragments Engine — Slash Commands Agent Pack

**Scope:** Backend-only MVP (no UI) for a file-based Slash Command system with a DB-backed registry cache and a YAML DSL runner. Deliver core commands as packs (`/todo`, `/note`, `/link`, `/recall`, `/search`).

**Owner:** Chrispian | **Version Plan:** 0.0.1 (MVP backend), 0.0.2 (enhancements / nice-to-haves, still backend-first), UI in a separate sprint.

---

## Mission Summary

- Implement **Command Packs** (files), a **Registry Cache** (DB), and a **DSL Runner** with a small set of step types: `transform`, `ai.generate`, `fragment.create`, `search.query`, `notify`, `tool.call` (whitelisted).
- Provide **artisan** commands to scaffold and manage packs:
  - `frag:command:make {slug}` (scaffold)
  - `frag:command:cache` (rebuild registry & verify)
  - `frag:command:test {slug} {sample?} --dry` (execute locally with debug)
- Ship 5 **built-in packs** (file-first, overrideable).

---

## Deliverables (MVP v0.0.1)

1. **Filesystem Layout**
   ```
   fragments/
     commands/
       todo/...
       note/...
       link/...
       recall/...
       search/...
   storage/fragments/commands/   # user overrides (not in MVP UI)
   ```

2. **DB: command_registry**
   - `slug` (unique), `version`, `source_path`, `steps_hash`, `capabilities` (json), `requires_secrets` (json), `reserved` (bool), `created_at`, `updated_at`.

3. **DSL Runner (backend service)**
   - Loads command by slug → validates manifest → executes step sequence.
   - Context sources: `ctx.body`, `ctx.selection`, `ctx.user`, `ctx.workspace`, `ctx.session`, `ctx.now`.
   - Merge tags + filters: `trim, slug, lower, upper, default:x, take:n, date:ISO8601, jsonpath:$.foo`.
   - Step outputs typed: `text|json|void`. Errors halt with structured diagnostics.

4. **Built-in Command Packs**
   - `/todo` → create `todo` fragment (AI parser optional via feature flag).
   - `/note` → create `document` fragment.
   - `/link` → extract URL, basic meta (optional enrichment hook).
   - `/recall` → recall/search results.
   - `/search` → broader search across types/tags.

5. **Artisan Commands**
   - `frag:command:make {slug}` → scaffold a pack.
   - `frag:command:cache` → rebuild registry, print summary & warnings.
   - `frag:command:test {slug} {sample?} --dry` → run command in sandbox, print step-by-step output.

---

## v0.0.2 Enhancements (Nice-to-haves)

- **Dry-run visualizer** (CLI): pretty tree of steps + resolved templates.
- **Additional step types:** `http.request` (safe allowlist), `fragment.update`, `emit.event` (internal bus).
- **Secrets broker**: declare `requires.secrets` and surface missing keys gracefully.
- **Command-level policies:** rate limit, timeouts per step, retry policy for `ai.generate`/`http.request`.
- **Golden fixtures** for regression testing: `samples/*.in`, `samples/*.out.json`.
- **TS Handlers (experiment flag)**: optional `handler.ts` sandbox with explicit capability gating.

See `04-mvp-v0.0.1-plan.md` and `05-v0.0.2-plan.md` for task breakdowns.
