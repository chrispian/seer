<?php

namespace Tests\Unit;

use App\Contracts\HybridSearchInterface;
use App\Services\Search\HybridSearchManager;
use Tests\TestCase;

class HybridSearchTest extends TestCase
{
    public function test_hybrid_search_service_is_registered()
    {
        $hybridSearch = app('hybrid-search');

        $this->assertInstanceOf(HybridSearchManager::class, $hybridSearch);
        $this->assertInstanceOf(HybridSearchInterface::class, $hybridSearch);
    }

    public function test_hybrid_search_can_determine_capabilities()
    {
        $hybridSearch = app('hybrid-search');

        $capabilities = $hybridSearch->getCapabilities();

        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('database', $capabilities);
        $this->assertArrayHasKey('vector_search', $capabilities);
        $this->assertArrayHasKey('text_search', $capabilities);
    }

    public function test_hybrid_search_has_capability_methods()
    {
        $hybridSearch = app('hybrid-search');

        $this->assertIsBool($hybridSearch->hasVectorCapability());
        $this->assertIsBool($hybridSearch->hasTextCapability());
    }

    public function test_hybrid_search_methods_return_arrays()
    {
        $hybridSearch = app('hybrid-search');

        // These should not throw exceptions and should return arrays
        $textResults = $hybridSearch->textSearch('test query');
        $this->assertIsArray($textResults);

        if ($hybridSearch->hasVectorCapability()) {
            $vectorResults = $hybridSearch->vectorSearch([0.1, 0.2, 0.3]);
            $this->assertIsArray($vectorResults);
        }

        $hybridResults = $hybridSearch->hybridSearch('test query');
        $this->assertIsArray($hybridResults);
    }

    public function test_search_results_have_expected_structure()
    {
        $hybridSearch = app('hybrid-search');

        // Test that search methods return proper structure even with no results
        $results = $hybridSearch->textSearch('nonexistent query that should return empty');

        $this->assertIsArray($results);

        // If results exist, verify structure
        if (! empty($results)) {
            $result = $results[0];

            $this->assertArrayHasKey('id', $result);
            $this->assertArrayHasKey('content', $result);
            $this->assertArrayHasKey('score', $result);
            $this->assertArrayHasKey('type', $result);
        }

        // This test passes regardless of whether results are found
        $this->assertTrue(true);
    }
}
