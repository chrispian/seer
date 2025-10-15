# PACK B — Frontend: Shadcn Renderer (Agents)

## Objective
Render Agents modal page from config and wire actions to APIs.

### Tasks
1. Renderer Core (registry, slot binder, dispatcher)
2. Containers (layout.modal, layout.columns, layout.rows)
3. Primitives (table, search.bar, button.icon)
4. Event bus for detail/toasts/errors
5. Feature flags per capabilities

### Acceptance
- Modal with Agents table renders
- Search updates results
- Row click opens Agent detail
- Add Agent button triggers /orch-agent-new
