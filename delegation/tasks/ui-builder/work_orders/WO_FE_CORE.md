# WORK ORDER (PM → FE-Core)
Goal: Render config-driven pages with registry/dispatcher/slot binder.

Inputs:
  - /frontend/page.agent.table.modal.json
Constraints:
  - Shadcn UI
  - Use /api/v2/ui/* only
Deliverables:
  - ComponentRegistry, ActionDispatcher, SlotBinder
  - Error toasts; optimistic updates
Exit Criteria:
  - Modal renders with table component bound to Agent datasource
