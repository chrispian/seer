# Indexing for Inbox

- BTREE on `inbox_status`, `inbox_at`, `type`.
- Composite partial: `(type, inbox_at)` where `inbox_status='pending'` for fast type tabs.
- If tags are array/jsonb: GIN index for tag filters.
- Keep result windows small with cursor pagination to keep UI snappy.
