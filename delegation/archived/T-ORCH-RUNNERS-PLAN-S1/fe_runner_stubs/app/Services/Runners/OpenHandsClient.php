<?php

declare(strict_types=1);

namespace App\Services\Runners;

class OpenHandsClient
{
    public function attach(string $conversationId, array $opts = []): void
    {
        // TODO: open WS connection, map to (task_id, run_id), start heartbeats
    }

    public function sendAction(string $conversationId, array $action): void
    {
        // TODO: forward action to OH; enforce budgets/policy
    }
}
