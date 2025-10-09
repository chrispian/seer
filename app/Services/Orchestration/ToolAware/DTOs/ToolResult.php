<?php

namespace App\Services\Orchestration\ToolAware\DTOs;

final class ToolResult
{
    public string $tool_id;

    public array $args;

    public mixed $result = null;

    public ?string $error = null;

    public float $elapsed_ms = 0.0;

    public bool $success = false;

    public function __construct(
        string $tool_id,
        array $args = [],
        mixed $result = null,
        ?string $error = null,
        float $elapsed_ms = 0.0,
        bool $success = false
    ) {
        $this->tool_id = $tool_id;
        $this->args = $args;
        $this->result = $result;
        $this->error = $error;
        $this->elapsed_ms = $elapsed_ms;
        $this->success = $success;
    }

    public function toArray(): array
    {
        return [
            'tool_id' => $this->tool_id,
            'args' => $this->args,
            'result' => $this->result,
            'error' => $this->error,
            'elapsed_ms' => $this->elapsed_ms,
            'success' => $this->success,
        ];
    }
}
