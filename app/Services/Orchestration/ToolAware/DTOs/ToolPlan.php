<?php

namespace App\Services\Orchestration\ToolAware\DTOs;

final class ToolPlan
{
    /** @var string[] */
    public array $selected_tool_ids = [];

    /** @var array<int,array{tool_id:string, args:array, why:string}> */
    public array $plan_steps = [];

    /** @var string[] */
    public array $inputs_needed = [];

    public function __construct(
        array $selected_tool_ids = [],
        array $plan_steps = [],
        array $inputs_needed = []
    ) {
        $this->selected_tool_ids = $selected_tool_ids;
        $this->plan_steps = $plan_steps;
        $this->inputs_needed = $inputs_needed;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['selected_tool_ids'] ?? [],
            $data['plan_steps'] ?? [],
            $data['inputs_needed'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'selected_tool_ids' => $this->selected_tool_ids,
            'plan_steps' => $this->plan_steps,
            'inputs_needed' => $this->inputs_needed,
        ];
    }

    public function hasSteps(): bool
    {
        return count($this->plan_steps) > 0;
    }

    public function stepCount(): int
    {
        return count($this->plan_steps);
    }
}
