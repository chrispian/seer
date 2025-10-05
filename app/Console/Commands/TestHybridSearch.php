<?php

namespace App\Console\Commands;

use App\Contracts\HybridSearchInterface;
use Illuminate\Console\Command;

class TestHybridSearch extends Command
{
    protected $signature = 'hybrid-search:test {query? : Search query to test}';

    protected $description = 'Test the hybrid search system capabilities and performance';

    public function handle()
    {
        $hybridSearch = app(HybridSearchInterface::class);
        $query = $this->argument('query') ?? 'test search query';

        $this->info('Hybrid Search System Test');
        $this->info('========================');

        // Display capabilities
        $this->displayCapabilities($hybridSearch);

        // Test search methods
        $this->testSearchMethods($hybridSearch, $query);

        return Command::SUCCESS;
    }

    protected function displayCapabilities(HybridSearchInterface $hybridSearch): void
    {
        $this->info("\nCapabilities:");
        $this->line('Vector Search: '.($hybridSearch->hasVectorCapability() ? '✓' : '✗'));
        $this->line('Text Search: '.($hybridSearch->hasTextCapability() ? '✓' : '✗'));

        $capabilities = $hybridSearch->getCapabilities();
        $this->line('Database: '.($capabilities['database'] ?? 'unknown'));
        $this->line('Extensions: '.implode(', ', $capabilities['extensions'] ?? []));
    }

    protected function testSearchMethods(HybridSearchInterface $hybridSearch, string $query): void
    {
        $this->info("\nTesting search methods with query: '{$query}'");

        // Test text search
        $this->info("\n1. Text Search:");
        $start = microtime(true);
        $textResults = $hybridSearch->textSearch($query, ['limit' => 5]);
        $textTime = round((microtime(true) - $start) * 1000, 2);
        $this->line('   Results: '.count($textResults)." (took {$textTime}ms)");

        if (! empty($textResults)) {
            $this->displayResults($textResults, 2);
        }

        // Test vector search (if available)
        if ($hybridSearch->hasVectorCapability()) {
            $this->info("\n2. Vector Search:");

            try {
                // Create a dummy vector for testing
                $testVector = array_fill(0, 1536, 0.1);
                $start = microtime(true);
                $vectorResults = $hybridSearch->vectorSearch($testVector, ['limit' => 5]);
                $vectorTime = round((microtime(true) - $start) * 1000, 2);
                $this->line('   Results: '.count($vectorResults)." (took {$vectorTime}ms)");

                if (! empty($vectorResults)) {
                    $this->displayResults($vectorResults, 2);
                }
            } catch (\Exception $e) {
                $this->error('   Vector search failed: '.$e->getMessage());
            }
        } else {
            $this->line("\n2. Vector Search: Not available");
        }

        // Test hybrid search
        $this->info("\n3. Hybrid Search:");
        $start = microtime(true);
        $hybridResults = $hybridSearch->hybridSearch($query, ['limit' => 5]);
        $hybridTime = round((microtime(true) - $start) * 1000, 2);
        $this->line('   Results: '.count($hybridResults)." (took {$hybridTime}ms)");

        if (! empty($hybridResults)) {
            $this->displayResults($hybridResults, 2);
        }
    }

    protected function displayResults(array $results, int $maxDisplay = 3): void
    {
        foreach (array_slice($results, 0, $maxDisplay) as $i => $result) {
            $score = round($result['score'] ?? 0, 3);
            $type = $result['type'] ?? 'unknown';
            $title = $result['title'] ?? 'No title';
            $content = substr($result['content'] ?? 'No content', 0, 100);

            $this->line('   ['.($i + 1)."] Score: {$score} ({$type}) - {$title}");
            $this->line('       '.$content.'...');
        }

        if (count($results) > $maxDisplay) {
            $remaining = count($results) - $maxDisplay;
            $this->line("   ... and {$remaining} more results");
        }
    }
}
