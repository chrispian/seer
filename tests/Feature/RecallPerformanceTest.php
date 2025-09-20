<?php

namespace Tests\Feature;

use App\Actions\AnalyzeRecallPatterns;
use App\Actions\SearchFragments;
use App\Models\Fragment;
use App\Models\RecallDecision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecallPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_search_performance_with_large_dataset(): void
    {
        // Create a substantial number of fragments to test performance
        $fragmentCount = 1000;

        $startTime = microtime(true);

        // Create fragments in batches for efficiency
        $batchSize = 100;
        for ($i = 0; $i < $fragmentCount; $i += $batchSize) {
            $fragments = [];
            for ($j = 0; $j < $batchSize && ($i + $j) < $fragmentCount; $j++) {
                $fragments[] = Fragment::factory()->make([
                    'message' => 'Fragment content number '.($i + $j).' with various keywords and meeting notes',
                    'title' => 'Fragment Title '.($i + $j),
                    'type' => ['note', 'task', 'meeting'][($i + $j) % 3],
                    'tags' => [['work'], ['personal'], ['urgent']][($i + $j) % 3],
                ])->toArray();
            }
            Fragment::insert($fragments);
        }

        $creationTime = microtime(true) - $startTime;
        $this->assertLessThan(10, $creationTime, "Fragment creation took too long: {$creationTime}s");

        // Test search performance
        $searchAction = app(SearchFragments::class);

        $searchStart = microtime(true);
        $results = $searchAction('meeting notes');
        $searchTime = microtime(true) - $searchStart;

        $this->assertLessThan(2, $searchTime, "Search took too long: {$searchTime}s");
        $this->assertGreaterThan(0, count($results));
        $this->assertLessThan($fragmentCount, count($results)); // Should not return everything

        // Test filtered search performance
        $filteredStart = microtime(true);
        $filteredResults = $searchAction('type:meeting notes');
        $filteredTime = microtime(true) - $filteredStart;

        $this->assertLessThan(2, $filteredTime, "Filtered search took too long: {$filteredTime}s");
        $this->assertGreaterThan(0, count($filteredResults));

        // All results should be meeting type
        foreach ($filteredResults as $result) {
            $this->assertEquals('meeting', $result['type']);
        }
    }

    public function test_analytics_performance_with_large_decision_dataset(): void
    {
        $user = User::first();

        // Create a large number of recall decisions
        $decisionCount = 1000;
        $decisions = [];

        for ($i = 0; $i < $decisionCount; $i++) {
            $decisions[] = [
                'user_id' => $user->id,
                'query' => 'test query '.($i % 50), // Create patterns
                'parsed_query' => [
                    'search_terms' => 'test query',
                    'filters' => $i % 3 === 0 ? [['type' => 'type', 'value' => 'meeting']] : [],
                ],
                'total_results' => rand(1, 20),
                'selected_fragment_id' => $i % 4 === 0 ? null : rand(1, 100),
                'selected_index' => $i % 4 === 0 ? null : rand(0, 10),
                'action' => $i % 4 === 0 ? 'dismiss' : 'select',
                'context' => [
                    'click_depth' => $i % 4 === 0 ? null : rand(1, 10),
                    'total_results' => rand(1, 20),
                ],
                'decided_at' => now()->subMinutes(rand(1, 10080)), // Within last week
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches to avoid memory issues
            if (count($decisions) >= 100) {
                RecallDecision::insert($decisions);
                $decisions = [];
            }
        }

        if (! empty($decisions)) {
            RecallDecision::insert($decisions);
        }

        // Test analytics performance
        $analyzer = app(AnalyzeRecallPatterns::class);

        $analyticsStart = microtime(true);
        $analysis = $analyzer($user->id, 7);
        $analyticsTime = microtime(true) - $analyticsStart;

        $this->assertLessThan(5, $analyticsTime, "Analytics took too long: {$analyticsTime}s");

        // Verify analysis results
        $this->assertArrayHasKey('summary', $analysis);
        $this->assertArrayHasKey('query_patterns', $analysis);
        $this->assertArrayHasKey('selection_metrics', $analysis);

        $summary = $analysis['summary'];
        $this->assertEquals($decisionCount, $summary['total_searches']);
        $this->assertGreaterThan(0, $summary['success_rate']);

        // Test that common queries are identified
        $queryPatterns = $analysis['query_patterns']['most_frequent_queries'];
        $this->assertNotEmpty($queryPatterns);
        $this->assertGreaterThan(1, max($queryPatterns)); // Some queries should appear multiple times
    }

    public function test_concurrent_search_operations(): void
    {
        // Create test data
        Fragment::factory()->count(100)->create();

        $searchAction = app(SearchFragments::class);

        // Simulate concurrent searches
        $promises = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $query = 'test query '.$i;
            $result = $searchAction($query);
            $promises[] = count($result);
        }

        $totalTime = microtime(true) - $startTime;
        $this->assertLessThan(10, $totalTime, "Concurrent searches took too long: {$totalTime}s");

        // All searches should return some results or empty array (no errors)
        foreach ($promises as $resultCount) {
            $this->assertIsInt($resultCount);
            $this->assertGreaterThanOrEqual(0, $resultCount);
        }
    }

    public function test_memory_usage_during_large_operations(): void
    {
        $initialMemory = memory_get_usage();

        // Create a moderate dataset
        Fragment::factory()->count(500)->create();

        $searchAction = app(SearchFragments::class);

        // Perform multiple searches
        for ($i = 0; $i < 20; $i++) {
            $results = $searchAction("search term $i");
            // Don't store results to test memory cleanup
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable (less than 50MB for this test)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease,
            'Memory usage increased too much: '.($memoryIncrease / 1024 / 1024).' MB');
    }

    public function test_search_result_pagination_performance(): void
    {
        // Create fragments with common search terms
        Fragment::factory()->count(200)->create([
            'message' => 'Common search term content for pagination testing',
            'title' => 'Common Search Result',
        ]);

        $searchAction = app(SearchFragments::class);

        // Test that search limits results appropriately
        $startTime = microtime(true);
        $results = $searchAction('common search');
        $searchTime = microtime(true) - $startTime;

        $this->assertLessThan(1, $searchTime, "Search with many matches took too long: {$searchTime}s");

        // Should limit results to prevent performance issues
        $this->assertLessThanOrEqual(100, count($results), 'Search returned too many results');

        // But should return a reasonable number
        $this->assertGreaterThan(10, count($results), 'Search returned too few results');
    }

    public function test_database_query_efficiency(): void
    {
        // Create test data
        Fragment::factory()->count(100)->create();

        // Enable query logging
        \DB::enableQueryLog();

        $searchAction = app(SearchFragments::class);
        $results = $searchAction('test search efficiency');

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should not generate excessive queries (N+1 problem)
        $this->assertLessThanOrEqual(5, count($queries),
            'Search generated too many queries: '.count($queries));

        // Should return results in reasonable time
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        $this->assertLessThan(1000, $totalQueryTime, // 1 second in milliseconds
            "Total query time too high: {$totalQueryTime}ms");
    }
}
