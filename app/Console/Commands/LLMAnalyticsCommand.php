<?php

namespace App\Console\Commands;

use App\Services\Telemetry\LLMCostTracker;
use App\Services\Telemetry\LLMPerformanceAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class LLMAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:analytics
                            {--type= : Type of analytics (cost, performance, usage)}
                            {--period=day : Time period (hour, day, week, month)}
                            {--provider= : Filter by provider}
                            {--model= : Filter by model}
                            {--format=table : Output format (table, json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate LLM cost and performance analytics reports';

    protected LLMCostTracker $costTracker;
    protected LLMPerformanceAnalyzer $performanceAnalyzer;

    public function __construct()
    {
        parent::__construct();
        $this->costTracker = new LLMCostTracker();
        $this->performanceAnalyzer = new LLMPerformanceAnalyzer();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type') ?: 'overview';
        $period = $this->option('period');
        $provider = $this->option('provider');
        $model = $this->option('model');
        $format = $this->option('format');

        $this->info("Generating LLM Analytics Report");
        $this->info("Type: {$type}, Period: {$period}, Format: {$format}");
        $this->newLine();

        switch ($type) {
            case 'cost':
                $this->generateCostReport($period, $provider, $model, $format);
                break;
            case 'performance':
                $this->generatePerformanceReport($period, $provider, $model, $format);
                break;
            case 'usage':
                $this->generateUsageReport($period, $provider, $model, $format);
                break;
            case 'overview':
            default:
                $this->generateOverviewReport($period, $format);
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Generate cost analytics report
     */
    protected function generateCostReport(string $period, ?string $provider, ?string $model, string $format): void
    {
        $this->info("📊 Cost Analytics Report ({$period})");
        $this->newLine();

        $costSummary = $this->costTracker->getCostSummary($period);
        $costTrends = $this->costTracker->getCostTrends($this->getDaysFromPeriod($period));

        if ($format === 'json') {
            $this->outputJson([
                'summary' => $costSummary,
                'trends' => $costTrends,
            ]);
            return;
        }

        // Display cost summary
        $this->table(
            ['Provider', 'Total Cost ($)', 'Budget Limit ($)', 'Usage %', 'Status'],
            array_map(function ($p) {
                $status = '✅ OK';
                if (($p['usage_percentage'] ?? 0) > 90) {
                    $status = '🚨 Critical';
                } elseif (($p['usage_percentage'] ?? 0) > 75) {
                    $status = '⚠️ Warning';
                }

                return [
                    $p['provider'],
                    number_format($p['total_cost'], 4),
                    $p['budget_limit'] ? number_format($p['budget_limit'], 2) : 'No limit',
                    $p['usage_percentage'] ? number_format($p['usage_percentage'], 1) . '%' : 'N/A',
                    $status,
                ];
            }, $costSummary['providers'])
        );

        $this->newLine();
        $this->info("💡 Cost Optimization Suggestions:");
        $this->displayOptimizationSuggestions();
    }

    /**
     * Generate performance analytics report
     */
    protected function generatePerformanceReport(string $period, ?string $provider, ?string $model, string $format): void
    {
        $this->info("⚡ Performance Analytics Report ({$period})");
        $this->newLine();

        $performanceSummary = $this->performanceAnalyzer->getPerformanceSummary($period);
        $performanceTrends = $this->performanceAnalyzer->getPerformanceTrends($this->getHoursFromPeriod($period));

        if ($format === 'json') {
            $this->outputJson([
                'summary' => $performanceSummary,
                'trends' => $performanceTrends,
            ]);
            return;
        }

        // Display performance summary
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average Response Time', number_format($performanceSummary['avg_response_time_ms']) . ' ms'],
                ['Average Tokens/Second', number_format($performanceSummary['avg_tokens_per_second'], 1)],
                ['Success Rate', number_format($performanceSummary['success_rate_percent'], 1) . '%'],
                ['Total Requests', number_format($performanceSummary['total_requests'])],
                ['Performance Score', number_format($performanceSummary['performance_score'], 1) . '/100'],
            ]
        );

        $this->newLine();
        $this->info("🏆 Top Performing Models:");
        $this->table(
            ['Model', 'Score'],
            array_map(fn($m) => [$m['model'], $m['score']], $performanceSummary['top_performing_models'])
        );

        if (!empty($performanceSummary['underperforming_models'])) {
            $this->newLine();
            $this->info("⚠️ Underperforming Models:");
            $this->table(
                ['Model', 'Score'],
                array_map(fn($m) => [$m['model'], $m['score']], $performanceSummary['underperforming_models'])
            );
        }
    }

    /**
     * Generate usage patterns report
     */
    protected function generateUsageReport(string $period, ?string $provider, ?string $model, string $format): void
    {
        $this->info("📈 Usage Patterns Report ({$period})");
        $this->newLine();

        $usagePatterns = $this->performanceAnalyzer->analyzeUsagePatterns();

        if ($format === 'json') {
            $this->outputJson($usagePatterns);
            return;
        }

        $this->info("Peak Usage Hours: " . implode(', ', $usagePatterns['peak_usage_hours']));

        $this->newLine();
        $this->info("Most Used Models:");
        $this->table(
            ['Model', 'Usage %'],
            array_map(fn($model, $usage) => [$model, $usage . '%'], array_keys($usagePatterns['most_used_models']), $usagePatterns['most_used_models'])
        );

        $this->newLine();
        $this->info("🎯 Optimization Opportunities:");
        $this->table(
            ['Type', 'Description', 'Potential Savings ($)', 'Confidence'],
            array_map(function ($opp) {
                return [
                    $opp['type'],
                    wordwrap($opp['description'], 50),
                    number_format($opp['potential_savings'], 0),
                    ucfirst($opp['confidence']),
                ];
            }, $usagePatterns['optimization_opportunities'])
        );

        $this->newLine();
        $this->info("💡 Performance Recommendations:");
        foreach ($usagePatterns['performance_recommendations'] as $rec) {
            $this->line("• {$rec}");
        }
    }

    /**
     * Generate overview report
     */
    protected function generateOverviewReport(string $period, string $format): void
    {
        $this->info("📋 LLM Analytics Overview ({$period})");
        $this->newLine();

        $costSummary = $this->costTracker->getCostSummary($period);
        $performanceSummary = $this->performanceAnalyzer->getPerformanceSummary($period);

        if ($format === 'json') {
            $this->outputJson([
                'cost_summary' => $costSummary,
                'performance_summary' => $performanceSummary,
            ]);
            return;
        }

        // Cost Overview
        $this->info("💰 Cost Overview:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cost', '$' . number_format($costSummary['total_cost'], 2)],
                ['Provider Count', count($costSummary['providers'])],
                ['Avg Cost per Provider', '$' . number_format($costSummary['total_cost'] / max(1, count($costSummary['providers'])), 2)],
            ]
        );

        $this->newLine();

        // Performance Overview
        $this->info("⚡ Performance Overview:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Avg Response Time', number_format($performanceSummary['avg_response_time_ms']) . ' ms'],
                ['Avg Throughput', number_format($performanceSummary['avg_tokens_per_second'], 1) . ' tokens/s'],
                ['Success Rate', number_format($performanceSummary['success_rate_percent'], 1) . '%'],
                ['Performance Score', number_format($performanceSummary['performance_score'], 1) . '/100'],
            ]
        );

        $this->newLine();
        $this->info("🚨 Alerts:");
        $this->displayAlerts($costSummary, $performanceSummary);
    }

    /**
     * Display cost optimization suggestions
     */
    protected function displayOptimizationSuggestions(): void
    {
        $suggestions = [
            "Consider switching to GPT-4o-mini for 80% cost reduction vs GPT-4",
            "Use model caching for repeated queries to reduce API calls",
            "Optimize prompt lengths to reduce token consumption",
            "Set up budget alerts to prevent cost overruns",
            "Use batch processing for multiple similar requests",
        ];

        foreach ($suggestions as $suggestion) {
            $this->line("• {$suggestion}");
        }
    }

    /**
     * Display alerts and warnings
     */
    protected function displayAlerts(array $costSummary, array $performanceSummary): void
    {
        $alerts = [];

        // Cost alerts
        foreach ($costSummary['providers'] as $provider) {
            if (($provider['usage_percentage'] ?? 0) > 90) {
                $alerts[] = "🚨 CRITICAL: {$provider['provider']} budget usage at {$provider['usage_percentage']}%";
            } elseif (($provider['usage_percentage'] ?? 0) > 75) {
                $alerts[] = "⚠️ WARNING: {$provider['provider']} budget usage at {$provider['usage_percentage']}%";
            }
        }

        // Performance alerts
        if ($performanceSummary['success_rate_percent'] < 95) {
            $alerts[] = "⚠️ WARNING: Success rate below 95%: {$performanceSummary['success_rate_percent']}%";
        }

        if ($performanceSummary['avg_response_time_ms'] > 5000) {
            $alerts[] = "⚠️ WARNING: Average response time above 5s: {$performanceSummary['avg_response_time_ms']}ms";
        }

        if (empty($alerts)) {
            $this->line("✅ No critical alerts at this time");
        } else {
            foreach ($alerts as $alert) {
                $this->line($alert);
            }
        }
    }

    /**
     * Output data as JSON
     */
    protected function outputJson(array $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Convert period to days for trends
     */
    protected function getDaysFromPeriod(string $period): int
    {
        return match ($period) {
            'hour' => 1,
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7,
        };
    }

    /**
     * Convert period to hours for trends
     */
    protected function getHoursFromPeriod(string $period): int
    {
        return match ($period) {
            'hour' => 1,
            'day' => 24,
            'week' => 168,
            'month' => 720,
            default => 24,
        };
    }
}