<?php

namespace App\Console\Commands;

use App\Services\Telemetry\ToolHealthMonitor;
use Illuminate\Console\Command;

class ToolHealthCheckCommand extends Command
{
    protected $signature = 'tools:health-check 
                           {--tool= : Check specific tool only}
                           {--format=table : Output format (table, json)}';

    protected $description = 'Perform health checks on all registered tools';

    public function handle(ToolHealthMonitor $monitor): int
    {
        $specificTool = $this->option('tool');
        $format = $this->option('format');

        if ($specificTool) {
            $this->info("Checking health of tool: {$specificTool}");
            $result = [$specificTool => $monitor->checkTool($specificTool)];
        } else {
            $this->info("Checking health of all tools...");
            $result = $monitor->checkAllTools();
        }

        if ($format === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->displayHealthTable($result);
        }

        // Get system health summary
        $systemHealth = $monitor->getSystemHealth();
        
        $this->info("\n=== System Health Summary ===");
        $this->info("Overall Health: {$systemHealth['overall_health']}%");
        $this->info("Healthy Tools: {$systemHealth['healthy_tools']}/{$systemHealth['total_tools']}");

        // Exit with error code if any tools are unhealthy
        $hasUnhealthyTools = array_filter($result, fn($health) => $health['status'] !== 'healthy');
        
        return empty($hasUnhealthyTools) ? Command::SUCCESS : Command::FAILURE;
    }

    private function displayHealthTable(array $results): void
    {
        $headers = ['Tool', 'Status', 'Response Time (ms)', 'Error'];
        $rows = [];

        foreach ($results as $toolName => $health) {
            $rows[] = [
                $toolName,
                $this->formatStatus($health['status']),
                $health['response_time_ms'] ?? 'N/A',
                $health['error'] ?? '-',
            ];
        }

        $this->table($headers, $rows);
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'healthy' => '<fg=green>✓ Healthy</>',
            'unhealthy' => '<fg=red>✗ Unhealthy</>',
            'not_found' => '<fg=yellow>? Not Found</>',
            default => $status,
        };
    }
}