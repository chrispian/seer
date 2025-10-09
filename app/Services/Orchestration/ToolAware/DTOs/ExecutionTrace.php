<?php

namespace App\Services\Orchestration\ToolAware\DTOs;

final class ExecutionTrace
{
    public string $correlation_id;

    /** @var ToolResult[] */
    public array $steps = [];

    public float $total_elapsed_ms = 0.0;

    public function __construct(string $correlation_id = '')
    {
        $this->correlation_id = $correlation_id ?: \Illuminate\Support\Str::uuid()->toString();
    }

    public function addStep(ToolResult $result): void
    {
        $this->steps[] = $result;
        $this->total_elapsed_ms += $result->elapsed_ms;
    }

    public function hasErrors(): bool
    {
        foreach ($this->steps as $step) {
            if ($step->error !== null) {
                return true;
            }
        }
        return false;
    }

    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->steps as $step) {
            if ($step->error !== null) {
                $errors[] = [
                    'tool_id' => $step->tool_id,
                    'error' => $step->error,
                ];
            }
        }
        return $errors;
    }

    public function toArray(): array
    {
        return [
            'correlation_id' => $this->correlation_id,
            'steps' => array_map(fn($step) => $step->toArray(), $this->steps),
            'total_elapsed_ms' => $this->total_elapsed_ms,
            'step_count' => count($this->steps),
            'has_errors' => $this->hasErrors(),
        ];
    }
}
