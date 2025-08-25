<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;

class ParseSearchGrammar
{
    /**
     * Parse search query grammar and return structured query information
     * with user-friendly suggestions and auto-complete data
     */
    public function __invoke(string $query): array
    {
        Log::debug('ParseSearchGrammar::invoke()', ['query' => $query]);
        
        $parsed = [
            'original_query' => $query,
            'search_terms' => '',
            'filters' => [],
            'suggestions' => [],
            'autocomplete' => [],
            'valid' => true,
            'errors' => [],
        ];
        
        // Parse individual grammar components
        $parsed = $this->parseTypeFilter($query, $parsed);
        $parsed = $this->parseTagFilters($query, $parsed);
        $parsed = $this->parseMentionFilters($query, $parsed);
        $parsed = $this->parseHasFilters($query, $parsed);
        $parsed = $this->parseSessionFilter($query, $parsed);
        $parsed = $this->parseDateFilters($query, $parsed);
        
        // Extract remaining search terms
        $cleanQuery = $this->removeFiltersFromQuery($query);
        $parsed['search_terms'] = trim($cleanQuery);
        
        // Generate suggestions and auto-complete
        $parsed['suggestions'] = $this->generateSuggestions($parsed);
        $parsed['autocomplete'] = $this->generateAutocomplete($parsed);
        
        return $parsed;
    }
    
    private function parseTypeFilter(string $query, array $parsed): array
    {
        if (preg_match('/type:(\w+)/', $query, $matches)) {
            $parsed['filters'][] = [
                'type' => 'type',
                'value' => $matches[1],
                'display' => "Type: {$matches[1]}",
                'removable' => true,
            ];
        }
        
        return $parsed;
    }
    
    private function parseTagFilters(string $query, array $parsed): array
    {
        if (preg_match_all('/#([\w-]+)/', $query, $matches)) {
            foreach ($matches[1] as $tag) {
                $parsed['filters'][] = [
                    'type' => 'tag',
                    'value' => $tag,
                    'display' => "#{$tag}",
                    'removable' => true,
                ];
            }
        }
        
        return $parsed;
    }
    
    private function parseMentionFilters(string $query, array $parsed): array
    {
        if (preg_match_all('/@([\w\-\.]+)/', $query, $matches)) {
            foreach ($matches[1] as $mention) {
                $parsed['filters'][] = [
                    'type' => 'mention',
                    'value' => $mention,
                    'display' => "@{$mention}",
                    'removable' => true,
                ];
            }
        }
        
        return $parsed;
    }
    
    private function parseHasFilters(string $query, array $parsed): array
    {
        // has:link
        if (str_contains($query, 'has:link')) {
            $parsed['filters'][] = [
                'type' => 'has',
                'value' => 'link',
                'display' => 'Has: Links',
                'removable' => true,
            ];
        }
        
        // has:code
        if (str_contains($query, 'has:code')) {
            $parsed['filters'][] = [
                'type' => 'has',
                'value' => 'code',
                'display' => 'Has: Code',
                'removable' => true,
            ];
        }
        
        return $parsed;
    }
    
    private function parseSessionFilter(string $query, array $parsed): array
    {
        if (preg_match('/in:session\(([^)]+)\)/', $query, $matches)) {
            $parsed['filters'][] = [
                'type' => 'session',
                'value' => $matches[1],
                'display' => "Session: {$matches[1]}",
                'removable' => true,
            ];
        }
        
        return $parsed;
    }
    
    private function parseDateFilters(string $query, array $parsed): array
    {
        // before:date
        if (preg_match('/before:(\d{4}-\d{2}-\d{2})/', $query, $matches)) {
            $parsed['filters'][] = [
                'type' => 'date',
                'value' => $matches[1],
                'display' => "Before: {$matches[1]}",
                'operator' => 'before',
                'removable' => true,
            ];
        }
        
        // after:date
        if (preg_match('/after:(\d{4}-\d{2}-\d{2})/', $query, $matches)) {
            $parsed['filters'][] = [
                'type' => 'date',
                'value' => $matches[1],
                'display' => "After: {$matches[1]}",
                'operator' => 'after',
                'removable' => true,
            ];
        }
        
        return $parsed;
    }
    
    private function removeFiltersFromQuery(string $query): string
    {
        // Remove all filter patterns
        $patterns = [
            '/type:\w+/',
            '/#[\w-]+/',
            '/@[\w\-\.]+/',
            '/has:link/',
            '/has:code/',
            '/in:session\([^)]+\)/',
            '/before:\d{4}-\d{2}-\d{2}/',
            '/after:\d{4}-\d{2}-\d{2}/',
        ];
        
        foreach ($patterns as $pattern) {
            $query = preg_replace($pattern, '', $query);
        }
        
        // Clean up multiple spaces
        return preg_replace('/\s+/', ' ', $query);
    }
    
    private function generateSuggestions(array $parsed): array
    {
        $suggestions = [];
        
        // Suggest common filters if not already applied
        $hasTypeFilter = collect($parsed['filters'])->contains('type', 'type');
        $hasDateFilter = collect($parsed['filters'])->contains('type', 'date');
        
        if (!$hasTypeFilter) {
            $suggestions[] = [
                'type' => 'filter',
                'text' => 'type:todo',
                'description' => 'Filter by fragment type',
                'category' => 'filters',
            ];
        }
        
        if (!$hasDateFilter) {
            $suggestions[] = [
                'type' => 'filter',
                'text' => 'after:' . now()->subWeek()->format('Y-m-d'),
                'description' => 'Show recent fragments',
                'category' => 'filters',
            ];
        }
        
        // Suggest common tags based on usage
        $suggestions[] = [
            'type' => 'filter',
            'text' => '#urgent',
            'description' => 'Filter by urgent tag',
            'category' => 'tags',
        ];
        
        $suggestions[] = [
            'type' => 'filter',
            'text' => 'has:link',
            'description' => 'Show fragments with links',
            'category' => 'filters',
        ];
        
        return $suggestions;
    }
    
    private function generateAutocomplete(array $parsed): array
    {
        $autocomplete = [];
        
        // Fragment types
        $types = ['note', 'todo', 'task', 'meeting', 'idea', 'question', 'insight'];
        foreach ($types as $type) {
            $autocomplete[] = [
                'type' => 'type',
                'value' => "type:{$type}",
                'display' => ucfirst($type),
                'category' => 'Types',
            ];
        }
        
        // Has filters
        $hasFilters = [
            'link' => 'Has Links',
            'code' => 'Has Code Snippets',
        ];
        
        foreach ($hasFilters as $key => $display) {
            $autocomplete[] = [
                'type' => 'has',
                'value' => "has:{$key}",
                'display' => $display,
                'category' => 'Content',
            ];
        }
        
        // Date shortcuts
        $dateShortcuts = [
            'today' => now()->format('Y-m-d'),
            'yesterday' => now()->subDay()->format('Y-m-d'),
            'week' => now()->subWeek()->format('Y-m-d'),
            'month' => now()->subMonth()->format('Y-m-d'),
        ];
        
        foreach ($dateShortcuts as $label => $date) {
            $autocomplete[] = [
                'type' => 'date',
                'value' => "after:{$date}",
                'display' => "After {$label}",
                'category' => 'Dates',
            ];
        }
        
        return $autocomplete;
    }
}

