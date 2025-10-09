# Context Views & Cache

Key: `(task_run_id, view_signature)`
- `ProjectView@<sha>` — modules, services, tests, tools.
- `TaskView@<sha>` — spec, acceptance, deps, related PRs.
- `GitView@<sha>` — branch, dirty files, recent commits.
- `TestFailureView@<sha>` — failures, logs, suspects.

Cache: Redis read-through with stampede protection. Invalidate on repo events / explicit bust.
