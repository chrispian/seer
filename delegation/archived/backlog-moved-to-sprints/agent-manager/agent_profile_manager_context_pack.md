# Agent Profile Manager — Context Pack (Hybrid with Modes)

## 1) Goals (MVP)
- Create/manage agent profiles (name, description, personality, tone, style, role, mode, allowed tools, default model).
- Support **system agents** (non-deletable, required for engine ops) and **user agents**.
- Support **cloning** with lineage.
- Allow per-scope defaults (global, workspace, project, command).
- Support **modes** (Agent, Plan, Chat, Assistant) that define capability boundaries.
- Add **per-message mode overrides** at runtime.
- Use a **hybrid primary agent model**: each chat session has a dedicated “primary chat agent” for consistent UX, while sub-agents can act as delegates with logs tied back to the primary.

---

## 2) Data Model

### Table: agent_profiles
- id (uuid)
- name (string)
- description (text)
- role (string)
- mode (enum: Agent, Plan, Chat, Assistant)
- default_model (string)
- allowed_tools (json)
- prompt_customizations (json)
- personality (json)
- tone (json/string)
- style (json)
- is_system (bool)
- is_default_chat_agent (bool)
- parent_id (uuid)
- version (string, SemVer)
- scope_overrides (json)
- meta (json)
- timestamps

### Table: agent_profile_histories
- id (uuid)
- agent_profile_id (uuid)
- version (string)
- snapshot (json)
- change_summary (text)
- changed_by (user id)
- timestamps

### Logging
Each log entry includes:
- session_id
- primary_agent_id
- active_agent_id
- mode
- tool_calls
- context_chain

---

## 3) Modes

### Agent
- Full execution (commands, tools, system access).
- Configurable auto_accept and per-call prompting.

### Plan
- Sandboxed, read-only tools.
- No mutation of system state.

### Chat
- Conversational only, no system execution.
- Personality/tone centric.

### Assistant
- Cognitive assistant (notes, todos, summaries, calendar, email).
- Robust mix of productivity + second-brain style.

---

## 4) Behavior & Resolution

1. Resolve agent via command → project → workspace → global → system fallback.
2. Apply agent default mode unless user overrides per-message.
3. System pipelines may enforce a specific agent/mode regardless of session override.
4. Primary agent: each chat/session has a designated “primary chat agent.”
   - User interacts with this agent.
   - Sub-agents can delegate and post updates.
   - Logs: session scoped to primary, steps scoped to active agent with parent=primary.

---

## 5) Prompt Assembly

Prompt structure includes:
- System preamble (from agent + global FE guardrails)
- Developer directives
- Role/mode-specific constraints (sandboxing, execution hints)
- Tone/style instructions
- Few-shot examples (optional)
- User task/context

---

## 6) UI/UX (React/Shadcn)

- Agent Profiles page for CRUD (list, edit, clone, history).
- Mode dropdown on profile editor.
- Agent selector in chat bar, with mode badge.
- Model selector auto-fills from agent, warn if override.
- Actions button: show only allowed tools for active agent.

---

## 7) API Endpoints

- GET /api/agents
- POST /api/agents
- GET /api/agents/{id}
- PUT /api/agents/{id}
- POST /api/agents/{id}/clone
- GET /api/agents/{id}/history
- POST /api/agents/{id}/defaults
- GET /api/resolve-agent

---

## 8) Events

- AgentProfileCreated
- AgentProfileUpdated
- AgentProfileCloned
- AgentDefaultChanged
- AgentResolved

---

## 9) Telemetry

- Logs tied to agent_id, mode, and tool_calls.
- Primary agent recorded at session start.
- Sub-agent activity logged with parent=primary.

---

## 10) Seeds

- Chat Agent (default)
- Researcher (Plan)
- Code Reviewer (Agent)
- Planner (Assistant)

---

## 11) Implementation Steps

1. Migrations for agent_profiles, agent_profile_histories.
2. Models + policies for system vs user agents.
3. Services: AgentResolver, AgentVersioner, PromptAssembler.
4. Registry for allowed tools.
5. React/Shadcn UI for CRUD + selectors.
6. API endpoints.
7. Event emitters + logging observers.
8. Seed default profiles.
