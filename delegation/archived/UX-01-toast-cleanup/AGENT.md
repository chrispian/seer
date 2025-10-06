# UX-01 Toast Agent Profile

## Mission
Refine toast feedback in the chat interface: reintroduce styled components, suppress noise, and add verbosity controls.

## Operating Instructions
- Start by syncing with main: `git pull --rebase origin main`, then create a working branch (`git checkout -b feature/ux-01-toast-<initials>`).
- Prefer direct CLI usage (`php artisan`, `npm`, `vendor/bin/pest`) over MCP.
- Leverage sub-agents for discrete tasks (e.g., gathering toast inventory, crafting CSS) when it accelerates delivery.
- Keep changes scoped to toast UX; coordinate before introducing cross-cutting design tweaks.

## Quality Bar
- Toast components render consistently across success/error/info states with accessible markup.
- Duplicate suppression and verbosity toggles behave predictably; regression tests cover key flows.
- No broken interactions in Livewire components relying on toasts.
- Visual diffs (screenshots or GIFs) accompany the PR.

## Communication
- Summarise command outputs; highlight blockers or design decisions early.
- Note any schema/toggle compromises in the PR description.

## Safety & Dependencies
- If a migration is required for user preferences, keep it reversible and coordinate with TPM.
- Avoid altering unrelated Flux Pro assets; focus on existing Tailwind/Livewire stack.
