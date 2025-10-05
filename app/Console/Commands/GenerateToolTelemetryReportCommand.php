<?php

namespace App\Console\Commands;

use App\Services\Telemetry\ToolHealthMonitor;
use App\Services\Telemetry\ToolMetricsAnalyzer;
use Illuminate\Console\Command;

class GenerateToolTelemetryReportCommand extends Command
{
    protected $signature = 'telemetry:tool-report 
                           {--days=7 : Number of days to analyze}
                           {--format=text : Output format (text, json)}
                           {--output= : Output file path}';

    protected $description = 'Generate a comprehensive tool telemetry report';

    public function handle(ToolMetricsAnalyzer $analyzer, ToolHealthMonitor $monitor): int
    {
        $days = (int) $this->option('days');
        $format = $this->option('format');
        $outputPath = $this->option('output');

        $this->info("Generating tool telemetry report for the last {$days} days...");

        // Collect current health status
        $this->info("Checking tool health...");
        $healthResults = $monitor->checkAllTools();
        $systemHealth = $monitor->getSystemHealth();

        // Analyze historical metrics
        $this->info("Analyzing performance metrics...");
        $performanceMetrics = $analyzer->analyzePerformance($days);
        
        $this->info("Analyzing usage patterns...");
        $usagePatterns = $analyzer->analyzeUsagePatterns($days);
        
        $this->info("Analyzing error patterns...");
        $errorAnalysis = $analyzer->analyzeErrors($days);

        // Generate comprehensive report
        $report = [
            'report_info' => [
                'generated_at' => now()->toISOString(),
                'period_days' => $days,
                'analysis_start' => now()->subDays($days)->toISOString(),
                'analysis_end' => now()->toISOString(),
            ],
            'system_health' => $systemHealth,
            'tool_health' => $healthResults,
            'performance_metrics' => $performanceMetrics,
            'usage_patterns' => $usagePatterns,
            'error_analysis' => $errorAnalysis,
        ];

        // Output report
        if ($format === 'json') {
            $output = json_encode($report, JSON_PRETTY_PRINT);
        } else {
            $output = $this->formatTextReport($report);
        }

        if ($outputPath) {
            file_put_contents($outputPath, $output);
            $this->info("Report saved to: {$outputPath}");
        } else {
            $this->line($output);
        }

        // Summary
        $this->info("\n=== Report Summary ===");
        $this->info("System Health: {$systemHealth['overall_health']}%");
        $this->info("Healthy Tools: {$systemHealth['healthy_tools']}/{$systemHealth['total_tools']}");
        
        if (isset($performanceMetrics['summary']['total_invocations'])) {
            $this->info("Total Invocations: {$performanceMetrics['summary']['total_invocations']}");
        }
        
        if (isset($errorAnalysis['summary']['total_errors'])) {
            $errorRate = $errorAnalysis['summary']['error_rate'] ?? 0;
            $this->info("Error Rate: " . round($errorRate * 100, 2) . "%");
        }

        return Command::SUCCESS;
    }

    private function formatTextReport(array $report): string
    {
        $output = [];
        
        $output[] = "=== TOOL TELEMETRY REPORT ===";
        $output[] = "Generated: " . $report['report_info']['generated_at'];
        $output[] = "Period: " . $report['report_info']['period_days'] . " days";
        $output[] = "";

        // System Health
        $health = $report['system_health'];
        $output[] = "=== SYSTEM HEALTH ===";
        $output[] = "Overall Health: {$health['overall_health']}%";
        $output[] = "Healthy Tools: {$health['healthy_tools']}/{$health['total_tools']}";
        $output[] = "";

        // Tool Health Details
        $output[] = "=== TOOL HEALTH DETAILS ===";
        foreach ($report['tool_health'] as $toolName => $toolHealth) {
            $status = $toolHealth['status'];
            $responseTime = $toolHealth['response_time_ms'] ?? 'N/A';
            $output[] = "  {$toolName}: {$status} ({$responseTime}ms)";
            
            if (isset($toolHealth['error'])) {
                $output[] = "    Error: {$toolHealth['error']}";
            }
        }
        $output[] = "";

        // Performance Summary
        if (isset($report['performance_metrics']['summary'])) {
            $perf = $report['performance_metrics']['summary'];
            $output[] = "=== PERFORMANCE SUMMARY ===";
            $output[] = "Total Invocations: " . ($perf['total_invocations'] ?? 'N/A');
            $output[] = "Average Duration: " . round($perf['avg_duration_ms'] ?? 0, 2) . "ms";
            $output[] = "95th Percentile: " . round($perf['p95_duration_ms'] ?? 0, 2) . "ms";
            $output[] = "";
        }

        // Usage Patterns
        if (isset($report['usage_patterns']['tool_popularity'])) {
            $output[] = "=== TOOL POPULARITY ===";
            foreach ($report['usage_patterns']['tool_popularity'] as $tool => $count) {
                $output[] = "  {$tool}: {$count} invocations";
            }
            $output[] = "";
        }

        // Error Analysis
        if (isset($report['error_analysis']['summary'])) {
            $errors = $report['error_analysis']['summary'];
            $output[] = "=== ERROR ANALYSIS ===";
            $output[] = "Total Errors: " . ($errors['total_errors'] ?? 0);
            $output[] = "Error Rate: " . round(($errors['error_rate'] ?? 0) * 100, 2) . "%";
            
            if (isset($report['error_analysis']['by_tool'])) {
                $output[] = "\nErrors by Tool:";
                foreach ($report['error_analysis']['by_tool'] as $tool => $errorCount) {
                    $output[] = "  {$tool}: {$errorCount}";
                }
            }
            $output[] = "";
        }

        return implode("\n", $output);
    }
}