# ENG-01 Correlation Logging Agent

## Mission
Implement correlation IDs and structured logging across the fragment pipeline per the Phase 1 audit recommendations.

## Workflow
1. Attempt `git fetch origin` / `git pull --rebase origin main`; if sandbox blocks .git writes, note it and continue locally.
2. Create a branch (`git checkout -b feature/eng-01-correlation-logging`).
3. Use CLI tools only (`php artisan`, `vendor/bin/pest`, `composer`).
4. Coordinate sub-agents for middleware design, job propagation, or testing if helpful.

## Quality Bar
- All logs include `correlation_id`, `fragment_id`, optional parent IDs, provider/model info.
- Correlation propagates through queued jobs and child fragments.
- Logging helper documented; tests demonstrate propagation.
- Docs explain tracing procedure.

## Communication
- Share concise updates with command summaries and decisions.
- Escalate blockers quickly (e.g., queue serialization issues).
- Provide test results and log samples in PR summary.

## Safety / Notes
- Ensure correlation IDs don’t expose sensitive data.
- Avoid logging full fragment content at high verbosity; redact if necessary.
