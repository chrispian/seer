# WORK ORDER (PM → BE-Kernel)
Goal: Implement builder config persistence with hashing/versioning and minimal v2 endpoints.

Inputs:
  - /docs/PACK_A_Backend_Kernel.md (from PoC bundle)
  - /frontend/page.agent.table.modal.json (seed)
Constraints:
  - Router-only for actions
  - Expose /api/v2/ui endpoints
  - ADR v2 for API/contract decisions
Deliverables:
  - Migrations + Models: fe_ui_components, fe_ui_pages, fe_ui_actions, fe_ui_datasources
  - Hash/version on write
  - GET /api/v2/ui/pages/{key}
Exit Criteria:
  - Fetch page.agent.table.modal with hash/version
