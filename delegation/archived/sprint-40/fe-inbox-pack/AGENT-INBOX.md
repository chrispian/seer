# Fragments Engine — Inbox / Review Agent Pack (MVP Backend)

**Goal:** Every new fragment lands in an **Inbox** for fast, deterministic review. Users can accept all, accept selected, or accept item-by-item. During review they can edit fields (tags, category, type, vault, edited_message). Future versions add rules and automation.

**Scope (v0.0.1 MVP):** Backend-only with clean APIs and Command Packs; minimal/no UI aside from a list pane placeholder later. Deterministic behavior (no model decisions during review).

**Owner:** Chrispian
**Dependencies:** Fragments table, Command Packs runner

---

## Success Criteria
- New fragments default to `inbox_status='pending'`, visible in Inbox list.
- Deterministic **Accept** flow updates selected fields and sets `inbox_status='accepted'`.
- Bulk operations: Accept All, Accept Selected (ids).
- Fast sort/filter by type, tags, category, vault, created/captured/date.
- Edits captured in `edited_message` and persisted to fragment fields.
- Clear audit trail: who accepted, when, what changed.
