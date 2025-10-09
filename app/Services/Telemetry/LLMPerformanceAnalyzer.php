<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LLMPerformanceAnalyzer
{
    protected array $performanceThresholds;

    public function __construct()
    {
        $this->performanceThresholds = config('fragments.performance_thresholds', [
            'max_response_time_ms' => 30000, // 30 seconds
            'target_response_time_ms' => 5000, // 5 seconds
            'min_tokens_per_second' => 10,
            'max_error_rate' => 0.05, // 5%
        ]);
    }

    /**
     * Analyze performance for a single LLM request
     */
    public function analyzeRequestPerformance(array $requestData): array
    {
        $startTime = microtime(true);

        $provider = $requestData['provider'];
        $model = $requestData['model'];
        $responseTime = $requestData['response_time_ms'] ?? 0;
        $tokensPrompt = $requestData['tokens_prompt'] ?? 0;
        $tokensCompletion = $requestData['tokens_completion'] ?? 0;
        $success = $requestData['success'] ?? true;
        $errorType = $requestData['error_type'] ?? null;

        // Calculate performance metrics
        $metrics = $this->calculatePerformanceMetrics($responseTime, $tokensPrompt, $tokensCompletion, $success);

        // Compare against benchmarks
        $benchmarks = $this->getPerformanceBenchmarks($provider, $model);

        // Generate performance insights
        $insights = $this->generatePerformanceInsights($metrics, $benchmarks, $provider, $model);

        // Check for performance issues
        $issues = $this->identifyPerformanceIssues($metrics, $benchmarks);

        $analysisTime = microtime(true) - $startTime;

        // Log comprehensive performance analysis
        LLMTelemetry::logLLMCall([
            'event_type' => 'performance_analysis',
            'provider' => $provider,
            'model' => $model,
            'response_time_ms' => $responseTime,
            'tokens_prompt' => $tokensPrompt,
            'tokens_completion' => $tokensCompletion,
            'success' => $success,
            'error_type' => $errorType,
            'performance_metrics' => $metrics,
            'benchmarks' => $benchmarks,
            'insights' => $insights,
            'issues' => $issues,
            'analysis_time_ms' => round($analysisTime * 1000, 2),
        ]);

        return [
            'metrics' => $metrics,
            'benchmarks' => $benchmarks,
            'insights' => $insights,
            'issues' => $issues,
            'performance_score' => $this->calculatePerformanceScore($metrics, $benchmarks),
        ];
    }

    /**
     * Calculate performance metrics
     */
    protected function calculatePerformanceMetrics(float $responseTime, int $tokensPrompt, int $tokensCompletion, bool $success): array
    {
        $totalTokens = $tokensPrompt + $tokensCompletion;
        $tokensPerSecond = $responseTime > 0 ? ($totalTokens / $responseTime) * 1000 : 0;

        return [
            'response_time_ms' => round($responseTime, 2),
            'total_tokens' => $totalTokens,
            'tokens_per_second' => round($tokensPerSecond, 2),
            'success_rate' => $success ? 100 : 0,
            'time_to_first_token' => null, // Would need streaming data
            'completion_tokens_per_second' => $responseTime > 0 ? ($tokensCompletion / $responseTime) * 1000 : 0,
            'efficiency_score' => $this->calculateEfficiencyScore($responseTime, $totalTokens),
        ];
    }

    /**
     * Get performance benchmarks for provider/model
     */
    protected function getPerformanceBenchmarks(string $provider, string $model): array
    {
        $cacheKey = "llm_perf_benchmarks_{$provider}_{$model}";

        return Cache::remember($cacheKey, 3600, function () use ($provider, $model) {
            // This would typically query historical telemetry data
            // For now, return baseline benchmarks
            return [
                'avg_response_time_ms' => $this->getBaselineResponseTime($provider, $model),
                'avg_tokens_per_second' => $this->getBaselineTokensPerSecond($provider, $model),
                'success_rate_percent' => 95.0,
                'p95_response_time_ms' => $this->getBaselineResponseTime($provider, $model) * 2,
                'benchmark_date' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get baseline response time for provider/model
     */
    protected function getBaselineResponseTime(string $provider, string $model): float
    {
        // Provider-specific baselines
        $baselines = [
            'openai' => [
                'gpt-4o' => 2500,
                'gpt-4o-mini' => 1500,
                'gpt-3.5-turbo' => 1200,
            ],
            'anthropic' => [
                'claude-3-5-sonnet-latest' => 3000,
                'claude-3-5-haiku-latest' => 1800,
            ],
            'ollama' => [
                'llama3:latest' => 800,
                'llama3:8b' => 600,
            ],
        ];

        return $baselines[$provider][$model] ?? 2000; // Default 2 seconds
    }

    /**
     * Get baseline tokens per second for provider/model
     */
    protected function getBaselineTokensPerSecond(string $provider, string $model): float
    {
        // Provider-specific token rates
        $rates = [
            'openai' => [
                'gpt-4o' => 80,
                'gpt-4o-mini' => 100,
                'gpt-3.5-turbo' => 120,
            ],
            'anthropic' => [
                'claude-3-5-sonnet-latest' => 60,
                'claude-3-5-haiku-latest' => 80,
            ],
            'ollama' => [
                'llama3:latest' => 40,
                'llama3:8b' => 50,
            ],
        ];

        return $rates[$provider][$model] ?? 50; // Default 50 tokens/second
    }

    /**
     * Generate performance insights
     */
    protected function generatePerformanceInsights(array $metrics, array $benchmarks, string $provider, string $model): array
    {
        $insights = [];

        // Response time analysis
        $responseTimeDiff = $metrics['response_time_ms'] - $benchmarks['avg_response_time_ms'];
        if ($responseTimeDiff > 1000) { // More than 1 second slower
            $insights[] = [
                'type' => 'response_time',
                'severity' => 'warning',
                'message' => "Response time is " . round($responseTimeDiff / 1000, 1) . "s slower than benchmark",
                'recommendation' => 'Consider using a faster model or optimizing prompts',
            ];
        } elseif ($responseTimeDiff < -500) { // More than 0.5 seconds faster
            $insights[] = [
                'type' => 'response_time',
                'severity' => 'positive',
                'message' => "Response time is " . round(abs($responseTimeDiff) / 1000, 1) . "s faster than benchmark",
                'recommendation' => 'Good performance maintained',
            ];
        }

        // Token throughput analysis
        $tokenRateDiff = $metrics['tokens_per_second'] - $benchmarks['avg_tokens_per_second'];
        if ($tokenRateDiff < -10) { // Significantly slower token rate
            $insights[] = [
                'type' => 'throughput',
                'severity' => 'warning',
                'message' => "Token throughput is " . abs($tokenRateDiff) . " tokens/s slower than benchmark",
                'recommendation' => 'Consider model optimization or provider switching',
            ];
        }

        // Success rate analysis
        if ($metrics['success_rate'] < $benchmarks['success_rate_percent']) {
            $insights[] = [
                'type' => 'reliability',
                'severity' => 'error',
                'message' => "Success rate below benchmark: {$metrics['success_rate']}% vs {$benchmarks['success_rate_percent']}%",
                'recommendation' => 'Investigate error patterns and consider fallback providers',
            ];
        }

        return $insights;
    }

    /**
     * Identify performance issues
     */
    protected function identifyPerformanceIssues(array $metrics, array $benchmarks): array
    {
        $issues = [];

        // Check against thresholds
        if ($metrics['response_time_ms'] > $this->performanceThresholds['max_response_time_ms']) {
            $issues[] = [
                'type' => 'timeout',
                'severity' => 'critical',
                'message' => "Response time exceeded maximum threshold: {$metrics['response_time_ms']}ms",
            ];
        }

        if ($metrics['tokens_per_second'] < $this->performanceThresholds['min_tokens_per_second']) {
            $issues[] = [
                'type' => 'throughput',
                'severity' => 'warning',
                'message' => "Token throughput below minimum: {$metrics['tokens_per_second']} tokens/s",
            ];
        }

        if ($metrics['success_rate'] < (100 - ($this->performanceThresholds['max_error_rate'] * 100))) {
            $issues[] = [
                'type' => 'reliability',
                'severity' => 'error',
                'message' => "Error rate above threshold: " . (100 - $metrics['success_rate']) . "%",
            ];
        }

        return $issues;
    }

    /**
     * Calculate efficiency score
     */
    protected function calculateEfficiencyScore(float $responseTime, int $totalTokens): float
    {
        if ($responseTime <= 0 || $totalTokens <= 0) return 0;

        // Efficiency = tokens per second (higher is better)
        $tokensPerSecond = ($totalTokens / $responseTime) * 1000;

        // Normalize to 0-100 scale (assuming 100 tokens/s is perfect)
        return min(100, ($tokensPerSecond / 100) * 100);
    }

    /**
     * Calculate overall performance score
     */
    protected function calculatePerformanceScore(array $metrics, array $benchmarks): float
    {
        $scores = [];

        // Response time score (lower time = higher score)
        if ($benchmarks['avg_response_time_ms'] > 0) {
            $timeRatio = $benchmarks['avg_response_time_ms'] / max(1, $metrics['response_time_ms']);
            $scores[] = min(100, $timeRatio * 50); // Max 50 points for response time
        }

        // Throughput score
        if ($benchmarks['avg_tokens_per_second'] > 0) {
            $throughputRatio = $metrics['tokens_per_second'] / $benchmarks['avg_tokens_per_second'];
            $scores[] = min(30, $throughputRatio * 30); // Max 30 points for throughput
        }

        // Success rate score
        $successScore = ($metrics['success_rate'] / 100) * 20; // Max 20 points for success rate
        $scores[] = $successScore;

        return round(array_sum($scores), 1);
    }

    /**
     * Get performance summary for a time period
     */
    public function getPerformanceSummary(string $period = 'day'): array
    {
        $startDate = match ($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subDay(),
        };

        // This would typically query telemetry logs
        // For now, return mock data
        return [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'avg_response_time_ms' => rand(1000, 5000),
            'avg_tokens_per_second' => rand(30, 100),
            'success_rate_percent' => rand(90, 99),
            'total_requests' => rand(1000, 5000),
            'performance_score' => rand(70, 95),
            'top_performing_models' => [
                ['model' => 'gpt-4o-mini', 'score' => 92],
                ['model' => 'claude-3-5-haiku-latest', 'score' => 88],
                ['model' => 'llama3:8b', 'score' => 85],
            ],
            'underperforming_models' => [
                ['model' => 'gpt-4', 'score' => 65],
            ],
        ];
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends(int $hours = 24): array
    {
        $trends = [];
        $endTime = now();

        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = $endTime->copy()->subHours($i)->format('Y-m-d H:00');

            $trends[$hour] = [
                'hour' => $hour,
                'avg_response_time_ms' => rand(1500, 4000),
                'avg_tokens_per_second' => rand(40, 90),
                'success_rate_percent' => rand(92, 98),
                'request_count' => rand(50, 200),
            ];
        }

        return $trends;
    }

    /**
     * Analyze usage patterns and provide optimization recommendations
     */
    public function analyzeUsagePatterns(): array
    {
        // This would analyze historical usage data
        // For now, return mock optimization recommendations
        return [
            'peak_usage_hours' => ['14:00', '15:00', '16:00'],
            'most_used_models' => [
                'gpt-4o-mini' => 45,
                'claude-3-5-haiku-latest' => 30,
                'llama3:latest' => 25,
            ],
            'optimization_opportunities' => [
                [
                    'type' => 'model_switch',
                    'description' => 'Switch from GPT-4 to GPT-4o-mini for 80% cost reduction with similar quality',
                    'potential_savings' => 800,
                    'confidence' => 'high',
                ],
                [
                    'type' => 'caching',
                    'description' => 'Implement response caching for repeated queries',
                    'potential_savings' => 300,
                    'confidence' => 'medium',
                ],
                [
                    'type' => 'prompt_optimization',
                    'description' => 'Optimize prompt lengths to reduce token usage',
                    'potential_savings' => 200,
                    'confidence' => 'medium',
                ],
            ],
            'performance_recommendations' => [
                'Use streaming responses for better perceived performance',
                'Implement request queuing during peak hours',
                'Consider model warm-up for frequently used models',
            ],
        ];
    }

    /**
     * Get model performance comparison
     */
    public function compareModelPerformance(array $models = []): array
    {
        if (empty($models)) {
            $models = ['gpt-4o-mini', 'claude-3-5-haiku-latest', 'llama3:8b', 'gpt-3.5-turbo'];
        }

        $comparison = [];
        foreach ($models as $model) {
            $comparison[$model] = [
                'model' => $model,
                'avg_response_time_ms' => $this->getBaselineResponseTime($this->extractProvider($model), $model),
                'avg_tokens_per_second' => $this->getBaselineTokensPerSecond($this->extractProvider($model), $model),
                'estimated_cost_per_1k_tokens' => rand(1, 50) / 100, // Mock cost data
                'success_rate_percent' => rand(90, 98),
                'performance_score' => rand(70, 95),
            ];
        }

        return $comparison;
    }

    /**
     * Extract provider from model name
     */
    protected function extractProvider(string $model): string
    {
        if (str_contains($model, '/')) {
            return explode('/', $model)[0];
        }

        // Guess provider based on model name
        if (str_starts_with($model, 'gpt-')) return 'openai';
        if (str_starts_with($model, 'claude-')) return 'anthropic';
        if (str_contains($model, 'llama')) return 'ollama';

        return 'unknown';
    }
}