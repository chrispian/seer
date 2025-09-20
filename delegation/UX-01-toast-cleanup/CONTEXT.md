# UX-01 Context

## Current State
- Toast components at `resources/views/components/error-toast.blade.php`, `success-toast.blade.php`, `undo-toast.blade.php` are presently empty (cleared during Flux experiments). Existing UI shows toasts via inline Blade snippets or Livewire `dispatchBrowserEvent` hooks.
- Styling relies on Tailwind (via CDN) plus custom palette (`resources/css/app.css`). Severity colours: hot pink, electric blue, neon cyan, etc.
- Toast triggers to examine:
  - Livewire components (`command-result`, `routing-rules-manager`).
  - Controllers / actions dispatching events (search failures, chaos capture).
  - Slash command handlers returning `shouldShowErrorToast` / `shouldShowSuccessToast`.

## Desired Experience
- Distinct severity categories: success (green/emerald), info (blue), warning (amber), error (rose).
- Toasts should slide/fade, include iconography, and be dismissible via mouse/keyboard.
- Users should be able to toggle verbosity (normal vs minimal) so routine success messages can be hidden.

## Technical Notes
- Livewire + Alpine are already in use; feel free to encapsulate toast behaviour in an Alpine store.
- If adding a user setting, ensure compatibility with existing auth (likely `users` table). Use guarded migration patterns and remember to backfill defaults.
- When adding CSS, keep code in `resources/css/app.css`; postcss build may not be configured, so use plain Tailwind classes or inline styles.

## Testing Guidance
- Pest tests live under `tests/Feature`. Create a feature test to toggle verbosity via HTTP or Livewire call and assert stored preference.
- Consider Dusk if interaction tests are needed (out of scope unless absolutely necessary).

## Out of Scope
- Do not reintroduce full Flux design language; maintain compatibility with current layout until the separate UI project launches.
- No requirement to change backend event payload shape; just ensure frontend respects them.
