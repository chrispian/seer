# UX-01 Toast Cleanup — Implementation Plan

## Objective
Reduce toast noise in the chat interface by consolidating redundant messages, introducing severity cues, and making verbosity configurable per user.

## Deliverables
- Toast component refactor with consistent API (success, warning, error, info).
- Severity-based styling (icons/colours) aligned with current design system.
- Logic to suppress duplicate success toasts triggered in quick succession.
- User-level verbosity preference stored in DB (or config toggle) with UI to adjust.
- Updated documentation on toast usage guidelines.

## Work Breakdown
1. **Branch Prep**
   - `git pull --rebase origin main`
   - `git checkout -b feature/ux-01-toast-cleanup`
2. **Audit & Requirements Confirmation**
   - Inventory current toast triggers (Livewire components, controllers, commands).
   - Confirm desired severity mapping and default verbosity with TPM/design.
3. **Component Refactor**
   - Replace empty placeholder components (`resources/views/components/*-toast.blade.php`) with unified partial accepting props: `variant`, `title`, `message`, `actions`.
   - Ensure compatibility with both Flux-based panels and legacy Blade usage.
4. **Styling & Icons**
   - Add severity-specific classes in `resources/css/app.css` (reuse existing palette: hot pink, electric blue, etc.).
   - Include accessible aria tags and keyboard-dismiss support.
5. **Verbosity Controls**
   - Decide storage: user settings table or JSON column on users. Add migration if needed (coordinate if schema change).
   - Expose toggle via profile/settings panel; default to “normal”.
   - Update toast dispatcher to respect verbosity (e.g., suppress info toasts in “minimal” mode).
6. **Duplicate Suppression**
   - Implement debounce per toast key (session-based cache or Livewire store) to avoid spamming identical success toasts.
7. **Testing**
   - Add feature tests covering verbosity toggle and sample toast rendering.
   - Include unit tests for suppression helper if built in PHP.
8. **Docs & Cleanup**
   - Update `docs/` or README with toast severity guide and verbosity instructions.
   - Mark related TODOs in `PROJECT_PLAN.md` as complete.
   - Run tests/lints, clean diff, and commit.
9. **Handoff**
   - `git status`, push branch, open PR with before/after screenshots/GIFs.

## Acceptance Criteria
- Toasts render with clear severity cues and accessible markup.
- Duplicate success toasts do not appear back-to-back.
- User verbosity setting persists and toggles toast visibility appropriately.
- No regressions in existing Livewire flows (recall palette, routing panel, etc.).

## Notes & Risks
- Schema change requires coordination; if out of scope, propose alternative (per-session setting) and flag for follow-up.
- Watch for interplay with Flux modals; ensure styles don’t conflict.
- Keep design consistent with planned Flux Pro revamp to ease future migration.
