<?php

namespace App\Services\Orchestration\ToolAware\Contracts;

use App\Services\Orchestration\ToolAware\DTOs\ExecutionTrace;
use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;

interface ToolRunnerInterface
{
    /**
     * Execute tool plan and collect results
     *
     * @param ToolPlan $plan
     * @return ExecutionTrace
     */
    public function execute(ToolPlan $plan): ExecutionTrace;
}
