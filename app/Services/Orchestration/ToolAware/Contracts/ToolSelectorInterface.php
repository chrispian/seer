<?php

namespace App\Services\Orchestration\ToolAware\Contracts;

use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;

interface ToolSelectorInterface
{
    /**
     * Select minimal set of tools to achieve the goal
     *
     * @param  string  $goal  High-level goal from router
     * @param  ContextBundle  $context  Conversation context
     *
     * @throws \RuntimeException on LLM failure or validation errors
     */
    public function selectTools(string $goal, ContextBundle $context): ToolPlan;
}
