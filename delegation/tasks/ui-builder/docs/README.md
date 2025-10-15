# Agents Builder PoC (Fragments Engine v2)

## Overview
A prototype for config-driven UI builder rendering a Single Resource (Agents) modal using Shadcn components.

### Includes
- Backend Kernel (config persistence, resolver, actions)
- Frontend Renderer (Shadcn + JSON config)
- Integration (v2 route/shell)
- Seeds (scaffold commands + demo config)
- Profile Ingestion (future)

### Quick Start
1. Add backend migrations + models.
2. Seed `page.agent.table.modal.json`.
3. Visit `/v2/pages/page.agent.table.modal` to test UI.
4. Use `/orch-agent` and `/orch-agent-new` for actions.

### Next Steps
- Implement Profile ingestion job.
- Expand to multi-resource layouts.
