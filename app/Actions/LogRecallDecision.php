<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Models\RecallDecision;
use Illuminate\Support\Facades\Log;

class LogRecallDecision
{
    /**
     * Log a recall decision to track user behavior and improve search ranking
     */
    public function __invoke(
        string $query,
        array $results,
        ?Fragment $selectedFragment = null,
        ?int $selectedIndex = null,
        string $action = 'select'
    ): RecallDecision {
        Log::debug('LogRecallDecision::invoke()', [
            'query' => $query,
            'results_count' => count($results),
            'selected_fragment_id' => $selectedFragment?->id,
            'selected_index' => $selectedIndex,
            'action' => $action,
        ]);

        // Parse the query to understand search patterns
        $grammarParser = app(ParseSearchGrammar::class);
        $parsedQuery = $grammarParser($query);

        // Calculate position-based metrics
        $totalResults = count($results);
        $clickDepth = $selectedIndex !== null ? $selectedIndex + 1 : null;
        $clickedInTopN = null;

        if ($clickDepth !== null) {
            $clickedInTopN = [
                'top_1' => $clickDepth <= 1,
                'top_3' => $clickDepth <= 3,
                'top_5' => $clickDepth <= 5,
                'top_10' => $clickDepth <= 10,
            ];
        }

        // Extract decision context
        $context = [
            'parsed_query' => $parsedQuery,
            'total_results' => $totalResults,
            'click_depth' => $clickDepth,
            'clicked_in_top_n' => $clickedInTopN,
            'search_terms' => $parsedQuery['search_terms'],
            'filters_used' => array_column($parsedQuery['filters'], 'type'),
            'session_info' => [
                'timestamp' => now()->toISOString(),
                'user_agent' => request()->header('User-Agent'),
            ],
        ];

        // Store decision with rich metadata
        $decision = RecallDecision::create([
            'user_id' => auth()->id(),
            'query' => $query,
            'parsed_query' => $parsedQuery,
            'total_results' => $totalResults,
            'selected_fragment_id' => $selectedFragment?->id,
            'selected_index' => $selectedIndex,
            'action' => $action,
            'context' => $context,
            'decided_at' => now(),
        ]);

        // Update fragment selection stats if applicable
        if ($selectedFragment && $action === 'select') {
            $this->updateFragmentStats($selectedFragment, $context);
        }

        return $decision;
    }

    private function updateFragmentStats(Fragment $fragment, array $context): void
    {
        // Track selection frequency and context for ranking improvements
        $currentStats = $fragment->selection_stats ?? [];

        $currentStats['total_selections'] = ($currentStats['total_selections'] ?? 0) + 1;
        $currentStats['last_selected_at'] = now()->toISOString();

        // Track search context patterns
        if (! empty($context['search_terms'])) {
            $searchTerms = $context['search_terms'];
            $currentStats['search_patterns'][$searchTerms] =
                ($currentStats['search_patterns'][$searchTerms] ?? 0) + 1;
        }

        // Track filter usage patterns
        if (! empty($context['filters_used'])) {
            foreach ($context['filters_used'] as $filterType) {
                $currentStats['filter_patterns'][$filterType] =
                    ($currentStats['filter_patterns'][$filterType] ?? 0) + 1;
            }
        }

        // Track position-based selection (for ranking optimization)
        if (isset($context['click_depth'])) {
            $currentStats['position_stats']['total_clicks'] =
                ($currentStats['position_stats']['total_clicks'] ?? 0) + 1;
            $currentStats['position_stats']['average_position'] =
                (($currentStats['position_stats']['average_position'] ?? 0) *
                 ($currentStats['position_stats']['total_clicks'] - 1) +
                 $context['click_depth']) / $currentStats['position_stats']['total_clicks'];
        }

        $fragment->update(['selection_stats' => $currentStats]);
    }
}
