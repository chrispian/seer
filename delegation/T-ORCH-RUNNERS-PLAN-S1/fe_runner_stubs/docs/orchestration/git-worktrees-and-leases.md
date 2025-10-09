# Git Worktrees & Edit Leases

- One worktree per run: `worktrees/{task}/{run}`.
- Edit leases: Redis `SETNX file:{path}` with TTL (10–20m); renew on write.
- Apply patches with `git apply --3way`. Conflicts → open repair task.
