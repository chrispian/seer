# Deterministic Review Flow (MVP)

1. **Capture**: new fragment is inserted with `inbox_status='pending'`, `inbox_at=now()`.
2. **List**: Inbox API returns filtered/sorted pending items.
3. **Edit-in-place** (optional): user writes into `edited_message` to update tags/category/type/vault/etc.
4. **Accept**:
   - Apply any edits (`tags`, `category`, `type`, `vault`, field diffs).
   - Clear `edited_message` (or persist as system note).
   - Set `inbox_status='accepted'`, `reviewed_at`, `reviewed_by`.
   - Emit `FragmentAccepted` domain event (for search/projector refresh).
5. **Archive/Skip**:
   - Set `inbox_status='archived'` (or `skipped`), retain `edited_message` if provided as a note.
6. **Bulk**: server re-applies current filters to fetch ids; processes in batches (e.g., 500).

### Sorting defaults
- Default: `inbox_status='pending'` sorted by `inbox_at desc`.
- Secondary: `type asc`, then `created_at desc`.

### Filters
- `type in (todo, event, link, document, log, contact, media, ai_response)`
- `tags has ANY (...)`
- `category = ?`  
- `vault = ?`
