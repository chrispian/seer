<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;

/**
 * LLM Telemetry Service
 *
 * Specialized telemetry service for Large Language Model operations.
 * Provides comprehensive tracking, analytics, and reporting for AI interactions.
 */
class LLMTelemetry
{
    /**
     * Log a successful LLM API call with full telemetry data
     */
    public static function logLLMCall(array $data): void
    {
        // Integrate cost tracking if cost data is present
        if (isset($data['cost_usd']) || isset($data['tokens_prompt']) || isset($data['tokens_completion'])) {
            $costTracker = new LLMCostTracker;
            $costAnalysis = $costTracker->trackRequestCost($data);
            $data['cost_analysis'] = $costAnalysis;
        }

        // Integrate performance analysis if performance data is present
        if (isset($data['response_time_ms']) || isset($data['tokens_prompt']) || isset($data['tokens_completion'])) {
            $performanceAnalyzer = new LLMPerformanceAnalyzer;
            $performanceAnalysis = $performanceAnalyzer->analyzeRequestPerformance($data);
            $data['performance_analysis'] = $performanceAnalysis;
        }

        $event = [
            'event' => 'llm.call.completed',
            'data' => $data,
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->info('LLM Call Completed', $event);
    }

    /**
     * Log an LLM API call error with enhanced context
     */
    public static function logLLMError(array $data, \Exception $error): void
    {
        $event = [
            'event' => 'llm.call.error',
            'data' => array_merge($data, [
                'error_message' => $error->getMessage(),
                'error_class' => get_class($error),
                'error_category' => self::categorizeError($error),
                'error_stack' => config('app.debug') ? $error->getTraceAsString() : null,
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->error('LLM Call Error', $event);
    }

    /**
     * Log LLM performance metrics and analytics
     */
    public static function logLLMPerformance(array $metrics): void
    {
        // Integrate cost analysis if cost data is present
        if (isset($metrics['cost_usd']) || isset($metrics['tokens_prompt']) || isset($metrics['tokens_completion'])) {
            $costTracker = new LLMCostTracker;
            $costAnalysis = $costTracker->trackRequestCost($metrics);
            $metrics['cost_analysis'] = $costAnalysis;
        }

        // Integrate performance analysis
        $performanceAnalyzer = new LLMPerformanceAnalyzer;
        $performanceAnalysis = $performanceAnalyzer->analyzeRequestPerformance($metrics);
        $metrics['performance_analysis'] = $performanceAnalysis;

        $event = [
            'event' => 'llm.performance.metrics',
            'data' => array_merge($metrics, [
                'performance_category' => self::categorizePerformance($metrics),
                'efficiency_score' => self::calculateEfficiencyScore($metrics),
                'cost_effectiveness' => self::calculateCostEffectiveness($metrics),
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->info('LLM Performance Metrics', $event);
    }

    /**
     * Log LLM cost and usage analytics
     */
    public static function logLLMCost(array $costData): void
    {
        $event = [
            'event' => 'llm.cost.usage',
            'data' => array_merge($costData, [
                'cost_per_token' => self::calculateCostPerToken($costData),
                'usage_efficiency' => self::calculateUsageEfficiency($costData),
                'budget_remaining' => self::calculateBudgetRemaining($costData),
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->info('LLM Cost', $event);
    }

    /**
     * Log LLM model selection decisions
     */
    public static function logModelSelection(array $selectionData): void
    {
        $event = [
            'event' => 'llm.model.selected',
            'data' => array_merge($selectionData, [
                'selection_confidence' => self::calculateSelectionConfidence($selectionData),
                'fallback_used' => ($selectionData['source'] ?? 'unknown') === 'fallback',
                'performance_prediction' => self::predictPerformance($selectionData),
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->info('Model Selection', $event);
    }

    /**
     * Log LLM streaming events
     */
    public static function logLLMStreaming(array $streamingData): void
    {
        $event = [
            'event' => 'llm.streaming.event',
            'data' => array_merge($streamingData, [
                'tokens_per_second' => self::calculateTokensPerSecond($streamingData),
                'streaming_efficiency' => self::calculateStreamingEfficiency($streamingData),
                'user_perceived_latency' => self::calculatePerceivedLatency($streamingData),
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->info('LLM Streaming', $event);
    }

    /**
     * Log LLM quality and accuracy metrics
     */
    public static function logLLMQuality(array $qualityData): void
    {
        $event = [
            'event' => 'llm.quality.metrics',
            'data' => array_merge($qualityData, [
                'quality_score' => self::calculateQualityScore($qualityData),
                'confidence_level' => self::assessConfidence($qualityData),
                'improvement_suggestions' => self::generateImprovementSuggestions($qualityData),
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('llm-telemetry')->info('LLM Quality', $event);
    }

    /**
     * Log LLM operational health metrics
     */
    public static function logLLMHealth(array $healthData): void
    {
        $event = [
            'event' => 'llm.health.status',
            'data' => array_merge($healthData, [
                'overall_health_score' => self::calculateHealthScore($healthData),
                'availability_percentage' => self::calculateAvailability($healthData),
                'recommended_actions' => self::generateHealthRecommendations($healthData),
            ]),
            'correlation' => CorrelationContext::getContext(),
            'timestamp' => now()->toISOString(),
        ];

        $level = $healthData['status'] === 'healthy' ? 'info' : 'warning';
        Log::channel('llm-telemetry')->{$level}('LLM Health', $event);
    }

    /**
     * Analyze LLM usage patterns and trends
     */
    public static function analyzeUsagePatterns(int $hours = 24): array
    {
        // This would typically query the telemetry database
        // For now, return a placeholder structure
        return [
            'total_calls' => 0,
            'success_rate' => 0.0,
            'average_latency' => 0.0,
            'total_cost' => 0.0,
            'popular_models' => [],
            'error_patterns' => [],
            'usage_trends' => [],
            'cost_trends' => [],
        ];
    }

    /**
     * Generate LLM performance report
     */
    public static function generatePerformanceReport(int $days = 7): array
    {
        return [
            'period' => "{$days} days",
            'summary' => [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'average_response_time' => 0.0,
                'total_cost' => 0.0,
            ],
            'by_provider' => [],
            'by_model' => [],
            'by_operation_type' => [],
            'performance_trends' => [],
            'recommendations' => [],
        ];
    }

    /**
     * Calculate efficiency score based on performance metrics
     */
    private static function calculateEfficiencyScore(array $metrics): float
    {
        $latencyScore = max(0, 1 - ($metrics['response_time_ms'] ?? 0) / 10000); // Better if < 10s
        $tokenEfficiency = ($metrics['tokens_completion'] ?? 0) / max(1, $metrics['tokens_prompt'] ?? 1);
        $costEfficiency = 1 / max(0.0001, $metrics['cost_usd'] ?? 0.0001); // Lower cost is better

        return round(($latencyScore + $tokenEfficiency + $costEfficiency) / 3, 3);
    }

    /**
     * Calculate cost effectiveness ratio
     */
    private static function calculateCostEffectiveness(array $metrics): float
    {
        $totalTokens = ($metrics['tokens_prompt'] ?? 0) + ($metrics['tokens_completion'] ?? 0);
        $cost = $metrics['cost_usd'] ?? 0;

        return $totalTokens > 0 ? round($cost / $totalTokens, 6) : 0;
    }

    /**
     * Categorize performance based on metrics
     */
    private static function categorizePerformance(array $metrics): string
    {
        $latency = $metrics['response_time_ms'] ?? 0;

        if ($latency < 1000) {
            return 'excellent';
        }
        if ($latency < 3000) {
            return 'good';
        }
        if ($latency < 10000) {
            return 'fair';
        }

        return 'poor';
    }

    /**
     * Calculate cost per token
     */
    private static function calculateCostPerToken(array $costData): float
    {
        $totalTokens = ($costData['tokens_prompt'] ?? 0) + ($costData['tokens_completion'] ?? 0);
        $cost = $costData['cost_usd'] ?? 0;

        return $totalTokens > 0 ? round($cost / $totalTokens, 8) : 0;
    }

    /**
     * Calculate usage efficiency (tokens per dollar)
     */
    private static function calculateUsageEfficiency(array $costData): int
    {
        $cost = $costData['cost_usd'] ?? 0;
        if ($cost <= 0) {
            return 0;
        }

        $totalTokens = ($costData['tokens_prompt'] ?? 0) + ($costData['tokens_completion'] ?? 0);

        return (int) ($totalTokens / $cost);
    }

    /**
     * Calculate remaining budget (placeholder implementation)
     */
    private static function calculateBudgetRemaining(array $costData): ?float
    {
        // This would integrate with actual budget tracking
        // For now, return null to indicate not implemented
        return null;
    }

    /**
     * Calculate selection confidence
     */
    private static function calculateSelectionConfidence(array $selectionData): float
    {
        // Higher confidence for explicit selections vs fallbacks
        $confidenceMap = [
            'command_override' => 1.0,
            'project_preference' => 0.9,
            'vault_preference' => 0.8,
            'global_default' => 0.7,
            'fallback' => 0.5,
        ];

        return $confidenceMap[$selectionData['source'] ?? 'unknown'] ?? 0.5;
    }

    /**
     * Predict performance based on historical data (placeholder)
     */
    private static function predictPerformance(array $selectionData): ?array
    {
        // This would use historical telemetry data
        // For now, return null
        return null;
    }

    /**
     * Calculate tokens per second for streaming
     */
    private static function calculateTokensPerSecond(array $streamingData): float
    {
        $duration = $streamingData['duration_ms'] ?? 0;
        $tokens = $streamingData['tokens_completion'] ?? 0;

        return $duration > 0 ? round(($tokens / $duration) * 1000, 2) : 0;
    }

    /**
     * Calculate streaming efficiency
     */
    private static function calculateStreamingEfficiency(array $streamingData): float
    {
        $totalTime = $streamingData['duration_ms'] ?? 0;
        $firstTokenTime = $streamingData['time_to_first_token_ms'] ?? 0;

        if ($totalTime <= 0) {
            return 0;
        }

        // Efficiency is higher when first token arrives quickly relative to total time
        return round($firstTokenTime / $totalTime, 3);
    }

    /**
     * Calculate user-perceived latency
     */
    private static function calculatePerceivedLatency(array $streamingData): int
    {
        // User perception is dominated by time to first token
        return $streamingData['time_to_first_token_ms'] ?? 0;
    }

    /**
     * Calculate quality score (placeholder implementation)
     */
    private static function calculateQualityScore(array $qualityData): float
    {
        // This would analyze response quality metrics
        // For now, return a placeholder
        return 0.8;
    }

    /**
     * Assess confidence level
     */
    private static function assessConfidence(array $qualityData): string
    {
        $score = $qualityData['quality_score'] ?? 0.5;

        if ($score >= 0.9) {
            return 'high';
        }
        if ($score >= 0.7) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Generate improvement suggestions
     */
    private static function generateImprovementSuggestions(array $qualityData): array
    {
        // This would analyze metrics and suggest improvements
        return [];
    }

    /**
     * Calculate overall health score
     */
    private static function calculateHealthScore(array $healthData): float
    {
        $availability = $healthData['availability_percentage'] ?? 100;
        $errorRate = $healthData['error_rate'] ?? 0;
        $latencyScore = $healthData['average_latency_score'] ?? 1;

        return round(($availability / 100 * 0.4) + ((1 - $errorRate) * 0.4) + ($latencyScore * 0.2), 3);
    }

    /**
     * Calculate availability percentage
     */
    private static function calculateAvailability(array $healthData): float
    {
        $totalChecks = $healthData['total_checks'] ?? 1;
        $successfulChecks = $healthData['successful_checks'] ?? 1;

        return round(($successfulChecks / $totalChecks) * 100, 2);
    }

    /**
     * Generate health recommendations
     */
    private static function generateHealthRecommendations(array $healthData): array
    {
        $recommendations = [];

        if (($healthData['error_rate'] ?? 0) > 0.05) {
            $recommendations[] = 'High error rate detected - investigate API issues';
        }

        if (($healthData['average_latency_ms'] ?? 0) > 5000) {
            $recommendations[] = 'High latency detected - consider model optimization';
        }

        if (($healthData['availability_percentage'] ?? 100) < 95) {
            $recommendations[] = 'Low availability detected - check provider status';
        }

        return $recommendations;
    }

    /**
     * Categorize errors for analytics
     */
    private static function categorizeError(\Exception $e): string
    {
        $message = strtolower($e->getMessage());

        // Provider availability issues
        if (str_contains($message, 'not found') || str_contains($message, 'not available')) {
            return 'provider_unavailable';
        }

        // Authentication/authorization issues
        if (str_contains($message, 'auth') || str_contains($message, 'unauthorized') || str_contains($message, 'api key')) {
            return 'authentication_error';
        }

        // Rate limiting
        if (str_contains($message, 'rate limit') || str_contains($message, 'quota') || str_contains($message, 'too many requests')) {
            return 'rate_limit_exceeded';
        }

        // Network/connectivity issues
        if (str_contains($message, 'timeout') || str_contains($message, 'connection') || str_contains($message, 'network')) {
            return 'network_error';
        }

        // Model/parameter issues
        if (str_contains($message, 'model') || str_contains($message, 'parameter') || str_contains($message, 'invalid request')) {
            return 'request_error';
        }

        // Server-side issues
        if (str_contains($message, 'server error') || str_contains($message, 'internal error') || str_contains($message, '500')) {
            return 'server_error';
        }

        return 'unknown_error';
    }
}
