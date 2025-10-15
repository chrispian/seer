# PACK A — Backend: Builder Kernel (Agents)

## Objective
Persist builder configs, expose resolver endpoints for `Agent`, and wire command actions.

### Tasks
1. Models/Migrations for builder components, pages, actions, datasources.
2. Compute hash + version on write.
3. Implement DataSourceResolver for Agent list/detail.
4. Implement Action Adapter for command/navigate.
5. API Endpoints: /api/v2/ui/pages/{key}, /api/v2/ui/action, /api/v2/ui/datasource/{alias}/query.
6. Capability gate for searchable/filterable/sortable.

### Acceptance
- GET page.agent.table.modal works
- POST datasource/agent/query returns paginated Agents
- RowAction opens /orch-agent detail
