# Project Manager Agent — Task
## Objective
Review this pack + the repo's `delegation/` folder and:
1) Plan the work into logical sprints.
2) Create local agent task packs per task under the correct sprint folder using the delegation workflow.
3) Ensure risky tasks use the Plan→Approve→Apply pattern and define approval gates.
4) Attach rubrics and acceptance tests to each generated task pack.

## Inputs
- `CONTEXT/` files
- `TASKS/backlog.yaml`
- `CONTRACTS/` (tool schemas)
- `DATA_MODELS/` (proposed tables)
- Repo's existing `delegation/` workflow

## Steps
1. Read `CONTEXT/summary-implementation.md` and `TASKS/backlog.yaml`.
2. Group tasks by dependency and scope; produce `sprints/plan.yaml`.
3. For each sprint:
   - Create a new sprint folder inside the repo's `delegation/` (e.g., `delegation/Sprint-006`).
   - Copy `DELEGATION/sprint-template/*` as scaffolding.
   - For each task in the sprint, generate a **task pack** with:
     - task.md (goal, context, deliverables, acceptance, rubric, scopes)
     - tool-usage.json (allowed tools/scopes, dry-run flags)
     - checklists.md (security/telemetry to run)
     - dependencies.md (linked tasks/PRs/artifacts)
4. Where applicable, create **runbooks** files for multi-step operations.
5. Produce a sprint review artifact summarizing scope, risks, and exit criteria.

## Output
- Updated `delegation/Sprint-XXX/` folders with fully-specified task packs ready for execution by local agents.
