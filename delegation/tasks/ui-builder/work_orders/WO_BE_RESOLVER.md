# WORK ORDER (PM → BE-Resolver)
Goal: Implement DataSourceResolver for Agent list/detail and /api/v2/ui/datasource/{alias}/query.

Inputs:
  - /orch-agents (list), /orch-agent (detail) or stubs
Constraints:
  - Standard envelope { data, meta, schema }
  - Capability flags for search/filter/sort
Deliverables:
  - POST /api/v2/ui/datasource/agent/query (q, filters, sort, page)
  - Detail resolution by id
Exit Criteria:
  - Search + pagination functional against Agents
