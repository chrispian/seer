<?php

namespace App\Actions;

use App\Models\Fragment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalculateSearchRanking
{
    /**
     * Calculate a hybrid ranking score for a fragment based on multiple factors
     */
    public function __invoke(Fragment $fragment, string $searchTerms, ?string $sessionId = null): float
    {
        Log::debug('CalculateSearchRanking::invoke()', [
            'fragment_id' => $fragment->id,
            'search_terms' => $searchTerms,
            'session_id' => $sessionId,
        ]);

        $score = 0.0;

        // 1. BM25-like relevance score from FULLTEXT search (0-100)
        if (isset($fragment->relevance)) {
            $score += $fragment->relevance * 40; // Weight: 40%
        }

        // 2. Recency boost (0-30 points)
        $score += $this->calculateRecencyScore($fragment) * 30;

        // 3. Tag match boost (0-15 points)
        $score += $this->calculateTagMatchScore($fragment, $searchTerms) * 15;

        // 4. Session relevance boost (0-10 points)
        if ($sessionId) {
            $score += $this->calculateSessionScore($fragment, $sessionId) * 10;
        }

        // 5. Fragment type weighting (0-5 points)
        $score += $this->calculateTypeScore($fragment) * 5;

        // 6. Title match bonus (0-10 points)
        if ($fragment->title) {
            $score += $this->calculateTitleMatchScore($fragment->title, $searchTerms) * 10;
        }

        // 7. Entity match bonus (0-5 points)
        $score += $this->calculateEntityMatchScore($fragment, $searchTerms) * 5;

        // 8. Importance/confidence weighting (0-5 points)
        $score += $this->calculateImportanceScore($fragment) * 5;

        return round($score, 2);
    }

    private function calculateRecencyScore(Fragment $fragment): float
    {
        $daysSinceCreation = Carbon::now()->diffInDays($fragment->created_at);

        // Exponential decay: newer items score higher
        if ($daysSinceCreation === 0) {
            return 1.0; // Today
        } elseif ($daysSinceCreation <= 7) {
            return 0.8; // This week
        } elseif ($daysSinceCreation <= 30) {
            return 0.5; // This month
        } elseif ($daysSinceCreation <= 90) {
            return 0.3; // Last 3 months
        } elseif ($daysSinceCreation <= 365) {
            return 0.1; // This year
        }

        return 0.05; // Older than a year
    }

    private function calculateTagMatchScore(Fragment $fragment, string $searchTerms): float
    {
        if (empty($fragment->tags)) {
            return 0;
        }

        $searchWords = array_map('strtolower', explode(' ', $searchTerms));
        $matchCount = 0;

        foreach ($fragment->tags as $tag) {
            foreach ($searchWords as $word) {
                if (str_contains(strtolower($tag), $word)) {
                    $matchCount++;
                    break;
                }
            }
        }

        // Normalize by number of tags
        return min(1.0, $matchCount / max(1, count($fragment->tags)));
    }

    private function calculateSessionScore(Fragment $fragment, string $sessionId): float
    {
        $metadata = $fragment->metadata ?? [];

        if (isset($metadata['session_id']) && $metadata['session_id'] === $sessionId) {
            return 1.0; // Same session
        }

        // Could add logic for related sessions here
        return 0;
    }

    private function calculateTypeScore(Fragment $fragment): float
    {
        // Prioritize certain types based on search context
        $typeWeights = [
            'note' => 0.5,
            'todo' => 0.7,
            'task' => 0.7,
            'insight' => 0.8,
            'question' => 0.6,
            'meeting' => 0.7,
            'contact' => 0.6,
            'link' => 0.5,
            'idea' => 0.8,
        ];

        $type = $fragment->type?->value ?? 'log';

        return $typeWeights[$type] ?? 0.4;
    }

    private function calculateTitleMatchScore(string $title, string $searchTerms): float
    {
        $searchWords = array_map('strtolower', explode(' ', $searchTerms));
        $titleLower = strtolower($title);
        $matchCount = 0;

        foreach ($searchWords as $word) {
            if (str_contains($titleLower, $word)) {
                $matchCount++;
            }
        }

        // Exact match gets full score
        if (strtolower($searchTerms) === $titleLower) {
            return 1.0;
        }

        // Partial matches
        return min(1.0, $matchCount / max(1, count($searchWords)) * 0.8);
    }

    private function calculateEntityMatchScore(Fragment $fragment, string $searchTerms): float
    {
        $score = 0;
        $searchWords = array_map('strtolower', explode(' ', $searchTerms));

        // Check parsed entities
        if ($fragment->parsed_entities) {
            $entities = $fragment->parsed_entities;

            // Check people matches
            if (isset($entities['people'])) {
                foreach ($entities['people'] as $person) {
                    foreach ($searchWords as $word) {
                        if (str_contains(strtolower($person), $word)) {
                            $score += 0.3;
                            break;
                        }
                    }
                }
            }

            // Check email matches
            if (isset($entities['emails'])) {
                foreach ($entities['emails'] as $email) {
                    foreach ($searchWords as $word) {
                        if (str_contains(strtolower($email), $word)) {
                            $score += 0.2;
                            break;
                        }
                    }
                }
            }

            // Check URL domain matches
            if (isset($entities['urls'])) {
                foreach ($entities['urls'] as $url) {
                    foreach ($searchWords as $word) {
                        if (str_contains(strtolower($url), $word)) {
                            $score += 0.2;
                            break;
                        }
                    }
                }
            }
        }

        return min(1.0, $score);
    }

    private function calculateImportanceScore(Fragment $fragment): float
    {
        $score = 0.5; // Base score

        // Add importance weight
        if ($fragment->importance) {
            $score += ($fragment->importance / 100) * 0.3;
        }

        // Add confidence weight
        if ($fragment->confidence) {
            $score += ($fragment->confidence / 100) * 0.2;
        }

        // Pinned items get a boost
        if ($fragment->pinned) {
            $score = min(1.0, $score + 0.3);
        }

        return $score;
    }
}
