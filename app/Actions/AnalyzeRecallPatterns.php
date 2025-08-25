<?php

namespace App\Actions;

use App\Models\RecallDecision;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyzeRecallPatterns
{
    /**
     * Analyze recall patterns to provide insights for search optimization
     */
    public function __invoke(?int $userId = null, int $daysPast = 30): array
    {
        $baseQuery = RecallDecision::query()
            ->where('decided_at', '>=', now()->subDays($daysPast));
            
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }

        return [
            'summary' => $this->generateSummary($baseQuery),
            'query_patterns' => $this->analyzeQueryPatterns($baseQuery),
            'selection_metrics' => $this->calculateSelectionMetrics($baseQuery),
            'filter_usage' => $this->analyzeFilterUsage($baseQuery),
            'performance_insights' => $this->generatePerformanceInsights($baseQuery),
            'recommendations' => $this->generateRecommendations($baseQuery),
        ];
    }

    private function generateSummary(mixed $baseQuery): array
    {
        $decisions = $baseQuery->get();
        $totalSearches = $decisions->count();
        $selections = $decisions->where('action', 'select')->count();
        $dismissals = $decisions->where('action', 'dismiss')->count();

        return [
            'total_searches' => $totalSearches,
            'successful_selections' => $selections,
            'dismissals' => $dismissals,
            'success_rate' => $totalSearches > 0 ? round(($selections / $totalSearches) * 100, 2) : 0,
            'average_results_per_search' => $totalSearches > 0 ? 
                round($decisions->avg('total_results'), 1) : 0,
        ];
    }

    private function analyzeQueryPatterns(mixed $baseQuery): array
    {
        $decisions = $baseQuery->get();
        
        // Most common queries
        $queryFrequency = $decisions->groupBy('query')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->take(10);

        // Most successful queries (highest selection rate)
        $querySuccess = $decisions->groupBy('query')
            ->map(function($group) {
                $total = $group->count();
                $selected = $group->where('action', 'select')->count();
                return [
                    'total' => $total,
                    'selected' => $selected,
                    'success_rate' => $total > 0 ? ($selected / $total) * 100 : 0
                ];
            })
            ->where('total', '>=', 3) // Only queries with 3+ uses
            ->sortByDesc('success_rate')
            ->take(10);

        // Search term analysis
        $allTerms = $decisions->flatMap(function($decision) {
            $parsed = $decision->parsed_query;
            if (isset($parsed['search_terms']) && !empty($parsed['search_terms'])) {
                return explode(' ', strtolower(trim($parsed['search_terms'])));
            }
            return [];
        })->filter();

        $commonTerms = $allTerms->countBy()
            ->sortDesc()
            ->take(20);

        return [
            'most_frequent_queries' => $queryFrequency->toArray(),
            'most_successful_queries' => $querySuccess->toArray(),
            'common_search_terms' => $commonTerms->toArray(),
        ];
    }

    private function calculateSelectionMetrics(mixed $baseQuery): array
    {
        $selections = $baseQuery->where('action', 'select')->get();
        
        if ($selections->isEmpty()) {
            return [
                'average_click_position' => 0,
                'click_distribution' => [],
                'top_n_performance' => [],
            ];
        }

        // Click position analysis
        $positions = $selections->pluck('selected_index')->map(fn($idx) => $idx + 1);
        $averagePosition = $positions->avg();
        
        // Click distribution by position
        $clickDistribution = $positions->countBy()->sortKeys()->toArray();
        
        // Top-N performance
        $topNPerformance = [
            'top_1' => $positions->where('<=', 1)->count(),
            'top_3' => $positions->where('<=', 3)->count(),
            'top_5' => $positions->where('<=', 5)->count(),
            'top_10' => $positions->where('<=', 10)->count(),
        ];

        $total = $selections->count();
        $topNPerformance = array_map(fn($count) => [
            'count' => $count,
            'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
        ], $topNPerformance);

        return [
            'average_click_position' => round($averagePosition, 2),
            'click_distribution' => $clickDistribution,
            'top_n_performance' => $topNPerformance,
        ];
    }

    private function analyzeFilterUsage(mixed $baseQuery): array
    {
        $decisions = $baseQuery->get();
        
        $filterUsage = [];
        $filterSuccessRates = [];
        
        foreach ($decisions as $decision) {
            $parsed = $decision->parsed_query;
            if (isset($parsed['filters']) && is_array($parsed['filters'])) {
                foreach ($parsed['filters'] as $filter) {
                    $filterType = $filter['type'] ?? 'unknown';
                    
                    if (!isset($filterUsage[$filterType])) {
                        $filterUsage[$filterType] = 0;
                        $filterSuccessRates[$filterType] = ['total' => 0, 'selected' => 0];
                    }
                    
                    $filterUsage[$filterType]++;
                    $filterSuccessRates[$filterType]['total']++;
                    
                    if ($decision->action === 'select') {
                        $filterSuccessRates[$filterType]['selected']++;
                    }
                }
            }
        }
        
        // Calculate success rates
        foreach ($filterSuccessRates as $type => &$stats) {
            $stats['success_rate'] = $stats['total'] > 0 ? 
                round(($stats['selected'] / $stats['total']) * 100, 1) : 0;
        }
        
        return [
            'usage_frequency' => $filterUsage,
            'success_rates' => $filterSuccessRates,
        ];
    }

    private function generatePerformanceInsights(mixed $baseQuery): array
    {
        $decisions = $baseQuery->get();
        
        // Time-based patterns
        $hourlyPattern = $decisions->groupBy(function($decision) {
            return $decision->decided_at->format('H');
        })->map->count()->sortKeys();
        
        $dailyPattern = $decisions->groupBy(function($decision) {
            return $decision->decided_at->format('N'); // 1=Monday, 7=Sunday
        })->map->count()->sortKeys();
        
        // Query length analysis
        $queryLengths = $decisions->map(function($decision) {
            return strlen($decision->query);
        });
        
        $lengthStats = [
            'average' => round($queryLengths->avg(), 1),
            'min' => $queryLengths->min(),
            'max' => $queryLengths->max(),
        ];
        
        return [
            'hourly_usage_pattern' => $hourlyPattern->toArray(),
            'daily_usage_pattern' => $dailyPattern->toArray(),
            'query_length_stats' => $lengthStats,
        ];
    }

    private function generateRecommendations(mixed $baseQuery): array
    {
        $decisions = $baseQuery->get();
        $recommendations = [];
        
        // Check success rate
        $successRate = $this->generateSummary($baseQuery)['success_rate'];
        if ($successRate < 60) {
            $recommendations[] = [
                'type' => 'search_quality',
                'message' => 'Search success rate is low. Consider improving result ranking or query parsing.',
                'priority' => 'high'
            ];
        }
        
        // Check average position
        $selections = $decisions->where('action', 'select');
        if ($selections->isNotEmpty()) {
            $avgPosition = $selections->avg('selected_index') + 1;
            if ($avgPosition > 3) {
                $recommendations[] = [
                    'type' => 'ranking',
                    'message' => 'Users typically select results beyond position 3. Consider improving ranking algorithm.',
                    'priority' => 'medium'
                ];
            }
        }
        
        // Check common failed queries
        $failedQueries = $decisions->where('action', 'dismiss')
            ->groupBy('query')
            ->filter(fn($group) => $group->count() >= 3)
            ->keys();
            
        if ($failedQueries->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'failed_queries',
                'message' => 'Several queries consistently fail. Review: ' . $failedQueries->take(3)->implode(', '),
                'priority' => 'medium'
            ];
        }
        
        return $recommendations;
    }
}