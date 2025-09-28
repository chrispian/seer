<?php

namespace App\Actions\Commands;

use App\Actions\SearchFragments as FallbackSearch;
use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Fragment;
use App\Services\AI\Embeddings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SearchCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $query = $command->arguments['identifier'] ?? null;

        if (empty($query)) {
            return new CommandResponse(
                type: 'search',
                shouldShowErrorToast: true,
                message: 'No search query provided. Please try `/search your query here` or `/s your query here`',
            );
        }

        try {
            $isEmbeddingsEnabled = config('fragments.embeddings.enabled');

            if (! $isEmbeddingsEnabled) {
                $results = $this->fallbackSearch($command, $query);
                $searchMode = 'text-only';
            } else {
                $results = $this->hybridSearch($query);
                $searchMode = 'hybrid';

                // Fall back to text search if hybrid search returns empty results
                if (empty($results)) {
                    $results = $this->fallbackSearch($command, $query);
                    $searchMode = 'text-fallback';
                }
            }

            if (empty($results)) {
                $message = $isEmbeddingsEnabled
                    ? "🔍 No results found for: **{$query}**"
                    : "🔍 No results found for: **{$query}** (text-only search)";

                return new CommandResponse(
                    type: 'search',
                    shouldOpenPanel: true,
                    panelData: [
                        'message' => $message,
                        'query' => $query,
                        'fragments' => [],
                        'search_mode' => $searchMode,
                    ],
                );
            }

            // Convert search results to fragment-like objects for display
            $fragmentData = [];
            foreach ($results as $result) {
                $fragment = Fragment::with('type')->find($result->id);
                if ($fragment) {
                    $fragmentData[] = [
                        'id' => $fragment->id,
                        'message' => $fragment->message,
                        'created_at' => $fragment->created_at,
                        'type' => [
                            'name' => $fragment->type?->label ?? ucfirst($fragment->type?->value ?? 'fragment'),
                            'value' => $fragment->type?->value ?? 'fragment',
                        ],
                        'snippet' => $result->snippet ?? '',
                        'score' => $result->score ?? 0,
                        'vec_sim' => $result->vec_sim ?? 0,
                        'txt_rank' => $result->txt_rank ?? 0,
                    ];
                }
            }

            $message = $isEmbeddingsEnabled
                ? '🔍 Found **'.count($fragmentData)."** results for: **{$query}**"
                : '🔍 Found **'.count($fragmentData)."** results for: **{$query}** (text-only search)";

            return new CommandResponse(
                type: 'search',
                shouldOpenPanel: true,
                panelData: [
                    'message' => $message,
                    'query' => $query,
                    'fragments' => $fragmentData,
                    'search_mode' => $searchMode,
                ],
            );
        } catch (\Exception $e) {
            return new CommandResponse(
                type: 'search',
                shouldShowErrorToast: true,
                message: 'Search failed: '.$e->getMessage(),
            );
        }
    }

    private function fallbackSearch(CommandRequest $command, string $query): array
    {
        $vault = $command->arguments['vault'] ?? null;
        $projectId = isset($command->arguments['project_id']) ? (int) $command->arguments['project_id'] : null;
        $sessionId = $command->arguments['current_chat_session_id'] ?? null;

        $collection = app(FallbackSearch::class)(
            query: $query,
            vault: $vault,
            projectId: $projectId,
            sessionId: $sessionId,
            limit: 20
        );

        return $collection->map(function (Fragment $fragment) {
            return (object) [
                'id' => $fragment->id,
                'snippet' => Str::limit(strip_tags($fragment->message ?? ''), 200),
                'score' => $fragment->search_score ?? 0,
                'vec_sim' => null,
                'txt_rank' => $fragment->search_score ?? 0,
            ];
        })->all();
    }

    private function hybridSearch(string $query, ?string $provider = null, int $limit = 20): array
    {
        if (! config('fragments.embeddings.enabled')) {
            return [];
        }

        // Check if we have pgvector support
        if (! $this->hasPgVectorSupport()) {
            \Illuminate\Support\Facades\Log::warning('SearchCommand: pgvector not available, falling back to text search');

            return [];
        }

        $provider = $provider ?? config('fragments.embeddings.provider');

        try {
            // Get query embedding
            $emb = app(Embeddings::class)->embed($query, $provider);
            $qe = '['.implode(',', $emb['vector']).']';

            // Choose text expression (handle edited messages)
            $hasEdited = Schema::hasColumn('fragments', 'edited_message');
            $bodyExpr = $hasEdited ? "coalesce(f.edited_message, f.message, '')"
                : "coalesce(f.message, '')";
            $docExpr = "coalesce(f.title,'') || ' ' || {$bodyExpr}";

            // Hybrid SQL with snippet
            $sql = "
                WITH p AS (
                  SELECT ?::vector AS qe, websearch_to_tsquery('simple', ?) AS qq
                )
                SELECT
                  f.id,
                  f.title,
                  ts_headline('simple', {$docExpr}, p.qq,
                    'StartSel=<mark>,StopSel=</mark>,MaxFragments=2,MaxWords=18') AS snippet,
                  (1 - (e.embedding <=> p.qe)) AS vec_sim,
                  ts_rank_cd(to_tsvector('simple', {$docExpr}), p.qq) AS txt_rank,
                  (0.6 * ts_rank_cd(to_tsvector('simple', {$docExpr}), p.qq)
                   + 0.4 * (1 - (e.embedding <=> p.qe))) AS score
                FROM fragments f
                JOIN fragment_embeddings e
                  ON e.fragment_id = f.id
                 AND e.provider    = ?
                CROSS JOIN p
                ORDER BY score DESC
                LIMIT ?";

            $rows = DB::select($sql, [$qe, $query, $provider, $limit]);

            return $rows;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SearchCommand: hybrid search failed', [
                'query' => $query,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function hasPgVectorSupport(): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            return false;
        }

        try {
            // Check if pgvector extension is installed
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");

            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
