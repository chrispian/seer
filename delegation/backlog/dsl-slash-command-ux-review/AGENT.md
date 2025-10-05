# DSL Slash Command UX Review

## Overview
- Build a sprint plan that hardens the DSL-driven slash command experience: consistent autocomplete listings, richer help metadata, alias parity, and reliable keyboard navigation.
- Ensure future UI builders and help systems can pull deterministic command data from a single cached registry.

## Findings & Suggestions
- **Autocomplete Source of Truth**
  - `app/Http/Controllers/AutocompleteController.php:14` still reads from the legacy `App\Services\CommandRegistry` static map, so new YAML packs never show up; replace this with a service that queries `command_registry` rows (joining cached manifest metadata) and expands aliases + slash triggers.
  - Honor cache busting (e.g. `frag:command:cache`) by clearing both Laravel cache keys (`command_pack.*`) and any new autocomplete cache, so fresh commands appear immediately without app restarts.

- **Registry Schema & Manifest Metadata**
  - Extend the `command_registry` table (`database/migrations/2025_10_03_212411_create_command_registry_table.php:14`) to store human-friendly fields: `name`, `category`, `summary`, `usage`, `examples`, `aliases`, `keywords`. Persist them when `CommandPackLoader::updateRegistryCache()` (`app/Services/Commands/CommandPackLoader.php:108`) ingests a pack so UI + help endpoints stay in sync.
  - Encourage pack authors to add a `help` block in `command.yaml` (e.g. `help.summary`, `help.arguments`, `help.success`, `help.error`, `help.examples`) and validate it during cache rebuilds so broken help data is caught early.

- **Alias + Trigger Resolution**
  - The runtime controller (`app/Http/Controllers/CommandController.php:74`) only looks up the canonical slug; we need a lookup table (or hydrated cache) that maps every alias (strip leading `/`) back to the owning slug so `/s` → `search` lands in DSL. Propagate the same mapping to autocomplete so arrow-selection inserts the canonical command while still showing alias badges.
  - Surface reserved vs user-authored packs and gate conflicting slugs/aliases during cache rebuild to avoid silent overrides.

- **Interactive Help System**
  - Replace the static `/help` DSL pack (`fragments/commands/help/command.yaml`) with a template that renders from the cached registry metadata; expose a backing API (`GET /api/commands/help`) so `CommandController` and future UI panels can reuse output.
  - Add a long-lived cache entry (`command_help.index`) populated during `frag:command:cache` so `/help` stays fast but auto-invalidates when packs change.

- **UI Interaction & Keyboard Support**
  - The TipTap suggestion list closes on arrow presses because `SlashCommandList` (`resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx:52-85`) returns `true` without calling `event.preventDefault()`/`stopPropagation()`. Patch the handler to consume the event and keep focus inside the popover; add tests or Storybook coverage to lock behaviour in.
  - Consider adding client-side caching/debouncing in `fetchCommands` (`resources/js/islands/chat/tiptap/utils/autocomplete.ts:15`) to reduce rapid fire requests while typing, reusing the same registry payload as `/help`.

- **Observability Hooks**
  - Log cache rebuild outcomes (command counts, alias conflicts, missing help blocks) so deviations surface quickly; emit metrics when autocomplete payloads are regenerated to watch cold-start latency.

- **Next Steps for Planning**
  - Design the final registry schema + manifest contract, then create migration + loader tickets.
  - Schedule FE tasks for keyboard nav fixes, cached autocomplete consumption, and the `/help` UI refresh.
  - Outline validation + testing (unit for loader, feature for autocomplete + help endpoint, Cypress/Playwright for composer UX).
