# Prompt Orchestrator Assembly
Inputs:
- Chat/session context, active project/sprint
- Agent config (role, tone, style, rules)
- Tool availability + schemas (current registry subset)
- Relevant short-term rollups + pinned long-term memories
- Slash-command fragments (if any)

Outputs:
- System prompt (assembled) with:
  - Context block (IDs/links only, no raw secrets)
  - Tool list + short usage examples
  - Style/tone directives
  - Safety rails (no fs.write/shell without plan+approval)
- Telemetry: prompt hash, variant label, tool calls, outcomes
