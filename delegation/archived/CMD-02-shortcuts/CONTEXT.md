# CMD-02 Context

## Current State
- Some shortcuts exist within the chat UI (e.g., focusing input) but are hard-coded and undocumented.
- Livewire/Alpine are used; keyboard handling may be scattered.
- No central overlay lists available shortcuts.

## Desired Outcome
- Cohesive shortcut map enhancing navigation, recall, and capture workflows.
- Discoverability overlay accessible via keyboard/mouse.
- Accessible interactions (ARIA, focus management) ready for future UI refactors.

## Key Files
- `resources/views/filament/resources/fragment-resource/pages/chat-interface.blade.php`
- Livewire components handling key events (`command-result`, `routing-rules-manager`, etc.).
- JS utilities (if any) in `resources/js/` or inline scripts.
- Styles in `resources/css/app.css`.

## Considerations
- Ensure shortcuts can be toggled or disabled if conflicts arise; at minimum, avoid overriding browser/system defaults.
- Account for users on various keyboard layouts; provide alternatives or allow remapping later (document assumption).
- Keep modifications compatible with existing toasts/panels.

## Dependencies
- Works alongside CMD-01 outputs; ensure new commands integrate smoothly with shortcuts.
- Watch interactions with UX-01 toast verbosity and Flux panels.

## Definition of Done
- See PLAN acceptance criteria; confirm with manual smoke and documentation updates before hand-off.
