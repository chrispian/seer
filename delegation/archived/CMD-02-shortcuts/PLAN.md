# CMD-02 Keyboard Shortcuts — Implementation Plan

## Objective
Audit and expand keyboard shortcuts for the chat UI, adding a discoverable, accessible shortcut map and consistent handling across Livewire components.

## Deliverables
- Shortcut inventory document and conflict resolution plan.
- Implementation of new shortcuts covering recall palette, command launcher, capture/composer, navigation between sessions/panels.
- Discoverability overlay/modal showing shortcuts (invoked via `?` or command palette).
- Accessibility compliance (ARIA labels, focus management).
- Tests (JS/Livewire integration where possible) and updated documentation.

## Work Breakdown
1. **Branch Prep**
   - `git fetch origin`; `git pull --rebase origin main` (report sandbox restrictions if needed).
   - `git checkout -b feature/cmd-02-shortcuts`.
2. **Audit**
   - Inventory existing shortcuts (Blade templates, JS helpers) and note conflicts.
   - Gather desired shortcuts (recall, capture, command palette, navigation, toggles).
3. **Design Shortcut Map**
   - Collaborate with design/PM for final key assignments; document in the plan.
4. **Implementation**
   - Add JS/Alpine handlers or Livewire listeners to capture shortcuts.
   - Ensure shortcuts respect current context (e.g., avoid interfering with text inputs).
   - Implement discoverability overlay (modal or panel) listing shortcuts.
5. **Accessibility**
   - Provide screen-reader announcements when overlays open.
   - Ensure focus trapping and ESC handling work correctly.
   - Document shortcuts in README/docs.
6. **Testing**
   - Add frontend integration tests if feasible (e.g., Laravel Dusk) or Livewire tests mimicking key events.
   - Manual QA across browsers.
7. **Documentation**
   - Update docs/command references with shortcut list.
   - Add tooltip/help entry in UI (e.g., footer or command palette).
8. **Handoff**
   - Run test suites; capture manual QA notes.
   - Push branch, open PR with demo GIF of shortcuts/overlay.

## Acceptance Criteria
- Shortcut map implemented and conflict-free.
- Discoverability overlay accessible via keyboard and mouse.
- Shortcuts respect Livewire contexts and do not break text inputs.
- Documentation updated and tests pass.

## Risks & Notes
- Avoid global listeners that interfere with inputs; scope events appropriately.
- Dusk tests may be heavy; if skipped, document manual QA thoroughly.
- Coordinate with future Flux redesign to ensure shortcuts remain compatible.
