<?php

namespace App\Services\Orchestration;

use App\Enums\ContextScope;
use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrchestrationContextSearchService
{
    protected const CACHE_PREFIX = 'orch_ctx_search:';
    protected int $defaultCacheTtl = 3600;

    public function search(
        string $query,
        ContextScope $scope,
        string $scopeId,
        int $limit = 20,
        bool $useCache = true
    ): SearchResults {
        $cacheKey = $this->getCacheKey($query, $scope, $scopeId, $limit);

        if ($useCache && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return new SearchResults(
                query: $query,
                scope: $scope,
                scopeId: $scopeId,
                results: collect($cached['results']),
                totalCount: $cached['total_count'],
                searchDuration: $cached['search_duration'],
                fromCache: true
            );
        }

        $startTime = microtime(true);

        $driver = config('orchestration.search.driver', 'fulltext');
        
        $results = match ($driver) {
            'pgvector' => $this->vectorSearch($query, $scope, $scopeId, $limit),
            default => $this->fullTextSearch($query, $scope, $scopeId, $limit),
        };

        $searchDuration = round((microtime(true) - $startTime) * 1000, 2);

        $searchResults = new SearchResults(
            query: $query,
            scope: $scope,
            scopeId: $scopeId,
            results: $results,
            totalCount: $results->count(),
            searchDuration: $searchDuration,
            fromCache: false
        );

        if ($useCache) {
            Cache::put($cacheKey, [
                'results' => $results->toArray(),
                'total_count' => $results->count(),
                'search_duration' => $searchDuration,
            ], $this->defaultCacheTtl);
        }

        return $searchResults;
    }

    protected function fullTextSearch(
        string $query,
        ContextScope $scope,
        string $scopeId,
        int $limit
    ): Collection {
        $eventsQuery = OrchestrationEvent::query();

        $eventsQuery = $this->applyScopeFilter($eventsQuery, $scope, $scopeId);

        $searchTerms = $this->extractSearchTerms($query);
        
        $eventsQuery->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('event_type', 'ILIKE', "%{$term}%")
                  ->orWhere(DB::raw("payload::text"), 'ILIKE', "%{$term}%");
            }
        });

        $events = $eventsQuery
            ->orderBy('emitted_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) use ($query) {
                return $this->formatEventResult($event, $query);
            });

        return $events;
    }

    protected function vectorSearch(
        string $query,
        ContextScope $scope,
        string $scopeId,
        int $limit
    ): Collection {
        return collect();
    }

    protected function applyScopeFilter($query, ContextScope $scope, string $scopeId)
    {
        return match ($scope) {
            ContextScope::SESSION => $query->where('session_key', $scopeId),
            
            ContextScope::TASK => $query->where(function ($q) use ($scopeId) {
                $task = OrchestrationTask::where('task_code', $scopeId)->first();
                if ($task) {
                    $q->where('entity_type', 'task')
                      ->where('entity_id', $task->id);
                }
            }),
            
            ContextScope::SPRINT => $query->where(function ($q) use ($scopeId) {
                $sprint = OrchestrationSprint::where('sprint_code', $scopeId)->first();
                if ($sprint) {
                    $taskIds = OrchestrationTask::where('sprint_id', $sprint->id)
                        ->pluck('id');
                    
                    $q->where(function ($subQ) use ($sprint, $taskIds) {
                        $subQ->where('entity_type', 'sprint')
                             ->where('entity_id', $sprint->id)
                             ->orWhere(function ($taskQ) use ($taskIds) {
                                 $taskQ->where('entity_type', 'task')
                                       ->whereIn('entity_id', $taskIds);
                             });
                    });
                }
            }),
            
            ContextScope::PROJECT => $query,
        };
    }

    protected function extractSearchTerms(string $query): array
    {
        $query = strtolower(trim($query));
        
        $terms = preg_split('/\s+/', $query);
        
        $terms = array_filter($terms, fn($term) => strlen($term) >= 2);
        
        return array_values($terms);
    }

    protected function formatEventResult($event, string $query): array
    {
        $payload = $event->payload ?? [];
        $context = '';

        if (isset($payload['changes'])) {
            $context = json_encode($payload['changes'], JSON_PRETTY_PRINT);
        } elseif (isset($payload['task_code'])) {
            $context = "Task: {$payload['task_code']}";
        } elseif (isset($payload['sprint_code'])) {
            $context = "Sprint: {$payload['sprint_code']}";
        }

        $relevance = $this->calculateRelevance($event, $query);

        return [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'entity_type' => $event->entity_type,
            'entity_id' => $event->entity_id,
            'emitted_at' => $event->emitted_at->toIso8601String(),
            'context' => $context,
            'payload' => $payload,
            'relevance_score' => $relevance,
            'session_key' => $event->session_key,
        ];
    }

    protected function calculateRelevance($event, string $query): float
    {
        $score = 0.0;
        $queryLower = strtolower($query);

        if (str_contains(strtolower($event->event_type), $queryLower)) {
            $score += 10.0;
        }

        $payloadText = json_encode($event->payload ?? []);
        if (str_contains(strtolower($payloadText), $queryLower)) {
            $score += 5.0;
        }

        $hoursAgo = now()->diffInHours($event->emitted_at);
        $recencyScore = max(0, 5 - ($hoursAgo / 24));
        $score += $recencyScore;

        return round($score, 2);
    }

    protected function getCacheKey(
        string $query,
        ContextScope $scope,
        string $scopeId,
        int $limit
    ): string {
        $hash = md5($query . $scope->value . $scopeId . $limit);
        return self::CACHE_PREFIX . $hash;
    }

    public function clearCache(?string $scopeId = null): int
    {
        if ($scopeId) {
            $pattern = self::CACHE_PREFIX . "*{$scopeId}*";
        } else {
            $pattern = self::CACHE_PREFIX . "*";
        }

        $keys = Cache::getStore()->getRedis()->keys($pattern);
        $deleted = 0;

        foreach ($keys as $key) {
            if (Cache::forget($key)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function getRecentContext(
        ContextScope $scope,
        string $scopeId,
        int $limit = 10
    ): Collection {
        $query = OrchestrationEvent::query();
        
        $query = $this->applyScopeFilter($query, $scope, $scopeId);

        return $query
            ->orderBy('emitted_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return $this->formatEventResult($event, '');
            });
    }

    public function getSummary(ContextScope $scope, string $scopeId): array
    {
        $baseQuery = OrchestrationEvent::query();
        $baseQuery = $this->applyScopeFilter($baseQuery, $scope, $scopeId);

        $totalEvents = (clone $baseQuery)->count();
        
        $byTypeQuery = (clone $baseQuery)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->orderByRaw('count(*) desc')
            ->limit(10);
        
        $byType = $byTypeQuery->pluck('count', 'event_type')->toArray();

        $firstEvent = (clone $baseQuery)->orderBy('emitted_at', 'asc')->first();
        $lastEvent = (clone $baseQuery)->orderBy('emitted_at', 'desc')->first();

        return [
            'scope' => $scope->value,
            'scope_id' => $scopeId,
            'total_events' => $totalEvents,
            'event_types' => $byType,
            'first_event' => $firstEvent ? $firstEvent->emitted_at->toIso8601String() : null,
            'last_event' => $lastEvent ? $lastEvent->emitted_at->toIso8601String() : null,
            'time_span_hours' => $firstEvent && $lastEvent 
                ? $lastEvent->emitted_at->diffInHours($firstEvent->emitted_at) 
                : 0,
        ];
    }
}

class SearchResults
{
    public function __construct(
        public string $query,
        public ContextScope $scope,
        public string $scopeId,
        public Collection $results,
        public int $totalCount,
        public float $searchDuration,
        public bool $fromCache = false
    ) {}

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'scope' => $this->scope->value,
            'scope_id' => $this->scopeId,
            'total_count' => $this->totalCount,
            'search_duration_ms' => $this->searchDuration,
            'from_cache' => $this->fromCache,
            'results' => $this->results->toArray(),
        ];
    }

    public function isEmpty(): bool
    {
        return $this->results->isEmpty();
    }

    public function groupByEventType(): Collection
    {
        return $this->results->groupBy('event_type');
    }

    public function sortByRelevance(): self
    {
        $this->results = $this->results->sortByDesc('relevance_score')->values();
        return $this;
    }
}
