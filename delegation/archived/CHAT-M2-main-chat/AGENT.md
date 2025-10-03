# CHAT-M2 Main Chat Agent

## Mission
Upgrade the center chat experience to production quality by replacing the textarea composer with a TipTap-powered editor (slash commands, wiki links, hashtags, file drop/paste, optional voice input) while keeping streaming/persistence intact and adding per-message actions (copy, bookmark, delete).

## Getting Started
1. `git fetch origin`
2. `git checkout -b feature/chat-m2-main`
3. Install deps if needed: `composer install`, `npm install`
4. Run the app locally (`php artisan serve`, `npm run dev`) to verify baseline chat behavior.

## Key Context
- Current UI mount point: `resources/js/islands/chat/ChatIsland.tsx`
- Streaming + persistence already wired via `POST /api/messages` and SSE `GET /api/chat/stream/{messageId}`
- Markdown rendering: `react-markdown` + `remark-gfm`
- TipTap packages available via `package.json` (ensure versions align)
- Artifact uploads: confirm whether `POST /api/files` exists; create lightweight controller if missing
- Message actions should hit existing fragment endpoints (bookmark/delete) or add new routes if needed

## Deliverables
- New `ChatComposer.tsx` (TipTap) mounted within `ChatIsland`, supporting:
  - Placeholder text, history (undo/redo)
  - Slash command palette using shadcn Command, wired to `/api/autocomplete/commands`
  - Wiki links (`[[...]]`) suggestions using `/api/autocomplete/fragments`
  - Hashtag suggestions with pill rendering in-editor (serialize as `#tag`)
  - File drop/paste uploading via `POST /api/files` and inserting markdown references
  - Optional mic button leveraging Web Speech API (feature-flag if unsupported)
  - Markdown submission via Cmd/Ctrl+Enter or Send button
- Update send flow to extract markdown via TipTap markdown extension and POST to `/api/messages`
- Implement per-message actions (copy to clipboard, bookmark toggle, delete) in transcript component(s)
- Ensure optimistic rendering + streaming remain stable (user bubble appears instantly, assistant streams)
- Tests: Pest feature coverage for `/api/files` (if new) and `/api/messages` payloads, plus React-level tests (Vite/test runner) for composer utilities if present

## Definition of Done
- Full composer experience matches spec (slash, wiki, tags, uploads, voice optional)
- Message actions function against real endpoints with UI feedback
- Streaming/persistence unchanged; QA items for composer pass
- `npm run build` and `composer test` clean before PR request; include screenshots/gifs and test output in PR notes
