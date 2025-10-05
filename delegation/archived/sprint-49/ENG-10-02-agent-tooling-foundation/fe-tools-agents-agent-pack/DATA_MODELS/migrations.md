# Proposed Migrations (High-Level)
- artifacts: id, owner_id, type, mime, path, sha256, created_by_tool, source_query_id?, metadata (jsonb), timestamps
- saved_queries: id, name, entity, filters (jsonb), boosts (jsonb), order_by (jsonb), limit, owner_id, visibility, timestamps
- agent_notes: id, agent_id, topic, body, kind, scope, ttl_at, links (json), tags (json), provenance (json), timestamps
- agent_decisions: id, topic, decision, rationale, alternatives (json), confidence, links (json), project_id?, timestamps
- work_items: id, type, parent_id, assignee_type, assignee_id, status, priority, project_id, tags (json), state (jsonb), metadata (jsonb), timestamps
- work_item_events: id, work_item_id, kind, body, data (jsonb), actor_type, actor_id, timestamps
- sprints: id, name, start_at, end_at, goal, status, metadata (jsonb), timestamps
- sprint_items: id, sprint_id, work_item_id, order, timestamps
