<?php

namespace App\Http\Controllers;

use App\Actions\SearchFragments;
use App\Models\Category;
use App\Models\Fragment;
use App\Services\AI\Embeddings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FragmentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        // Create base fragment with log type (default)
        $fragment = Fragment::create([
            'message' => $request->input('message'),
            'type' => 'log', // Simple default type
            'source' => $request->input('source'),
            'relationships' => $request->input('relationships', []),
        ]);

        // Attach category if present
        if ($categoryName = $request->input('category')) {
            $category = Category::firstOrCreate(['name' => $categoryName]);
            $fragment->category_id = $category->id;
            $fragment->save();
        }

        // Dispatch async enrichment pipeline (same as RouteFragment)
        dispatch(function () use ($fragment) {
            try {
                // Reload fragment from database to ensure fresh state
                $freshFragment = Fragment::find($fragment->id);
                if (! $freshFragment) {
                    \Illuminate\Support\Facades\Log::error('Fragment not found for enrichment', ['fragment_id' => $fragment->id]);
                    return;
                }

                \Illuminate\Support\Facades\Log::debug('Starting enrichment pipeline', ['fragment_id' => $freshFragment->id]);

                app(\Illuminate\Pipeline\Pipeline::class)
                    ->send($freshFragment)
                    ->through([
                        \App\Actions\DriftSync::class,
                        \App\Actions\ParseAtomicFragment::class,
                        \App\Actions\ExtractMetadataEntities::class,
                        \App\Actions\GenerateAutoTitle::class,
                        \App\Actions\EnrichFragmentWithAI::class,
                        \App\Actions\InferFragmentType::class,
                        \App\Actions\SuggestTags::class,
                        \App\Actions\RouteToVault::class,
                        \App\Actions\EmbedFragmentAction::class,
                    ])
                    ->thenReturn();

                \Illuminate\Support\Facades\Log::debug('Enrichment pipeline completed', ['fragment_id' => $freshFragment->id]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Enrichment pipeline failed', [
                    'fragment_id' => $fragment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $fragment->refresh();
                $fragment->metadata = array_merge($fragment->metadata ?? [], [
                    'enrichment_status' => 'pipeline_failed',
                    'error' => $e->getMessage(),
                ]);
                $fragment->save();
            }
        })->onQueue('fragments');

        return response()->json($fragment);
    }

    public function update(Request $request, Fragment $fragment)
    {
        $fragment->update($request->only(['type', 'message', 'tags', 'relationships']));

        return response()->json($fragment);
    }

    public function index(Request $request)
    {
        return response()->json(
            Fragment::query()
                ->latest()
                ->get()
        );
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $vault = $request->get('vault');
        $projectId = $request->get('project_id');
        $sessionId = $request->get('session_id');
        $limit = $request->get('limit', 20);

        // Use the new SearchFragments action for advanced search
        $searchAction = app(\App\Actions\SearchFragments::class);
        $results = $searchAction(
            query: $query,
            vault: $vault,
            projectId: $projectId ? (int) $projectId : null,
            sessionId: $sessionId,
            limit: min($limit, 100) // Cap at 100 results
        );

        // Return results with search scores
        return response()->json(
            $results->map(function ($fragment) {
                return [
                    'id' => $fragment->id,
                    'type' => $fragment->type?->value ?? 'log',
                    'title' => $fragment->title,
                    'message' => $fragment->message,
                    'tags' => $fragment->tags,
                    'category' => $fragment->category,
                    'project' => $fragment->project,
                    'created_at' => $fragment->created_at,
                    'search_score' => $fragment->search_score ?? 0,
                    'vault' => $fragment->vault,
                    'parsed_entities' => $fragment->parsed_entities,
                ];
            })
        );
    }

    //    public function hybridSearch(string $query, string $provider = null, int $limit = 20)
    //    {
    //        $provider = $provider ?: config('fragments.embeddings.provider');
    //
    //        $emb = app(Embeddings::class)->embed($query, $provider);
    //        $qe  = '['.implode(',', $emb['vector']).']';
    //
    //        $textExpr = Schema::hasColumn('fragments', 'edited_message')
    //            ? "coalesce(f.title,'') || ' ' || coalesce(f.edited_message,'')"
    //            : "coalesce(f.title,'') || ' ' || coalesce(f.message,'')";
    //
    //        $sql = <<<SQL
    //            WITH q AS (SELECT ?::vector AS qe)
    //            SELECT
    //              f.id, f.title,
    //              (1 - (e.embedding <=> q.qe)) AS vec_sim,
    //              ts_rank_cd(to_tsvector('simple', {$textExpr}), plainto_tsquery('simple', ?)) AS txt_rank,
    //              (0.6 * ts_rank_cd(to_tsvector('simple', {$textExpr}), plainto_tsquery('simple', ?))
    //               + 0.4 * (1 - (e.embedding <=> q.qe))) AS score
    //            FROM fragments f
    //            JOIN fragment_embeddings e
    //              ON e.fragment_id = f.id AND e.provider = ?
    //            CROSS JOIN q
    //            ORDER BY score DESC
    //            LIMIT ?
    //            SQL;
    //
    //        return DB::select($sql, [$qe, $query, $query, $provider, $limit]);
    //    }

    public function hybridSearch(Request $request)
    {
        $q = (string) $request->query('q', '');
        $provider = (string) $request->query('provider', config('fragments.embeddings.provider'));
        $limit = (int) $request->query('limit', 20);

        if ($q === '') {
            return response()->json([]);
        }

        if (! config('fragments.embeddings.enabled')) {
            $results = app(\App\Actions\SearchFragments::class)(
                query: $q,
                vault: null,
                projectId: null,
                sessionId: null,
                limit: $limit
            )->map(fn ($fragment) => [
                'id' => $fragment->id,
                'title' => $fragment->title,
                'snippet' => \Illuminate\Support\Str::limit(strip_tags($fragment->message ?? ''), 200),
                'vec_sim' => null,
                'txt_rank' => $fragment->search_score ?? 0,
                'score' => $fragment->search_score ?? 0,
                'search_mode' => 'text-only',
            ])->values();

            return response()->json($results);
        }

        // Check if we have pgvector support
        if (! $this->hasPgVectorSupport()) {
            \Illuminate\Support\Facades\Log::warning('FragmentController: pgvector not available, falling back to text search');
            $results = app(\App\Actions\SearchFragments::class)(
                query: $q,
                vault: null,
                projectId: null,
                sessionId: null,
                limit: $limit
            )->map(fn ($fragment) => [
                'id' => $fragment->id,
                'title' => $fragment->title,
                'snippet' => \Illuminate\Support\Str::limit(strip_tags($fragment->message ?? ''), 200),
                'vec_sim' => null,
                'txt_rank' => $fragment->search_score ?? 0,
                'score' => $fragment->search_score ?? 0,
                'search_mode' => 'text-only-fallback',
            ])->values();

            return response()->json($results);
        }

        try {
            // 1) query embedding
            $emb = app(Embeddings::class)->embed($q, $provider);
            $qe = '['.implode(',', $emb['vector']).']';

            // 2) choose text expr (edits first)
            $hasEdited = Schema::hasColumn('fragments', 'edited_message');
            $bodyExpr = $hasEdited ? "coalesce(f.edited_message, f.message, '')"
                : "coalesce(f.message, '')";
            $docExpr = "coalesce(f.title,'') || ' ' || {$bodyExpr}";

            // 3) hybrid SQL with snippet
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

            $rows = DB::select($sql, [$qe, $q, $provider, $limit]);

            // Add search mode to each result
            $rows = array_map(function ($row) {
                $row->search_mode = 'hybrid';

                return $row;
            }, $rows);

            return response()->json($rows);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('FragmentController: hybrid search failed', [
                'query' => $q,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            // Fallback to text search
            $results = app(\App\Actions\SearchFragments::class)(
                query: $q,
                vault: null,
                projectId: null,
                sessionId: null,
                limit: $limit
            )->map(fn ($fragment) => [
                'id' => $fragment->id,
                'title' => $fragment->title,
                'snippet' => \Illuminate\Support\Str::limit(strip_tags($fragment->message ?? ''), 200),
                'vec_sim' => null,
                'txt_rank' => $fragment->search_score ?? 0,
                'score' => $fragment->search_score ?? 0,
                'search_mode' => 'text-only-error-fallback',
            ])->values();

            return response()->json($results);
        }
    }

    public function recall(Request $request)
    {
        $query = Fragment::with('category')->latest();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $limit = $request->get('limit', 5);

        return response()->json(
            $query->take($limit)->get()
        );
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
