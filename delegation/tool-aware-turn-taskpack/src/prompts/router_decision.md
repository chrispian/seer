# Decision Prompt (Router)

Inputs:
- conversation_summary
- user_message

Instruction:
Decide if a tool call is required to make the next best step.
Return **JSON only**:
{
  "needs_tools": true|false,
  "rationale": "one sentence",
  "high_level_goal": "what we’re trying to accomplish right now or null"
}

Rules:
- needs_tools = true only if you cannot answer sufficiently from context or you need to act (send email, create event, fetch inbox, etc.).
- Keep rationale to one sentence.
