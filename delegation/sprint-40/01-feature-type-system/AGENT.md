# Fragments Engine – Type System Sprint Agent Pack

**Owner:** Chrispian  
**Context:** Move forward with file‑based “Type Packs,” fast reads via generated columns/partial indexes, and a clear path to optionally split `state` (and other bulky JSON) into a separate table later.

---

## Mission Objectives (this sprint)

1) **Registry + Type Packs**
   - Implement a file‑based *Type Pack* system with a DB‑cached registry.
   - Ship a `todo` sample pack for validation.
   - Wire validation of `state` against JSON Schema per type.

2) **Indexing for Snappy Reads**
   - Add *generated columns* for per‑type hot fields.
   - Create *partial indexes* scoped by `type`.
   - Prepare a small *index suggestion generator* that reads `indexes.yaml` from Type Packs and outputs migration stubs.

3) **State Split Option (not required now)**
   - Provide a migration plan to split `state` to `fragment_states` if/when needed.
   - Keep **fragments** as the source of truth; any additional tables are read‑models or targeted joins.

4) **Read‑Models (Optional)**
   - Add projector stubs for `todo_read`, `event_read`, etc. as future work. Not required for launch.

---

## Deliverables to Produce

- Registry loader + cache table migration.
- Sample Type Pack (`todo`) + schema validation on write.
- Migration adding generated columns + partial indexes for `todo` hot fields (`status`, `due_at`).
- `artisan` commands:
  - `frag:type:make {slug}` – scaffolds a Type Pack.
  - `frag:type:cache` – rebuilds registry + prints index SQL suggestions.
  - `frag:type:validate {slug} {sample.json}` – validates a payload against schema.
- Optional: migration and code stubs for `fragment_states` table (split by FK).

---

## Execution Order (checklist)

1. **Registry Cache**
   - [ ] Create `fragment_type_registry` table.
   - [ ] Implement loader that merges app defaults + user overrides with precedence.
   - [ ] Cache minimal hydrated info per type (slug, version, min reqs, hot fields, schema hash).

2. **Validation on Write**
   - [ ] In `FragmentService@upsert`, resolve type ➜ validate `state` using cached schema.
   - [ ] On fail: return 422 with precise error paths.

3. **Indexing**
   - [ ] Add generated columns from `todo/indexes.yaml`:
         - `status` ← `state->status`
         - `due_at` ← `state->due_at`
   - [ ] Partial indexes (Postgres examples) for `type='todo'`.
   - [ ] Add utility to emit SQL from `indexes.yaml`.

4. **Commands**
   - [ ] `frag:type:make` – scaffold files from templates.
   - [ ] `frag:type:cache` – (re)build registry and print suggested indexes.
   - [ ] `frag:type:validate` – validate a local JSON sample.

5. **(Optional) State Split**
   - [ ] Create `fragment_states` (fragment_id PK/FK, state JSONB).
   - [ ] Backfill + switch read path under feature flag.
   - [ ] Ensure single, indexed join path keeps queries fast.

---

## Guardrails

- Fragments table remains **canonical**.
- File packs are canonical for definitions; DB is a **cache**.
- Keep enrichment **namespaced** in `metadata` (append‑only).
- Only expose *hot fields* as generated columns; keep base lean.
- Do not over-normalize prematurely.

---

## Quick Commands (planned)

```bash
php artisan make:migration create_fragment_type_registry_table
php artisan frag:type:make todo
php artisan frag:type:cache
php artisan frag:type:validate todo storage/samples/todo.json
```
