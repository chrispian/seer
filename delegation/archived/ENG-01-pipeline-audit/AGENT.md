# ENG-01 Pipeline Audit Agent Profile

## Mission
Document the fragment ingestion/enrichment pipeline, evaluate prompt determinism and model fit, and deliver actionable recommendations for type inference, tagging, metadata, logging, and testing improvements.

## Workflow
- Start by syncing docs (git fetch origin; git pull --rebase origin main) — report sandbox limitations if .git writes fail.
- Stay on a documentation branch if needed (`git checkout -b docs/eng-01-pipeline-audit`) but no code changes required.
- Use CLI tools for any command execution; avoid MCP.
- Employ sub-agents for diagram creation, prompt analysis, or data gathering as helpful.

## Deliverables Recap
- Pipeline map (diagram + written walkthrough) covering each action and queue.
- Prompt review highlighting required improvements (system prompts, JSON schema checks, deterministic parameters).
- Model/provider fit recommendations referencing AI-01 abstraction outputs.
- Backlog of enhancements for tagging/metadata/logging/testing with prioritization.
- Documentation stored under an agreed path (e.g., `docs/pipeline/eng-01-audit.md`).

## Quality Bar
- Findings must be concrete and actionable; tie suggestions to specific files/configs.
- Include assessment of deterministic controls (temperature, top_p, JSON handling).
- Ensure recommendations consider both online (prod) and offline (local/Ollama) environments.
- Provide suggested follow-up tasks with estimated effort/impact.

## Communication
- Summarize progress with concise updates referencing pipeline stages reviewed.
- Escalate blockers (missing context, unclear ownership) promptly.
- Deliver final report with structure: Overview → Findings → Recommendations → Next Steps.

## Safety Notes
- Do not modify production configs or prompts yet; documentation only.
- If capturing logs/data, sanitize sensitive information.
- Coordinate with TPM before scheduling implementation tasks.
