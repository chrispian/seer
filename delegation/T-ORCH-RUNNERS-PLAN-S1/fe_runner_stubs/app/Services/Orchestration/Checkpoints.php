<?php

declare(strict_types=1);

namespace App\Services\Orchestration;

class Checkpoints
{
    public function persist(string $taskId, string $runId, array $result): string
    {
        // TODO: write manifest + artifacts; return checkpoint URI
        return "fe://{$taskId}/{$runId}/checkpoints/".uniqid().'.json';
    }
}
