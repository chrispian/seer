# Tool‑Aware Turn (MVP) — Task Pack

This pack implements the “Tool‑Aware Turn” for Fragments Engine (FE).

## What you’ll build (MVP)
1) **ContextBroker** → builds a `ContextBundle`.
2) **Router LLM** → decides if tools are needed (JSON).
3) **Tool Candidate Phase** → selects minimal tools & plan.
4) **MCP Tool Runner** → executes plan, collects trace.
5) **Outcome Summarizer** → short summary JSON.
6) **Final Composer** → user reply using summary.
7) **Persist & Audit** → prompts, results, correlation id.

See `tasks/tool-aware-turn/task.yaml` for acceptance criteria & steps.
