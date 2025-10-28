<?php

namespace App\Console\Commands\Orchestration;

use App\Enums\ContextScope;
use App\Services\Orchestration\OrchestrationContextSearchService;
use Illuminate\Console\Command;

class OrchestrationContextSearch extends Command
{
    protected $signature = 'orchestration:context-search
                            {query : The search query}
                            {--scope=task : Search scope: session, task, sprint, project}
                            {--id= : Scope ID (session key, task code, or sprint code)}
                            {--limit=20 : Maximum number of results}
                            {--no-cache : Bypass cache and search fresh}
                            {--summary : Show summary statistics instead of search}';

    protected $description = 'Search orchestration context across sessions, tasks, sprints, or project';

    public function handle(OrchestrationContextSearchService $searchService): int
    {
        $query = $this->argument('query');
        $scopeValue = $this->option('scope');
        $scopeId = $this->option('id');
        $limit = (int) $this->option('limit');
        $useCache = !$this->option('no-cache');
        $showSummary = $this->option('summary');

        try {
            $scope = ContextScope::from($scopeValue);
        } catch (\ValueError $e) {
            $this->error("Invalid scope: {$scopeValue}");
            $this->line("Valid scopes: session, task, sprint, project");
            return 1;
        }

        if (!$scopeId && $scope !== ContextScope::PROJECT) {
            $this->error("Scope ID is required for {$scope->value} scope");
            $this->line("Use --id=<session-key|task-code|sprint-code>");
            return 1;
        }

        if ($scope === ContextScope::PROJECT) {
            $scopeId = 'all';
        }

        if ($showSummary) {
            return $this->displaySummary($searchService, $scope, $scopeId);
        }

        $this->info("Searching {$scope->label()} context: {$scopeId}");
        $this->line("Query: \"{$query}\"");
        $this->newLine();

        $results = $searchService->search($query, $scope, $scopeId, $limit, $useCache);

        if ($results->fromCache) {
            $this->line("<fg=yellow>⚡ Results from cache</>");
        }

        $this->line("<fg=cyan>Found {$results->totalCount} results in {$results->searchDuration}ms</>");
        $this->newLine();

        if ($results->isEmpty()) {
            $this->warn("No results found");
            return 0;
        }

        $results->sortByRelevance();

        foreach ($results->results as $index => $result) {
            $this->displayResult($index + 1, $result);
        }

        $this->newLine();
        $this->line("<fg=gray>Tip: Use --summary to see context statistics</>");

        return 0;
    }

    protected function displayResult(int $index, array $result): void
    {
        $relevance = str_repeat('★', min(5, (int)($result['relevance_score'] / 3)));

        $this->line("<fg=green>{$index}.</> {$result['event_type']} {$relevance}");
        $this->line("   Entity: {$result['entity_type']} (ID: {$result['entity_id']})");
        $this->line("   Time: {$result['emitted_at']}");

        if (!empty($result['context'])) {
            $context = is_array($result['context'])
                ? json_encode($result['context'])
                : $result['context'];
            $truncated = substr($context, 0, 100);
            if (strlen($context) > 100) {
                $truncated .= '...';
            }
            $this->line("   Context: {$truncated}");
        }

        if ($result['session_key']) {
            $this->line("   <fg=gray>Session: {$result['session_key']}</>");
        }

        $this->newLine();
    }

    protected function displaySummary(
        OrchestrationContextSearchService $searchService,
        ContextScope $scope,
        string $scopeId
    ): int {
        $this->info("Context Summary: {$scope->label()} ({$scopeId})");
        $this->newLine();

        $summary = $searchService->getSummary($scope, $scopeId);

        $this->line("<fg=cyan>Overview:</>");
        $this->line("  Total Events: {$summary['total_events']}");
        $this->line("  First Event: " . ($summary['first_event'] ?? 'N/A'));
        $this->line("  Last Event: " . ($summary['last_event'] ?? 'N/A'));
        $this->line("  Time Span: {$summary['time_span_hours']} hours");
        $this->newLine();

        if (!empty($summary['event_types'])) {
            $this->line("<fg=cyan>Top Event Types:</>");
            $table = [];
            foreach ($summary['event_types'] as $type => $count) {
                $table[] = [$type, $count];
            }
            $this->table(['Event Type', 'Count'], $table);
        }

        $this->newLine();
        $this->line("<fg=gray>Tip: Use without --summary to search this context</>");

        return 0;
    }
}
