# Outcome Summary Prompt

Input:
- ToolResults[] (trim to essentials)

Instruction:
Summarize outcomes for the user in <=120 words. Include 3–6 bullet key facts.
Return **JSON only**:
{
  "short_summary": "…",
  "key_facts": ["…","…"],
  "links": [],
  "confidence": "low|medium|high"
}
