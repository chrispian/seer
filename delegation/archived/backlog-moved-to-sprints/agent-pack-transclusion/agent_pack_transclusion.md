# Agent Pack — Transclusion Command (`/include`)

**Status:** Draft v1 (finalized for MVP) · **Scope:** Workspace-first · **Editor:** TipTap · **Storage:** MD + JSON

---

## 1) Purpose
Allow authors to pull other fragments into the current fragment **by reference (live transclusion)** or **by copy (materialize content)** while editing or creating. This improves reuse, keeps deterministic links, and supports Obsidian‑style workflows.

---

## 2) Command Summary

### Slash commands (TipTap menu + inline)
- **Primary:** `/include` (alias: `/inc`)
- **Targets:** by **UID**, by **search/query**, or by **type filters**.
- **Modes:** `ref` (reference/transclude) · `copy` (materialize)

**Common forms**
- `/include uid:fe:note/7Q2M…` → embed that fragment (default `mode:ref`).
- `/include "rendered state cosmology" type:note mode:ref` → search → pick → embed.
- `/include query:"type:todo where:done=false sort:due limit:5" layout:checklist` → insert a **list transclusion block**.
- `/include uid:fe:todo/AB12 mode:copy` → copy the todo and link back.

> Inline overrides: `@ws:…`, `@proj:…` apply to the lookup context (do not change active context).

---

## 3) Modes & Behavior

### A) Reference (live transclusion)
- Inserts a **Transclusion Node** (TipTap) that renders target content live.
- The host fragment stores a **TransclusionSpec** (see §6) and a **stable link** to the target’s UID.
- **Edits are applied to the canonical record** (e.g., checking a todo box updates `fe:todo/…`).
- Supports **single-item** and **list/query** forms.

### B) Copy (materialize)
- Clones the current canonical content **into the host** as plain Markdown and creates a `relation` back to the source UID.
- Edits affect **only the host** unless the user chooses **Sync-Back** (disabled by default).
- For todos, copied checkboxes become **scoped to the host fragment** (new UIDs).

---

## 4) Linking Model

### UIDs
- Format: `fe:<type>/<base62>` (e.g., `fe:note/7Q2M9K`, `fe:todo/AB12CD`).
- Stored in both records and mirrors (MD), enabling round‑trip.

### Inline link & embed syntax in Markdown
- **Inline link:** `[[fe:note/7Q2M9K|Optional Title]]`
- **Block embed (single-item):** `![[fe:note/7Q2M9K]]`
- **List/query embed:** fenced block (see §5):
  ```fragments
  context: ws:work proj:Fragments
  source: type:todo where:done=false sort:due limit:20
  layout: checklist
  mode: live
  ```
- **Todo anchors (mirroring):** add a stable tail marker:  
  `- [ ] Title (due: 2025-10-15) ^fe:todo/AB12CD`

### Relationship fields (canonical)
- `links: [ { rel: "includes", to: "fe:note/7Q2M9K" } ]`
- Copy mode adds `{ rel: "copied-from", to: "fe:note/7Q2M9K" }`.

---

## 5) Transclusion Blocks (list/query)

**Fenced block label:** `fragments`

**Keys**
- `context`: optional; e.g., `ws:work proj:Fragments` (inherits active Context Stack if omitted)
- `source`: mini-query string: `type:todo tag:#work where:done=false sort:due asc limit:20`
- `layout`: `checklist | table | cards (bento/gallery/post) | kanban` (MVP: `checklist`, `table`, `cards`)
- `columns`: array for `table` (e.g., `["title","due","priority","tags"]`)
- `template`: optional per-item override (falls back to TypeDef `output_template`)
- `mode`: `live | snapshot` (MVP default: `live`)
- `empty`: string to render when no results

**Behavior**
- Renderer replaces block with live results. Without renderer, the fenced block remains portable MD.
- In `snapshot` mode, engine writes static MD and stores a hidden refresh token for manual updates.

---

## 6) TransclusionSpec (TipTap node + storage)

**Transclusion Node attributes (TipTap)**
```json
{
  "kind": "single" | "list",
  "mode": "ref" | "copy" | "live" | "snapshot",
  "uid": "fe:note/7Q2M9K",
  "query": "type:todo where:done=false sort:due",
  "context": { "ws": "work", "proj": "Fragments" },
  "layout": "block" | "inline" | "checklist" | "table" | "cards",
  "columns": ["title","due","priority"],
  "mirrorTodos": true,
  "readonly": false,
  "createdAt": 1690000000,
  "updatedAt": 1690001000
}
```

**Markdown serialization**
- **single, ref:** `![[fe:note/7Q2M9K]]`
- **single, copy:** plain MD pasted + hidden link comment: `<!-- copied-from: fe:note/7Q2M9K -->`
- **list:** fenced block as in §5

**JSON companion (stored alongside MD)**
- A per-fragment `.json` file persists the TransclusionSpec array under `transclusions: [...]` for deterministic rehydrate.

---

## 7) Todo Handling

### Live Mirror (reference mode)
- Checking a box in the host updates the **canonical todo** (`fe:todo/…`).
- The inline MD shows `- [x]` and preserves the `^fe:todo/…` marker.

### Copy Mode
- Todos are materialized with **new UIDs**; the host becomes canonical for those copies.
- The engine writes `{ rel: "copied-from" }` so suggestions can offer a one‑time **Sync-Back** rule if repeated.

### List Blocks (checklists)
- Each item renders with its checkbox.
- Bulk actions in the rendered view update each canonical item.
- Optional `scope` flag for advanced users later (`scope: host | canonical`), default `canonical`.

---

## 8) Rules Engine Interaction

- Rules may **add or rewrite** transclusions (e.g., convert raw pasted link → `/include` by domain).
- When users repeatedly convert the same domains or keywords into includes, surface **Suggested Rules** (one‑click add):
  - Example suggestion: *“Links from `laravel-news.com` → auto‑include as bookmark ref in workspace:personal, skip inbox, tags: newsletter, laravel.”*
- Applying rules never mutates the target canonical content—only the host’s transclusion spec or copied material.

---

## 9) Agent Responsibilities (deterministic)

1. **Parse** `/include` command → resolve **target** (UID or search dialog) and **mode**.
2. **Authoring Context**: derive Context Stack (workspace → project → session) + adhoc overrides.
3. **Insert** appropriate TipTap node and serialize to MD/JSON (see §6).
4. **Render**: fetch canonical(s) and provide a read‑through view; handle permissions.
5. **Mutations**: if `mode:ref`, write back to canonical on edits; if `mode:copy`, write only to host.
6. **Conflicts**: if target changed since render, show stale badge and offer refresh.
7. **Fallbacks**: if offline/LLM unavailable, everything still works (purely deterministic paths).

---

## 10) Error Handling & Edge Cases

- **Missing UID**: show inline error chip; offer search.
- **Permission denied**: render redacted preview; forbid mutation.
- **Circular reference**: detect and collapse to a link with warning.
- **Deleted target**: keep the node, mark as orphan; offer to remove or restore from snapshot (if available).
- **Schema drift**: if TypeDef changed, re‑render using latest `output_template`; log a soft warning.

---

## 11) TipTap Integration (MVP)

- **Slash menu entries**
  - *Include by UID* → prompt UID
  - *Include by search* → opens picker (type filters, tags, recent)
  - *Include list (query)* → inserts a prefilled fenced block, cursor in `source:`
  - *Copy content from…* → same picker, `mode:copy`

- **Node schema**: `transclusion` with attributes in §6; custom renderers for inline vs block.
- **Layouts supported (MVP)**: `checklist`, `table`, `cards` (cards supports bento/gallery/post variants via per-item `template`).
- **Paste handler**: when a `[[fe:…]]` or fenced `fragments` block is pasted, auto‑hydrate to the node.
- **Keyboard**: Enter toggles edit of query; Space on checklist toggles item; Cmd/Ctrl‑R refreshes snapshot.

---

## 12) Storage Layout (per fragment)
```
/fragments/
  <uid>/
    index.md           # Markdown, includes embeds/blocks
    index.json         # Canonical fragment (type/state/metadata)
    transclusions.json # Array of TransclusionSpec
```

Transclusion targets remain in their own canonical locations. Copy mode writes raw MD into `index.md` only and logs `copied-from` in `index.json`.

---

## 13) Examples

### 13.1 Single include (reference)
Slash: `/include uid:fe:note/7Q2M9K`

MD:
```
![[fe:note/7Q2M9K]]
```

### 13.2 List include (open todos in project)
Slash: `/include query:"type:todo where:done=false sort:due limit:10" layout:checklist @proj:Fragments`

MD:
```fragments
context: proj:Fragments
source: type:todo where:done=false sort:due limit:10
layout: checklist
mode: live
```

### 13.3 Copy a snippet
Slash: `/include uid:fe:note/CL42Z mode:copy`

MD result contains the pasted text and:
```
<!-- copied-from: fe:note/CL42Z -->
```

### 13.4 Inline todo mirror
MD line:
```
- [ ] Draft agent pack intro ^fe:todo/AB12CD
```

Checking this updates `fe:todo/AB12CD`.

---

## 14) Minimal `/help include` entry
```
/include [uid:<fe:…> | "search terms" | query:"…"] [mode:ref|copy] [layout:…] [@ws:… @proj:…]

Examples:
- /include uid:fe:note/7Q2M9K
- /include "laravel queue" type:bookmark mode:ref
- /include query:"type:todo where:done=false" layout:checklist @proj:Fragments
- /include uid:fe:todo/AB12 mode:copy
```

---

## 15) Acceptance Criteria (MVP)
- Insert single-item ref by UID; renders live; edits write back to canonical.
- Insert list block from query; **checklist** toggles update canonicals; **cards** layout renders with per-item templates and respects canonical updates.
- Copy mode produces plain MD + `copied-from` relation; no back‑writes.
- MD ↔ TipTap round‑trip preserves UIDs and specs.
- Context overrides apply to lookup/render only; do not change active context.
- Works offline without AI; no dependence on enrichment.

---

## 16) Decisions & Remaining Open Questions

### Decisions (confirmed)
1. **Obsidian‑friendly embeds**: Use `![[fe:UID]]` for single includes; keep inline UID markers for todos (`^fe:todo/…`).
2. **MVP layouts**: Support `checklist`, `table`, and `cards` (bento/gallery/post via `template`).
3. **Global preference**: `transclusion.autoPreferCopyAcrossWorkspaces = true` (default). When including from external workspaces, **copy** is preferred unless user flips per‑action or per‑workspace.
4. **Cross‑workspace includes policy**: **Opt‑in per workspace** (safe default). A workspace must explicitly allow being referenced as a source. Supports allowlist, one‑time ephemeral allow, and global "trust this workspace". Admins can switch to allow‑by‑default.

### Configuration keys (initial)
```yaml
features:
  obsidianFriendlyEmbeds: true
transclusion:
  autoPreferCopyAcrossWorkspaces: true
  crossWorkspaceIncludes:
    mode: opt-in              # opt-in | allow-by-default
    allowlist: []             # list of workspace slugs/ids
    ephemeralAllows: true     # permit one-off includes via prompt
```

### Remaining Open Questions
- None for MVP. (We can revisit per‑workspace rate limits/quotas or audit trails when we wire permissions.)
