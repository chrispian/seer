<?php

    namespace App\Http\Controllers;

    use App\Models\Fragment;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Http;

    class FragmentController extends Controller
    {
        public function store(Request $request)
        {
            $request->validate([
                'message' => 'required|string',
            ]);

            $parsed = app(\App\Actions\ParseFragmentInput::class)($request->input('message'));
            $data = array_merge($request->only(['relationships', 'category', 'source']), $parsed);

            // Find or create category
            if (!empty($data['category'])) {
                $category = \App\Models\Category::firstOrCreate(['name' => $data['category']]);
                $data['category_id'] = $category->id;
            }

            unset($data['category']); // prevent mass-assignment issues

            $fragment = \App\Models\Fragment::create($data);

            // Enrichment via LLaMA
            dispatch(function () use ($fragment) {
                try {
                    $prompt = app(\App\Http\Controllers\AnalyzeFragmentController::class)
                        ->buildPrompt($fragment->message, '', $fragment->type);


                    $response = Http::timeout(20)->post('http://localhost:11434/api/generate', [
                        'model' => 'llama3',
                        'prompt' => $prompt,
                        'stream' => false,
                    ]);

                    if ($response->ok()) {
                        $llamaResponse = $response->json();

                        \Log::info('LLaMA response raw', [
                            'fragment_id' => $fragment->id,
                            'prompt' => $prompt,
                            'raw_response' => $llamaResponse,
                        ]);

                        // Extract JSON block from the response
                        $raw = $llamaResponse['response'];
                        $cleanJson = $raw;

                        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $raw, $matches)) {
                            $cleanJson = $matches[1];
                        } elseif (str_starts_with(trim($raw), 'Here')) {
                            $parts = explode('```', $raw);
                            $cleanJson = $parts[1] ?? $raw;
                        }

                        $parsed = json_decode($cleanJson, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            \Log::info('LLaMA enrichment parsed', [
                                'fragment_id' => $fragment->id,
                                'enrichment' => $parsed,
                            ]);

                            $currentMetadata = is_array($fragment->metadata) ? $fragment->metadata : [];
                            $fragment->metadata = array_merge($currentMetadata, [
                                'enrichment' => $parsed,
                            ]);

                            // Optionally sync enriched type back to main `type` field if it’s in the approved list
                            $validTypes = [
                                'bookmark', 'log', 'observation', 'insight', 'link',
                                'media', 'seed', 'shard', 'note', 'todo', 'calendar',
                                'reminder', 'contact', 'article', 'project'
                            ];

                            if (!empty($parsed['type']) && in_array($parsed['type'], $validTypes)) {
                                $fragment->type = $parsed['type'];
                            }


                            $fragment->save();
                        } else {
                            \Log::error('JSON decode failed', [
                                'fragment_id' => $fragment->id,
                                'error' => json_last_error_msg(),
                                'cleanJson' => $cleanJson,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error('Fragment enrichment failed', [
                        'fragment_id' => $fragment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

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
