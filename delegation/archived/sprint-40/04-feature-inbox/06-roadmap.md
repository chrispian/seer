# Roadmap

## v0.0.1 (this sprint)
- Fields & indexes.
- Endpoints for list/accept/archive/reopen (bulk safe).
- Command Packs: `/inbox`, `/accept`, `/archive`, `/tag`.
- Deterministic behavior only; no model-transform on accept.

## v0.0.2 (next sprint)
- **User Rules**: pre-sort/skip rules (gmail-like) with scopes (workspace/project/user).
- **Automation**: suggestion prompts (e.g., propose tags/type), one-click apply.
- **Keyboard-first UX**: j/k nav, `a` accept, `e` edit, `#` tag, `v` change vault.
- **Quick Actions**: “Accept & tag +dev”, “Accept & set type=todo”, “Accept & move to Project X”.
- **Undo/Trash**: soft revert to `pending` with full diff.

## Later
- **Confidence-based queues**: high-confidence enrichments flip to `accepted` with banner.
- **Learning rules**: convert repeated manual steps into suggested rules.
- **Multi-select sidebar** with aggregate edits applied across items.
