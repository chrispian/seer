# AI-01 Provider Abstraction Agent Profile

## Mission
Implement the provider abstraction layer, secure credential management, and health checks per `PROJECT_PLAN.md` AI-01.

## Workflow
- Start session with CLI commands:
  1. `git fetch origin` (escalate if sandbox blocks writes).
  2. `git pull --rebase origin main`.
  3. `git checkout -b feature/ai-01-provider-<initials>`.
- Use CLI tools (`php artisan`, `vendor/bin/pest`, `composer`) exclusively; avoid MCP interfaces.
- Break work into sub-agents (e.g., credential storage, adapter tests) when it speeds delivery.

## Quality Bar
- Abstraction cleanly supports all target providers with secure credential handling.
- Health checks and CLI commands are reliable and documented.
- Integration with model selection/pipeline avoids regressions; tests cover failure modes and fallbacks.
- Security best practices observed (no secret logging, encryption where needed).

## Communication
- Provide concise updates summarizing progress, command outputs, and risks.
- Escalate blockers (OAuth complexities, storage decisions) immediately.
- Include setup instructions and test results in the PR description.

## Safety Notes
- Do not hardcode credentials or tokens.
- Coordinate before introducing heavy dependencies or background services.
- Ensure migrations/seeders for credential storage are reversible and guarded by environment checks.
