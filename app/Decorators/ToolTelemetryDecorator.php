<?php

namespace App\Decorators;

use App\Contracts\ToolContract;
use App\Services\Telemetry\ToolTelemetry;
use Exception;
use Throwable;

class ToolTelemetryDecorator implements ToolContract
{
    public function __construct(
        private ToolContract $tool,
        private ToolTelemetry $telemetry
    ) {}

    public function name(): string
    {
        return $this->tool->name();
    }

    public function scope(): string
    {
        return $this->tool->scope();
    }

    public function inputSchema(): array
    {
        return $this->tool->inputSchema();
    }

    public function outputSchema(): array
    {
        return $this->tool->outputSchema();
    }

    public function run(array $payload): array
    {
        $invocationId = $this->telemetry->startInvocation($this->tool, $payload);
        
        try {
            $result = $this->tool->run($payload);
            $this->telemetry->completeInvocation($invocationId, $result);
            return $result;
        } catch (Throwable $e) {
            $this->telemetry->completeInvocation($invocationId, [], $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the underlying tool instance
     */
    public function getDecoratedTool(): ToolContract
    {
        return $this->tool;
    }

    /**
     * Static factory method to wrap a tool with telemetry
     */
    public static function wrap(ToolContract $tool): self
    {
        if (!config('tool-telemetry.enabled', true)) {
            return $tool;
        }
        
        return new self($tool, app(ToolTelemetry::class));
    }
}