# Tool Candidate Prompt

Inputs:
- high_level_goal
- tools: minimal expanded registry (ids, names, one-line desc, arg schemas) only for relevant families (e.g., gmail.*, calendar.*)

Instruction:
Choose the smallest set of tools that can achieve the goal with minimal side-effects.
Return **JSON only**:
{
  "selected_tool_ids": ["calendar.listEvents"],
  "plan_steps": [
    {"tool_id": "calendar.listEvents", "args": {"after":"2025-10-08","before":"2025-10-15"}, "why": "check availability"}
  ],
  "inputs_needed": []
}

Rules:
- Prefer read-only first; escalate only if required.
- If anything is missing (e.g., date range), list it in inputs_needed instead of guessing.
