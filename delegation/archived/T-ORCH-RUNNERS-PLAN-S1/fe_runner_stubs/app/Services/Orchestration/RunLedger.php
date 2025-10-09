<?php

declare(strict_types=1);

namespace App\Services\Orchestration;

class RunLedger
{
    public function append(array $stepResult): void
    {
        // TODO: buffer + flush to telemetry bus / DB on checkpoint
    }
}
