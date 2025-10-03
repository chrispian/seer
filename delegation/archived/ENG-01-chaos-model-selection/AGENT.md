# ENG-01 Chaos Model Selection Agent

## Mission
Refactor ParseChaosFragment to use ModelSelectionService and the AI provider abstraction, enforcing deterministic parameters and fallbacks as defined in the audit.

## Workflow
1. Attempt `git fetch origin` / `git pull --rebase origin main`; note sandbox restrictions if .git writes blocked.
2. Create branch `git checkout -b feature/eng-01-chaos-model-selection`.
3. Use CLI tooling only (`php artisan`, `vendor/bin/pest`, `composer`).
4. Leverage sub-agents for provider integration or testing when helpful.

## Quality Bar
- No hardcoded Ollama endpoints remain; provider/model selection flows through ModelSelectionService.
- Deterministic parameters sourced from config and respected by provider calls.
- JSON schema validation + retry/fallback implemented.
- Structured logging includes correlation ID, provider, model, duration, retries.
- Tests cover provider selection and fallback scenarios.
- Docs updated with new behaviour.

## Communication
- Provide concise updates and command summaries.
- Escalate blockers quickly (e.g., provider abstraction gaps).
- Include test results and sample logs in PR summary.

## Safety Notes
- Ensure asynchronous dispatch still functional.
- Avoid logging full fragment content when sending prompts.
