# CMD-01 Slash Command Expansion — Implementation Plan

## Objective
Design and implement the next set of slash commands (`/vault`, `/project`, `/session`, `/context`, `/inbox`, `/compose`) with consistent UX, validation, and test coverage.

## Deliverables
- Command definitions registered in `CommandRegistry` with aliases and help text.
- Handler classes implementing each command’s behaviour:
  - `/vault` & `/project`: switch context, optionally create/list.
  - `/session`: manage chat sessions (start/end/list).
  - `/context`: display or update current working context (vault/project/session/model).
  - `/inbox`: surface pending fragments/tasks.
  - `/compose`: open rich composer panel for multi-step capture.
- Flux/Livewire panels or toasts for each command with clear messaging.
- Validation and error handling (unknown vaults, missing context, etc.).
- Tests (unit + feature) covering successful paths and common failures.
- Updated documentation (command reference in docs/README).

## Work Breakdown
1. **Branch Prep**
   - `git fetch origin` (notify TPM if blocked) and `git pull --rebase origin main`.
   - `git checkout -b feature/cmd-01-command-expansion`.
2. **Command Specs**
   - Review existing command patterns (e.g., `SearchCommand`, `TodoCommand`).
   - Draft UX copy, argument structure, and expected responses for each new command.
3. **Registry Updates**
   - Add commands/aliases to `App\Services\CommandRegistry` with descriptive comments.
   - Update help/`/help` output to include new entries.
4. **Handler Implementation**
   - Create action classes under `App\Actions\Commands\`.
   - Ensure commands return `CommandResponse` with appropriate panel/toast data.
   - Reuse existing services (vault/project services) where possible; add new ones if necessary.
5. **UI Integration**
   - Build necessary Flux/Livewire panels or reuse existing components (e.g., context selector, inbox list).
   - Ensure commands respect new toast verbosity settings.
6. **Validation & Errors**
   - Handle missing arguments, unauthorized actions, and invalid IDs gracefully.
   - Provide actionable feedback to the user.
7. **Testing**
   - Add unit tests for handlers (input → response).
   - Feature tests hitting Livewire/command endpoints to ensure panel data renders correctly.
   - Update `/help` test expectations.
8. **Docs & Comms**
   - Update command reference docs with usage examples.
   - Mark completion in `PROJECT_PLAN.md`.
9. **Handoff**
   - Run `vendor/bin/pest` (all suites) and share results.
   - Push branch, open PR summarising new commands with screenshots/GIFs of panels where relevant.

## Acceptance Criteria
- All new commands available via slash syntax with discoverable help entries.
- UI interactions feel consistent with existing command responses.
- Tests cover success/error cases; suite remains green.
- Documentation reflects new capabilities.

## Risks & Notes
- Be mindful of context switching; ensure state updates across Livewire components.
- `/compose` may require richer UI – coordinate scope to avoid multi-day rabbit holes.
- If dependencies on future projects appear, flag with TPM for follow-up deferral.
