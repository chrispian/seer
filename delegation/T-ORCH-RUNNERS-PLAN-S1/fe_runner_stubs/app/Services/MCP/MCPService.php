<?php

declare(strict_types=1);

namespace App\Services\MCP;

class MCPService
{
    public function listTasks(): array { return []; }
    public function listRuns(string $taskId): array { return []; }
    public function listArtifacts(string $taskId, string $runId): array { return []; }
    public function startRun(string $taskId): array { return ['run_id'=>'R1']; }
    public function runStatus(string $runId): array { return ['status'=>'stub']; }
    public function artifactPut(string $uri, string $content): bool { return true; }
}
