<?php

    namespace App\Http\Controllers;

    use App\Actions\EnrichFragmentWithLlama;
    use App\Actions\RouteToVault;
    use App\Actions\SuggestTags;
    use App\Models\Category;
    use App\Models\Fragment;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Pipeline;

    class FragmentController extends Controller
    {
        public function store(Request $request)
        {
            $request->validate([
                'message' => 'required|string',
            ]);

            // Create base fragment with only required message field
            $fragment = Fragment::create([
                'message' => $request->input('message'),
                'type' => 'chaos', // ← this is the fix
                'source' => $request->input('source'),
                'relationships' => $request->input('relationships', []),
            ]);

            // Run parse action directly (sync)
             app(\App\Actions\ParseChaosFragment::class)($fragment);

            // Attach category if present
            if ($categoryName = $request->input('category')) {
                $category = Category::firstOrCreate(['name' => $categoryName]);
                $fragment->category_id = $category->id;
                $fragment->save();
            }

            // Dispatch enrichment pipeline (async)
//            dispatch(function () use ($fragment) {
//                try {
//                    app(Pipeline::class)
//                        ->send($fragment)
//                        ->through([
//                            \App\Actions\ParseChaosFragment::class,
////                            // \App\Actions\ParseAtomicFragment::class,
////                            \App\Actions\EnrichFragmentWithLlama::class,
////                            \App\Actions\InferFragmentType::class,
////                            \App\Actions\SuggestTags::class,
////                            \App\Actions\RouteToVault::class,
//
//                        ])
//                        ->thenReturn();
//                } catch (\Throwable $e) {
//                    Log::error('Enrichment pipeline failed', [
//                        'fragment_id' => $fragment->id,
//                        'error' => $e->getMessage(),
//                    ]);
//
//                    $fragment->metadata = array_merge($fragment->metadata ?? [], [
//                        'enrichment_status' => 'pipeline_failed',
//                        'error' => $e->getMessage(),
//                    ]);
//                    $fragment->save();
//                }
//            })->onQueue('fragments');

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
            $query = $request->get('q');

            return response()->json(
                \App\Models\Fragment::with('category')
                    ->where('message', 'like', "%{$query}%")
                    ->orWhereJsonContains('tags', $query)
                    ->latest()
                    ->limit(10)
                    ->get()
            );
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


    }
