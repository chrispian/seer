<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Fragment;
use App\Services\AI\Embeddings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $query = $command->arguments['identifier'] ?? null;

        if (empty($query)) {
            return new CommandResponse(
                type: 'search',
                shouldOpenPanel: true,
                panelData: [
                    'error' => true,
                    'message' => "❌ No search query provided. Please try `/search your query here` or `/s your query here`",
                ]
            );
        }

        try {
            $results = $this->hybridSearch($query);
            
            if (empty($results)) {
                return new CommandResponse(
                    type: 'search',
                    shouldOpenPanel: true,
                    panelData: [
                        'message' => "🔍 No results found for: **{$query}**",
                        'query' => $query,
                        'fragments' => [],
                    ],
                );
            }

            // Convert search results to fragment-like objects for display
            $fragmentData = [];
            foreach ($results as $result) {
                $fragment = Fragment::find($result->id);
                if ($fragment) {
                    $fragmentData[] = [
                        'id' => $fragment->id,
                        'message' => $fragment->message,
                        'created_at' => $fragment->created_at,
                        'type' => ['value' => $fragment->type?->value ?? 'fragment'],
                        'snippet' => $result->snippet ?? '',
                        'score' => $result->score ?? 0,
                        'vec_sim' => $result->vec_sim ?? 0,
                        'txt_rank' => $result->txt_rank ?? 0,
                    ];
                }
            }

            return new CommandResponse(
                type: 'search',
                shouldOpenPanel: true,
                panelData: [
                    'message' => "🔍 Found **" . count($fragmentData) . "** results for: **{$query}**",
                    'query' => $query,
                    'fragments' => $fragmentData,
                ],
            );
        } catch (\Exception $e) {
            return new CommandResponse(
                type: 'search',
                shouldOpenPanel: true,
                panelData: [
                    'error' => true,
                    'message' => "❌ Search failed: " . $e->getMessage(),
                ]
            );
        }
    }

    private function hybridSearch(string $query, string $provider = null, int $limit = 20): array
    {
        $provider = $provider ?? config('fragments.embeddings.provider');
        
        // Get query embedding
        $emb = app(Embeddings::class)->embed($query, $provider);
        $qe = '[' . implode(',', $emb['vector']) . ']';

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
    }
}