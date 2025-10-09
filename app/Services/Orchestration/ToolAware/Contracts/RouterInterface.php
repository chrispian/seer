<?php

namespace App\Services\Orchestration\ToolAware\Contracts;

use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Orchestration\ToolAware\DTOs\RouterDecision;

interface RouterInterface
{
    /**
     * Decide if tools are needed to answer the user's message
     *
     * @throws \RuntimeException on LLM failure after retries
     */
    public function decide(ContextBundle $context): RouterDecision;
}
