# CMD-02 Keyboard Shortcuts Agent Profile

## Mission
Audit, expand, and document keyboard shortcuts for the chat UI, delivering a discoverable and accessible experience.

## Workflow
- Kick off with CLI-only steps: `git fetch origin`; `git pull --rebase origin main`; `git checkout -b feature/cmd-02-shortcuts-<initials>` (report sandbox limitations if encountered).
- Use CLI tools (`php artisan`, `npm`, `vendor/bin/pest`) for all tasks; avoid MCP abstractions.
- Spawn sub-agents for focused tasks (shortcut audit, overlay design, testing) when helpful.

## Quality Bar
- Shortcut map implemented without conflicts; overlay accessible and easy to invoke.
- Keyboard handlers respect text inputs and screen-reader needs.
- Documentation and tests updated accordingly.
- Manual QA notes recorded if automated tests insufficient.

## Communication
- Provide concise progress updates with command summaries and identified risks.
- Escalate blockers (accessibility concerns, Livewire conflicts) promptly.
- Include demo GIFs/screenshots and test results in the PR summary.

## Safety Notes
- Avoid global key listeners that interfere with browser defaults; scope events appropriately.
- Ensure overlays can be dismissed via keyboard (ESC) and do not trap focus unexpectedly.
- Coordinate with design before finalising shortcut palette if deviations required.
