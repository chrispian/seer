# CMD-01 Context

## Existing Command System
- Commands implement `HandlesCommand` and return `CommandResponse` objects (see `SearchCommand`, `TodoCommand`, `RoutingCommand`).
- `CommandRegistry` maps command strings to handlers; `/help` references this list.
- UI responses appear via Livewire components (`command-result`, Flux panels) and toasts; ensure messages align with new verbosity settings.

## Desired Experiences
- `/vault` and `/project`: allow switching active context and optionally listing/creating items.
- `/session`: start/end/list chat sessions; integrate with `ChatSession` model.
- `/context`: show current vault/project/session/model metadata; allow quick adjustments.
- `/inbox`: surface queued fragments or tasks (use existing data structures, e.g., bookmarked/pending fragments).
- `/compose`: open a compose panel supporting multi-line capture (may reuse existing capture component).

## Dependencies
- Vault/project data via `App\Models\Vault` and `Project` plus services from ENG-02.
- Model selection info from ENG-03 — reuse to show current model in `/context`.
- Toast verbosity (UX-01) already implemented; respect user preferences.

## Considerations
- Ensure commands log usage (`logger()` statements) with context for telemetry.
- Provide consistent Markdown formatting in responses; leverage `chat-markdown` component.
- Keep UI responsive: avoid heavy queries in command handlers; eager load where necessary.

## Testing Tips
- Use Pest feature tests to simulate command execution through Livewire endpoints or direct handler invocation.
- Mock data via factories (DEV-01 outputs) for vault/project/session scenarios.

## Out of Scope
- Deep inbox automation (e.g., email integrations) — focus on existing fragment/task data.
- Complex compose workflows beyond opening panel and capturing data.
