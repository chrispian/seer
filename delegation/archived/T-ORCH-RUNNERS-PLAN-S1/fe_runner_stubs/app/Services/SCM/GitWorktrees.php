<?php

declare(strict_types=1);

namespace App\Services\SCM;

class GitWorktrees
{
    public function create(string $taskId, string $runId): string
    {
        // TODO: create worktree path and return
        return storage_path("worktrees/{$taskId}/{$runId}");
    }

    public function cleanup(string $taskId, string $runId): void
    {
        // TODO
    }
}
