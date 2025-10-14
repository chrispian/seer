<?php

namespace App\Services\Orchestration\ToolAware\Contracts;

use App\Services\Orchestration\ToolAware\DTOs\ExecutionTrace;
use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;

interface ToolRunnerInterface
{
    /**
     * Execute tool plan and collect results
     */
    public function execute(ToolPlan $plan, ?int $sessionId = null, ?string $conversationId = null, ?string $messageId = null): ExecutionTrace;
}
