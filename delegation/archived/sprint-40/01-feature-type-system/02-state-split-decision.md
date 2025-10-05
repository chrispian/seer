# Decision Note: Splitting `state` to a Separate Table

## Short Answer
- **You can safely keep `state` in `fragments` now** and remain fast using generated columns + partial indexes.
- If/when we need it, **splitting `state`** into `fragment_states` is a **contained, low‑risk migration** with a fast 1:1 join.

## Why keep it inline (now)
- Simpler writes and transactions.
- Single source of truth.
- Index only what matters via generated columns.
- Avoids premature normalization and migration churn.

## When to split
- `fragments` row size grows enough to impact cache/IO.
- You need **different storage** or **compression** strategies for `state`.
- You want **row‑level security** or partial encryption on `state` separately.
- Extremely write‑hot workloads competing on the same page/heap.

## Split Plan (Postgres example)
1. **Create table**
```sql
CREATE TABLE fragment_states (
  fragment_id UUID PRIMARY KEY REFERENCES fragments(id) ON DELETE CASCADE,
  state JSONB NOT NULL,
  state_hash TEXT GENERATED ALWAYS AS (md5(state::text)) STORED
);
CREATE INDEX idx_fragment_states_gin ON fragment_states USING GIN (state);
```
2. **Backfill**
```sql
INSERT INTO fragment_states (fragment_id, state)
SELECT id, state FROM fragments WHERE state IS NOT NULL;
```
3. **Switch reads (feature-flag)**
- Read path: prefer join on `fragment_states` when enabled.
- Write path: write to both during migration window; finalize by dropping `fragments.state` when stable.

4. **Keep the fast path**
- For hot fields (`status`, `due_at`), still expose **generated columns on fragments** backed by triggers or by duplicating those values on write to keep partial indexes in the main table blazing.

## Other candidates to externalize (later)
- **`rich_text`** (large payloads).
- **`metadata`** (append‑only, can get large; consider `fragment_metadata` with namespaced rows).
- **`attachments`** (already URIs; keep as a table if you need relations/ACLs).

## Net
Your logic is correct: it’s a simple path with a fast join. Let’s **not split now**, but we have a crisp migration when/if metrics demand it.
