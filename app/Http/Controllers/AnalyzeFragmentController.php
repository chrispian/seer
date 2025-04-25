<?php

namespace App\Http\Controllers;

use App\Actions\ParseAtomicFragment;
use App\Models\Fragment;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class AnalyzeFragmentController extends Controller
{
    public function __invoke(Request $request)
    {
//        $request->validate([
//            'message' => 'required|string',
//            'context' => 'nullable|string',
//            'model' => 'nullable|string', // default to llama3
//            'type' => 'nullable|string',
//        ]);
//
//        $message = $request->input('message');
//        $context = $request->input('context', '');
//        $model = $request->input('model', 'llama3');
//        $type = $request->input('type');
//
//        $prompt = $this->buildPrompt($message, $context, $type);
//
//        $ollamaResponse = Http::timeout(20)
//            ->post('http://localhost:11434/api/generate', [
//                'model' => $model,
//                'prompt' => $prompt,
//                'stream' => false,
//            ]);
//
//        if (! $ollamaResponse->ok()) {
//            Log::error('Fragment enrichment failed', [
//                'status' => $ollamaResponse->status(),
//                'response' => $ollamaResponse->body(),
//            ]);
//            return response()->json([
//                'error' => 'Failed to enrich fragment',
//                'details' => $ollamaResponse->body(),
//            ], Response::HTTP_BAD_GATEWAY);
//        }
//
//        $data = $ollamaResponse->json();
//        return response()->json([
//            'message' => $message,
//            'model' => $model,
//            'analysis' => $data['response'] ?? null,
//        ]);

        $parsed = app(ParseAtomicFragment::class)($request->input('message'));

        $fragment = Fragment::create(array_merge($request->only(['source']), $parsed));

        dispatch(new \App\Jobs\EnrichFragment($fragment))->onQueue('fragments');



    }

    public function buildPrompt(string $message, string $context = '', ?string $type = null): string
    {

        $typeHint = $type ? "The user has categorized this fragment as: \"{$type}\".\nUse this as a strong signal when selecting the type, but confirm based on context.\n\n" : '';

        return <<<EOT
You are an assistant that helps enrich personal thought fragments.

{$typeHint}
Analyze the following input and return structured metadata as JSON.

Use only the following for the "type" field, picking the one that best fits:
bookmark, log, observation, insight, link, media, seed, shard, note, todo, calendar, reminder, contact, article, project

---

Fragment:
\"\"\"
{$message}
\"\"\"

If applicable, extract:
- type (from the above list only)
- mood (e.g. focused, frustrated, inspired, curious)
- any implicit tags (keywords or topics)
- summary (one-liner)
- signals (e.g. urgency, emotional tone)
- narrative pattern if detectable (e.g. self-reflection, idea, task, plan)

Respond only with valid JSON.
EOT;
    }
}
