<?php

namespace App\Services\Orchestration\ToolAware\Contracts;

use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;

interface ContextBrokerInterface
{
    /**
     * Assemble context bundle from session and user message
     *
     * @param  int|null  $sessionId  Chat session ID
     * @param  string  $userMessage  Current user message
     */
    public function assemble(?int $sessionId, string $userMessage): ContextBundle;
}
