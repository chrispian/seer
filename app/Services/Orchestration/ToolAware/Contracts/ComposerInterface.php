<?php

namespace App\Services\Orchestration\ToolAware\Contracts;

use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Orchestration\ToolAware\DTOs\OutcomeSummary;

interface ComposerInterface
{
    /**
     * Compose final user-facing reply
     *
     * @param  OutcomeSummary|null  $summary  Null if no tools were used
     * @param  string|null  $correlationId  Null if no tools were used
     * @return string Final markdown reply
     */
    public function compose(
        ContextBundle $context,
        ?OutcomeSummary $summary = null,
        ?string $correlationId = null
    ): string;
}
