<?php

namespace App\Support;

use App\Contracts\ToolContract;
use App\Decorators\ToolTelemetryDecorator;
use Illuminate\Support\Facades\Log;

class ToolRegistry
{
    /** @var array<string, ToolContract> */
    protected array $tools = [];

    public function register(ToolContract $tool): void
    {
        // Automatically wrap tools with telemetry if enabled
        if (config('tool-telemetry.enabled', true)) {
            $tool = ToolTelemetryDecorator::wrap($tool);
        }

        $this->tools[$tool->name()] = $tool;
    }

    public function get(string $name): ?ToolContract
    {
        return $this->tools[$name] ?? null;
    }

    /** Load JSON schemas located in resources/tools/contracts */
    public function loadContract(string $name): ?array
    {
        $path = config('tools.contracts_path').DIRECTORY_SEPARATOR."{$name}.json";
        if (! file_exists($path)) {
            return null;
        }

        return json_decode(file_get_contents($path), true);
    }

    /** Simple scope check hook (extend for capability tokens) */
    public function ensureScope(string $scope): void
    {
        $allowed = config('tools.scopes', []);
        if (! in_array($scope, $allowed, true)) {
            // In production, throw AccessDenied; here we log and allow to let dev start
            Log::warning("Tool scope not in config/tools.php scopes: {$scope}");
        }
    }

    /** Utility to hash inputs/outputs for telemetry */
    public static function hash(mixed $data): string
    {
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
