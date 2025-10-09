<?php

namespace App\Services\Orchestration\ToolAware\DTOs;

final class RouterDecision
{
    public bool $needs_tools = false;

    public string $rationale = '';

    public ?string $high_level_goal = null;

    public function __construct(
        bool $needs_tools = false,
        string $rationale = '',
        ?string $high_level_goal = null
    ) {
        $this->needs_tools = $needs_tools;
        $this->rationale = $rationale;
        $this->high_level_goal = $high_level_goal;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['needs_tools'] ?? false,
            $data['rationale'] ?? '',
            $data['high_level_goal'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'needs_tools' => $this->needs_tools,
            'rationale' => $this->rationale,
            'high_level_goal' => $this->high_level_goal,
        ];
    }
}
