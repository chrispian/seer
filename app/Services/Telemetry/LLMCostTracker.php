<?php

namespace App\Services\Telemetry;

use App\Models\AICredential;
use App\Models\Provider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LLMCostTracker
{
    protected array $costRates;

    protected array $budgetLimits;

    public function __construct()
    {
        $this->costRates = config('fragments.models.cost_rates', []);
        $this->budgetLimits = config('fragments.cost_limits', []);
    }

    /**
     * Track cost for a single LLM request
     */
    public function trackRequestCost(array $requestData): array
    {
        $startTime = microtime(true);

        $provider = $requestData['provider'];
        $model = $requestData['model'];
        $tokensPrompt = $requestData['tokens_prompt'] ?? 0;
        $tokensCompletion = $requestData['tokens_completion'] ?? 0;
        $cost = $requestData['cost_usd'] ?? 0;

        // Calculate cost if not provided
        if ($cost === 0) {
            $cost = $this->calculateCost($provider, $model, $tokensPrompt, $tokensCompletion);
        }

        // Update provider and credential usage
        $this->updateUsageStats($provider, $cost);

        // Check budget limits
        $budgetStatus = $this->checkBudgetLimits($provider, $cost);

        // Generate cost analytics
        $analytics = $this->generateCostAnalytics($provider, $model, $cost, $tokensPrompt, $tokensCompletion);

        $processingTime = microtime(true) - $startTime;

        // Log comprehensive cost tracking
        LLMTelemetry::logLLMCost([
            'provider' => $provider,
            'model' => $model,
            'tokens_prompt' => $tokensPrompt,
            'tokens_completion' => $tokensCompletion,
            'cost_usd' => $cost,
            'cost_per_token' => $this->calculateCostPerToken($cost, $tokensPrompt, $tokensCompletion),
            'budget_status' => $budgetStatus,
            'efficiency_score' => $this->calculateEfficiencyScore($cost, $tokensPrompt, $tokensCompletion),
            'processing_time_ms' => round($processingTime * 1000, 2),
            'analytics' => $analytics,
        ]);

        return [
            'cost_usd' => $cost,
            'budget_status' => $budgetStatus,
            'analytics' => $analytics,
        ];
    }

    /**
     * Calculate cost for token usage
     */
    public function calculateCost(string $provider, string $model, int $tokensPrompt, int $tokensCompletion): float
    {
        // Get cost rates for the model
        $modelRates = $this->costRates[$model] ?? null;

        // Fall back to provider defaults if model not found
        if (! $modelRates) {
            $providerDefaults = $this->costRates[$provider]['default'] ?? null;
            if ($providerDefaults) {
                $modelRates = $providerDefaults;
            }
        }

        if (! $modelRates) {
            Log::warning('No cost rates found for model', [
                'provider' => $provider,
                'model' => $model,
            ]);

            return 0.0;
        }

        $inputCost = ($tokensPrompt / 1000) * ($modelRates['input_per_thousand'] ?? 0);
        $outputCost = ($tokensCompletion / 1000) * ($modelRates['output_per_thousand'] ?? 0);

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Update usage statistics for providers and credentials
     */
    protected function updateUsageStats(string $provider, float $cost): void
    {
        try {
            // Update provider total cost
            $providerModel = Provider::where('provider', $provider)->first();
            if ($providerModel) {
                $providerModel->incrementUsage($cost);
            }

            // Update active credential cost
            $credential = AICredential::getActiveEnabledCredential($provider);
            if ($credential) {
                $credential->updateUsageStats($cost);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update usage stats', [
                'provider' => $provider,
                'cost' => $cost,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check budget limits and return status
     */
    public function checkBudgetLimits(string $provider, float $cost): array
    {
        $status = [
            'within_budget' => true,
            'current_spend' => 0.0,
            'budget_limit' => null,
            'remaining_budget' => null,
            'alert_level' => 'none', // none, warning, critical
        ];

        try {
            $providerModel = Provider::where('provider', $provider)->first();
            if ($providerModel) {
                $currentSpend = $providerModel->total_cost;
                $budgetLimit = $this->budgetLimits[$provider] ?? null;

                if ($budgetLimit) {
                    $remainingBudget = $budgetLimit - $currentSpend - $cost;
                    $status['current_spend'] = $currentSpend;
                    $status['budget_limit'] = $budgetLimit;
                    $status['remaining_budget'] = max(0, $remainingBudget);

                    // Check alert levels
                    $usagePercentage = (($currentSpend + $cost) / $budgetLimit) * 100;
                    if ($usagePercentage >= 90) {
                        $status['alert_level'] = 'critical';
                        $status['within_budget'] = false;
                    } elseif ($usagePercentage >= 75) {
                        $status['alert_level'] = 'warning';
                    }

                    if ($remainingBudget < 0) {
                        $status['within_budget'] = false;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check budget limits', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
        }

        return $status;
    }

    /**
     * Generate cost analytics and insights
     */
    protected function generateCostAnalytics(string $provider, string $model, float $cost, int $tokensPrompt, int $tokensCompletion): array
    {
        $totalTokens = $tokensPrompt + $tokensCompletion;
        $costPerToken = $totalTokens > 0 ? $cost / $totalTokens : 0;

        // Get historical cost data for comparison
        $historicalAvg = $this->getHistoricalAverageCost($provider, $model);

        return [
            'cost_per_token_usd' => round($costPerToken, 8),
            'cost_efficiency' => $this->calculateCostEfficiency($cost, $totalTokens),
            'historical_comparison' => [
                'average_cost_per_token' => $historicalAvg,
                'cost_variance' => $historicalAvg > 0 ? round((($costPerToken - $historicalAvg) / $historicalAvg) * 100, 2) : 0,
            ],
            'optimization_suggestions' => $this->generateOptimizationSuggestions($provider, $model, $cost, $totalTokens),
        ];
    }

    /**
     * Calculate cost per token
     */
    protected function calculateCostPerToken(float $cost, int $tokensPrompt, int $tokensCompletion): float
    {
        $totalTokens = $tokensPrompt + $tokensCompletion;

        return $totalTokens > 0 ? round($cost / $totalTokens, 8) : 0;
    }

    /**
     * Calculate efficiency score (tokens per dollar)
     */
    protected function calculateEfficiencyScore(float $cost, int $tokensPrompt, int $tokensCompletion): int
    {
        if ($cost <= 0) {
            return 0;
        }
        $totalTokens = $tokensPrompt + $tokensCompletion;

        return (int) ($totalTokens / $cost);
    }

    /**
     * Calculate cost efficiency ratio
     */
    protected function calculateCostEfficiency(float $cost, int $totalTokens): float
    {
        return $totalTokens > 0 ? round($cost / $totalTokens, 6) : 0;
    }

    /**
     * Get historical average cost for provider/model
     */
    protected function getHistoricalAverageCost(string $provider, string $model): float
    {
        $cacheKey = "llm_cost_avg_{$provider}_{$model}";

        return Cache::remember($cacheKey, 3600, function () {
            // This would typically query telemetry logs or a cost analytics table
            // For now, return a placeholder
            return 0.0001; // $0.0001 per token average
        });
    }

    /**
     * Generate optimization suggestions based on cost patterns
     */
    protected function generateOptimizationSuggestions(string $provider, string $model, float $cost, int $totalTokens): array
    {
        $suggestions = [];

        // Check if cheaper alternatives exist
        $cheaperModels = $this->findCheaperAlternatives($provider, $model, $cost, $totalTokens);
        if (! empty($cheaperModels)) {
            $suggestions[] = [
                'type' => 'model_switch',
                'message' => 'Consider switching to cheaper models: '.implode(', ', $cheaperModels),
                'potential_savings' => 'up to 50%',
            ];
        }

        // Check for high cost per token
        $costPerToken = $this->calculateCostPerToken($cost, $totalTokens, 0);
        if ($costPerToken > 0.001) { // More than $0.001 per token
            $suggestions[] = [
                'type' => 'efficiency',
                'message' => 'High cost per token detected. Consider optimizing prompts or using smaller models.',
                'potential_savings' => '20-40%',
            ];
        }

        return $suggestions;
    }

    /**
     * Find cheaper alternative models
     */
    protected function findCheaperAlternatives(string $provider, string $model, float $currentCost, int $totalTokens): array
    {
        $alternatives = [];
        $currentCostPerToken = $currentCost / max(1, $totalTokens);

        foreach ($this->costRates as $altModel => $rates) {
            if (str_starts_with($altModel, $provider.'/') || ! str_contains($altModel, '/')) {
                $altCost = $this->calculateCost($provider, $altModel, $totalTokens, 0);
                $altCostPerToken = $altCost / max(1, $totalTokens);

                if ($altCostPerToken < $currentCostPerToken * 0.8) { // At least 20% cheaper
                    $alternatives[] = $altModel;
                }
            }
        }

        return array_slice($alternatives, 0, 3); // Return top 3 alternatives
    }

    /**
     * Get cost summary for a time period
     */
    public function getCostSummary(string $period = 'day'): array
    {
        $startDate = match ($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subDay(),
        };

        // This would typically query telemetry logs or cost analytics
        // For now, return provider totals from database
        $providers = Provider::all();
        $summary = [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'total_cost' => 0.0,
            'providers' => [],
        ];

        foreach ($providers as $provider) {
            $providerData = [
                'provider' => $provider->provider,
                'total_cost' => (float) $provider->total_cost,
                'budget_limit' => $this->budgetLimits[$provider->provider] ?? null,
                'usage_percentage' => null,
            ];

            if ($providerData['budget_limit']) {
                $providerData['usage_percentage'] = round(($providerData['total_cost'] / $providerData['budget_limit']) * 100, 2);
            }

            $summary['providers'][] = $providerData;
            $summary['total_cost'] += $providerData['total_cost'];
        }

        return $summary;
    }

    /**
     * Get cost trends over time
     */
    public function getCostTrends(int $days = 7): array
    {
        $trends = [];
        $endDate = now();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $endDate->copy()->subDays($i)->format('Y-m-d');

            // This would query actual cost data
            // For now, return placeholder data
            $trends[$date] = [
                'date' => $date,
                'total_cost' => rand(10, 100) / 100, // Random cost between $0.10 and $1.00
                'requests_count' => rand(50, 200),
            ];
        }

        return $trends;
    }
}
