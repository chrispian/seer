<?php

namespace App\Services\Telemetry;

use App\Contracts\ToolContract;
use App\Support\ToolRegistry;
use Exception;
use Illuminate\Support\Facades\Log;

class ToolHealthMonitor
{
    public function __construct(
        private ToolRegistry $registry,
        private ToolTelemetry $telemetry
    ) {}

    public function checkAllTools(): array
    {
        if (! config('tool-telemetry.health.enabled', true)) {
            return [];
        }

        $results = [];
        $toolNames = $this->getRegisteredToolNames();

        foreach ($toolNames as $toolName) {
            $results[$toolName] = $this->checkTool($toolName);
        }

        $this->generateHealthSummary($results);

        return $results;
    }

    public function checkTool(string $toolName): array
    {
        $tool = $this->registry->get($toolName);
        if (! $tool) {
            return [
                'status' => 'not_found',
                'error' => 'Tool not found in registry',
                'timestamp' => now()->toISOString(),
            ];
        }

        $startTime = microtime(true);
        $healthy = true;
        $error = null;

        try {
            // Perform basic health check based on tool type
            $this->performHealthCheck($tool);
        } catch (Exception $e) {
            $healthy = false;
            $error = $e->getMessage();
        }

        $responseTime = (microtime(true) - $startTime) * 1000;

        // Record health check result
        $this->telemetry->recordHealthCheck($toolName, $healthy, $error, $responseTime);

        return [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'response_time_ms' => round($responseTime, 2),
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ];
    }

    private function performHealthCheck(ToolContract $tool): void
    {
        $timeout = config('tool-telemetry.health.health_check_timeout_ms', 1000);

        // Set timeout for health check
        $startTime = microtime(true);

        try {
            switch ($tool->name()) {
                case 'db.query':
                    $this->checkDbQueryTool($tool);
                    break;

                case 'memory.search':
                case 'memory.write':
                    $this->checkMemoryTool($tool);
                    break;

                case 'export.generate':
                    $this->checkExportTool($tool);
                    break;

                default:
                    $this->checkGenericTool($tool);
            }

            $elapsed = (microtime(true) - $startTime) * 1000;
            if ($elapsed > $timeout) {
                throw new Exception("Health check timed out after {$elapsed}ms");
            }

        } catch (Exception $e) {
            throw new Exception('Health check failed: '.$e->getMessage());
        }
    }

    private function checkDbQueryTool(ToolContract $tool): void
    {
        // Validate schema availability
        $inputSchema = $tool->inputSchema();
        if (empty($inputSchema)) {
            throw new Exception('Input schema not available');
        }

        // Check database connectivity by testing a simple query
        try {
            $result = $tool->run([
                'entity' => 'work_items',
                'limit' => 1,
                'offset' => 0,
            ]);

            if (! isset($result['items'])) {
                throw new Exception('Unexpected response format');
            }
        } catch (Exception $e) {
            throw new Exception('Database query test failed: '.$e->getMessage());
        }
    }

    private function checkMemoryTool(ToolContract $tool): void
    {
        // Validate schema availability
        $inputSchema = $tool->inputSchema();
        if (empty($inputSchema)) {
            throw new Exception('Input schema not available');
        }

        if ($tool->name() === 'memory.search') {
            // Test memory search functionality
            try {
                $result = $tool->run([
                    'q' => 'test',
                    'limit' => 1,
                ]);

                if (! isset($result['items'])) {
                    throw new Exception('Unexpected response format');
                }
            } catch (Exception $e) {
                throw new Exception('Memory search test failed: '.$e->getMessage());
            }
        }
    }

    private function checkExportTool(ToolContract $tool): void
    {
        // Validate schema availability
        $inputSchema = $tool->inputSchema();
        if (empty($inputSchema)) {
            throw new Exception('Input schema not available');
        }

        // Check if export directory is writable
        $exportPaths = config('tools.allow_write_paths', []);
        if (empty($exportPaths)) {
            throw new Exception('No export paths configured');
        }

        foreach ($exportPaths as $path) {
            if (! is_dir($path)) {
                throw new Exception("Export path does not exist: {$path}");
            }

            if (! is_writable($path)) {
                throw new Exception("Export path is not writable: {$path}");
            }
        }
    }

    private function checkGenericTool(ToolContract $tool): void
    {
        // Basic validation for any tool
        $name = $tool->name();
        $scope = $tool->scope();

        if (empty($name)) {
            throw new Exception('Tool name is empty');
        }

        if (empty($scope)) {
            throw new Exception('Tool scope is empty');
        }

        // Validate schemas are available
        $inputSchema = $tool->inputSchema();
        $outputSchema = $tool->outputSchema();

        if (empty($inputSchema) && empty($outputSchema)) {
            throw new Exception('Both input and output schemas are empty');
        }
    }

    private function getRegisteredToolNames(): array
    {
        // Get tool names from configuration or discovery
        return [
            'db.query',
            'memory.search',
            'memory.write',
            'export.generate',
        ];
    }

    private function generateHealthSummary(array $results): void
    {
        $totalTools = count($results);
        $healthyTools = 0;
        $unhealthyTools = 0;
        $errors = [];

        foreach ($results as $toolName => $result) {
            if ($result['status'] === 'healthy') {
                $healthyTools++;
            } else {
                $unhealthyTools++;
                if (isset($result['error'])) {
                    $errors[$toolName] = $result['error'];
                }
            }
        }

        $healthPercentage = $totalTools > 0 ? round(($healthyTools / $totalTools) * 100, 1) : 0;

        $summary = [
            'timestamp' => now()->toISOString(),
            'total_tools' => $totalTools,
            'healthy_tools' => $healthyTools,
            'unhealthy_tools' => $unhealthyTools,
            'health_percentage' => $healthPercentage,
            'errors' => $errors,
        ];

        Log::channel('tool-telemetry')->info('tool.health.summary', $summary);

        // Generate alerts if health is poor
        if ($healthPercentage < 80 && config('tool-telemetry.alerts.enabled', true)) {
            $this->generateHealthAlert($summary);
        }
    }

    private function generateHealthAlert(array $summary): void
    {
        Log::channel('tool-telemetry')->warning('tool.health.alert', [
            'alert_type' => 'poor_tool_health',
            'health_percentage' => $summary['health_percentage'],
            'unhealthy_tools' => $summary['unhealthy_tools'],
            'total_tools' => $summary['total_tools'],
            'errors' => $summary['errors'],
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function getToolStatus(string $toolName): array
    {
        $healthStatus = ToolTelemetry::getHealthStatus();

        return $healthStatus[$toolName] ?? [
            'consecutive_failures' => 0,
            'consecutive_successes' => 0,
            'last_check' => null,
            'current_status' => 'unknown',
        ];
    }

    public function getSystemHealth(): array
    {
        $healthStatus = ToolTelemetry::getHealthStatus();
        $totalTools = count($healthStatus);
        $healthyTools = 0;

        foreach ($healthStatus as $status) {
            if ($status['current_status'] === 'healthy') {
                $healthyTools++;
            }
        }

        return [
            'overall_health' => $totalTools > 0 ? round(($healthyTools / $totalTools) * 100, 1) : 0,
            'total_tools' => $totalTools,
            'healthy_tools' => $healthyTools,
            'tool_status' => $healthStatus,
            'timestamp' => now()->toISOString(),
        ];
    }
}
