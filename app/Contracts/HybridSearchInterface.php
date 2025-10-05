<?php

namespace App\Contracts;

interface HybridSearchInterface
{
    /**
     * Perform hybrid search combining vector similarity and text search
     *
     * @param  string  $query  The search query text
     * @param  array  $options  Search options including weights, limits, filters
     * @return array Array of search results with scores
     */
    public function hybridSearch(string $query, array $options = []): array;

    /**
     * Perform vector similarity search only
     *
     * @param  array  $vector  The query vector
     * @param  array  $options  Search options
     * @return array Array of results with similarity scores
     */
    public function vectorSearch(array $vector, array $options = []): array;

    /**
     * Perform text search only
     *
     * @param  string  $query  The search query
     * @param  array  $options  Search options
     * @return array Array of results with text match scores
     */
    public function textSearch(string $query, array $options = []): array;

    /**
     * Check if vector search is available
     */
    public function hasVectorCapability(): bool;

    /**
     * Check if text search is available
     */
    public function hasTextCapability(): bool;

    /**
     * Get the search capabilities of this implementation
     *
     * @return array Array describing available features
     */
    public function getCapabilities(): array;
}
