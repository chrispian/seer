<?php

declare(strict_types=1);

namespace App\Services\ContextBroker;

class ContextBroker
{
    public function get(string $taskRunId, string $viewSignature): array
    {
        // TODO: check cache; compute on miss; pin per-run
        return ['signature'=>$viewSignature,'data'=>[]];
    }
}
