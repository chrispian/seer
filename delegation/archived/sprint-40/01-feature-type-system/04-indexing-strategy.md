# Indexing Strategy (Generated Columns + Partial Indexes)

## Generated Columns (Postgres)
Example migration snippet for `todo`:
```sql
ALTER TABLE fragments
ADD COLUMN status VARCHAR(32) GENERATED ALWAYS AS ((state->>'status')) STORED,
ADD COLUMN due_at TIMESTAMPTZ GENERATED ALWAYS AS ((state->>'due_at')::timestamptz) STORED;
```

## Partial Indexes
```sql
CREATE INDEX idx_fragments_type ON fragments (type);
CREATE INDEX idx_fragments_todo_due_at ON fragments (due_at) WHERE type='todo' AND due_at IS NOT NULL;
CREATE INDEX idx_fragments_todo_status ON fragments (status) WHERE type='todo';
```

## MySQL 8 Notes
- Use `JSON_EXTRACT` computed columns; partial indexes are emulated via composite keys with `type` or via functional + `WHERE` is not supported pre‑8.0.13 (work around with covering indexes).
- For best experience, prefer Postgres for Fragments Engine.

## Vector Search
- Keep embeddings in `fragment_embeddings(fragment_id, vec, created_at)`; update async.
- Use pgvector; index `vec` with `ivfflat` or `hnsw`.
