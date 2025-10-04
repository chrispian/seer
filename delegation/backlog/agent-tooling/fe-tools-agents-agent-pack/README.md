# Fragments Engine — Tools & Agents Foundation (Agent Pack)
**Pack Version:** 0.1.0 • **Date:** 2025-10-04

This pack seeds a Project Manager agent to:
1) Review this context pack + your repo's existing `delegation/` folder.
2) Plan the work into **logical sprints**.
3) Create **local agent task packs** per task in the appropriate **sprint folder** using your delegation workflow.

## Contents
- `CONTEXT/` — summary, principles, architecture, security, sprintable plan.
- `CONTRACTS/` — versioned tool contracts (JSON Schemas) for deterministic tool use.
- `TASKS/` — backlog.yaml (high-level tasks), runbooks, patterns, rubrics.
- `AGENTS/` — Project Manager task + config.
- `PROMPTS/` — prompt orchestrator assembly guidance + evaluation rubrics.
- `DATA_MODELS/` — proposed tables and model fields for memory, artifacts, work items.
- `DELEGATION/` — instructions + sprint folder template.
- `CHECKLISTS/` — security, telemetry, observability checklists.

## How the PM agent should proceed
- Read `AGENTS/ProjectManager.task.md` first.
- Use `TASKS/backlog.yaml` to break down into sprints.
- For each created sprint, copy `DELEGATION/sprint-template/` into your repo's `delegation/` with the correct sprint name (e.g., `Sprint-006`), then place generated task packs inside `tasks/` under that sprint.

> Note: This pack does **not** include your repo code. It references your existing `delegation/` folder and assumes your standard local “delegation workflow” is present.

## Next Steps
- Map real entities in DbQueryTool::builderFor() (e.g., Fragment, Contact, etc.).
- Add exporters (CSV/XLSX/PDF) behind ExportGenerateTool (PHPSpreadsheet, Dompdf/Snappy).
- Wire telemetry (hash input/output, latency) to your existing observability layer.
- Add approval gates for mutating tools (shell/fs/repo)