<?php

namespace App\Console\Commands;

use App\Services\Telemetry\CommandMetricsAnalyzer;
use Illuminate\Console\Command;

class GenerateCommandTelemetryReportCommand extends Command
{
    protected $signature = 'telemetry:command-report 
                            {--days=7 : Number of days to analyze}
                            {--format=table : Output format (table, json)}
                            {--output= : Output file path}';

    protected $description = 'Generate a comprehensive command telemetry report';

    public function handle(CommandMetricsAnalyzer $analyzer)
    {
        $days = (int) $this->option('days');
        $format = $this->option('format');
        $outputFile = $this->option('output');

        $this->info("Generating command telemetry report for the last {$days} days...");

        $summary = $analyzer->generateSummary($days);

        if ($format === 'json') {
            $output = json_encode($summary, JSON_PRETTY_PRINT);

            if ($outputFile) {
                file_put_contents($outputFile, $output);
                $this->info("Report saved to: {$outputFile}");
            } else {
                $this->line($output);
            }

            return;
        }

        // Table format output
        $this->displayTableReport($summary);

        if ($outputFile) {
            $output = $this->generateTextReport($summary);
            file_put_contents($outputFile, $output);
            $this->info("Report saved to: {$outputFile}");
        }
    }

    protected function displayTableReport(array $summary): void
    {
        $this->info('📊 Command Telemetry Report');
        $this->info('Generated: '.$summary['generated_at']);
        $this->info('Period: Last '.$summary['period_days'].' days');
        $this->newLine();

        // Command Popularity
        if (! empty($summary['command_popularity'])) {
            $this->info('🏆 Most Popular Commands');
            $popularityData = [];
            foreach (array_slice($summary['command_popularity'], 0, 10, true) as $command => $stats) {
                $popularityData[] = [
                    'Command' => $command,
                    'Executions' => $stats['count'],
                    'Success Rate' => $stats['success_rate'].'%',
                    'Avg Duration' => round($stats['avg_duration'], 1).'ms',
                ];
            }
            $this->table(['Command', 'Executions', 'Success Rate', 'Avg Duration'], $popularityData);
            $this->newLine();
        }

        // Performance Bottlenecks
        $bottlenecks = $summary['performance_bottlenecks'];
        if (! empty($bottlenecks['slow_commands'])) {
            $this->warn('🐌 Slowest Commands');
            $slowData = [];
            foreach (array_slice($bottlenecks['slow_commands'], 0, 5) as $slow) {
                $slowData[] = [
                    'Command' => $slow['command'],
                    'Duration' => round($slow['duration_ms'], 1).'ms',
                    'Category' => $slow['performance_category'],
                    'When' => date('M j H:i', strtotime($slow['timestamp'])),
                ];
            }
            $this->table(['Command', 'Duration', 'Category', 'When'], $slowData);
            $this->newLine();
        }

        if (! empty($bottlenecks['slow_steps'])) {
            $this->warn('🔧 Slowest Steps');
            $stepData = [];
            foreach (array_slice($bottlenecks['slow_steps'], 0, 5) as $slow) {
                $stepData[] = [
                    'Step Type' => $slow['step_type'],
                    'Duration' => round($slow['duration_ms'], 1).'ms',
                    'Category' => $slow['performance_category'],
                    'When' => date('M j H:i', strtotime($slow['timestamp'])),
                ];
            }
            $this->table(['Step Type', 'Duration', 'Category', 'When'], $stepData);
            $this->newLine();
        }

        // Error Patterns
        if (! empty($summary['error_patterns'])) {
            $this->error('❌ Error Patterns');
            $errorData = [];
            foreach ($summary['error_patterns'] as $category => $contexts) {
                $totalErrors = array_sum($contexts);
                $topContext = array_key_first($contexts);
                $errorData[] = [
                    'Category' => $category,
                    'Total Errors' => $totalErrors,
                    'Most Frequent' => $topContext,
                    'Count' => $contexts[$topContext],
                ];
            }
            $this->table(['Category', 'Total Errors', 'Most Frequent', 'Count'], $errorData);
            $this->newLine();
        }

        // Template Performance
        $templatePerf = $summary['template_performance'];
        if ($templatePerf['total_renders'] > 0) {
            $this->info('🎨 Template Performance');
            $this->line("Total Renders: {$templatePerf['total_renders']}");
            $this->line("Cache Hit Rate: {$templatePerf['cache_hit_rate']}%");
            $this->line("Average Duration: {$templatePerf['avg_duration']}ms");

            if (! empty($templatePerf['slow_renders'])) {
                $this->newLine();
                $this->warn('Slowest Template Renders:');
                $templateData = [];
                foreach (array_slice($templatePerf['slow_renders'], 0, 5) as $slow) {
                    $templateData[] = [
                        'Hash' => substr($slow['template_hash'], 0, 8),
                        'Length' => $slow['template_length'],
                        'Duration' => round($slow['duration_ms'], 1).'ms',
                        'Cache Hit' => $slow['cache_hit'] ? 'Yes' : 'No',
                    ];
                }
                $this->table(['Hash', 'Length', 'Duration', 'Cache Hit'], $templateData);
            }
        }
    }

    protected function generateTextReport(array $summary): string
    {
        $output = "Command Telemetry Report\n";
        $output .= "Generated: {$summary['generated_at']}\n";
        $output .= "Period: Last {$summary['period_days']} days\n\n";

        // Add all sections in text format
        if (! empty($summary['command_popularity'])) {
            $output .= "COMMAND POPULARITY\n";
            $output .= str_repeat('=', 50)."\n";
            foreach (array_slice($summary['command_popularity'], 0, 10, true) as $command => $stats) {
                $output .= sprintf("%-30s %5d executions, %5.1f%% success, %6.1fms avg\n",
                    $command, $stats['count'], $stats['success_rate'], $stats['avg_duration']);
            }
            $output .= "\n";
        }

        if (! empty($summary['performance_bottlenecks']['slow_commands'])) {
            $output .= "PERFORMANCE BOTTLENECKS\n";
            $output .= str_repeat('=', 50)."\n";
            foreach (array_slice($summary['performance_bottlenecks']['slow_commands'], 0, 10) as $slow) {
                $output .= sprintf("%-30s %8.1fms (%s)\n",
                    $slow['command'], $slow['duration_ms'], $slow['performance_category']);
            }
            $output .= "\n";
        }

        return $output;
    }
}
