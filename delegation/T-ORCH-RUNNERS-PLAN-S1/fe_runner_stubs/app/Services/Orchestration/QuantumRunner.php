<?php

declare(strict_types=1);

namespace App\Services\Orchestration;

use Illuminate\Support\Facades\Redis;

class QuantumRunner
{
    public int $defaultQuantumSeconds = 90;

    public function executeQuantum(string $taskId, string $runId): void
    {
        $leaseKey = "lease:{$taskId}:{$runId}";
        $got = Redis::setnx($leaseKey, getmypid());
        if (!$got) return;
        Redis::expire($leaseKey, 120);

        $start = microtime(true);
        try {
            // TODO: load state, plan; run until budgets/quantum exhausted; checkpoint and yield.
        } finally {
            Redis::del($leaseKey);
        }
    }
}
