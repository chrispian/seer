<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ParseChaosFragment
{
    public function __invoke(Fragment $fragment): Fragment|array
    {

        Log::debug('Made it to parse chaos fragment');
        $prompt = <<<PROMPT
The following text contains multiple different tasks or thoughts mixed together.

Split it into **multiple self-contained JSON fragments**. Each should represent **one idea or task**.

Output an array of valid JSON objects like this (Do not include markdown or anything except valid json):

[
  {
    "type": "todo",
    "message": "Call the doctor.",
    "tags": ["health"]
  },
  {
    "type": "reminder",
    "message": "Email the client before noon.",
    "tags": ["work"]
  }
]

Input:
{$fragment->message}

ONLY return an array of JSON objects. No explanation, no markdown, no prose.
PROMPT;

        $response = Http::timeout(20)->post('http://localhost:11434/api/generate', [
            'model' => 'llama3',
            'prompt' => $prompt,
            'stream' => false,
        ]);

        if (! $response->ok()) {
            Log::error('Chaos parse HTTP failed', ['fragment_id' => $fragment->id]);

            return [
                'error' => $response->json('error'),
            ];

        }

        Log::debug('Raw chaos response before parse', ['raw' => $response->json()]);

        $raw = $response->json('response');

        // Step 1: Extract markdown
        if (preg_match('/```(?:json)?\s*(\[.*?\])\s*```/s', $raw, $matches)) {
            $raw = $matches[1];
        }

        // Step 2: Force decode if still a string
        if (is_string($raw)) {
            $raw = trim($raw);
            $raw = json_decode($raw, true);
        }

        // Step 3: Ensure it's an array now
        if (! is_array($raw)) {
            Log::error('Chaos fragment parse failed', [
                'fragment_id' => $fragment->id,
                'raw' => $raw,
            ]);

            return $fragment;
        }

        $atomicFragments = $raw;

        Log::debug('Chaos parse result', [
            'fragment_id' => $fragment->id,
            'raw' => $raw,
            'parsed' => $atomicFragments,
        ]);

        foreach ($atomicFragments as $entry) {
            if (! isset($entry['message'])) {
                continue;
            }

            $child = Fragment::create([
                'message' => $entry['message'],
                'type' => $entry['type'] ?? 'note',
                'tags' => $entry['tags'] ?? $fragment->tags,  // inherit if missing
                'vault' => $fragment->vault,
                'source' => $fragment->source ?? 'llama',      // inherit or default
                'state' => $entry['state'] ?? ['status' => 'open'],
                'metadata' => array_merge([
                    'origin_fragment_id' => $fragment->id,
                    'origin_type' => 'chaos',
                    'source' => 'llama',
                ], $entry['metadata'] ?? []),
            ]);

            $children[] = $child->id;

            // Run each new fragment through the pipeline (without ParseChaosFragment)

            dispatch(function () use ($child) {
                app(Pipeline::class)
                    ->send($child)
                    ->through([
                        \App\Actions\DriftSync::class,
                        \App\Actions\ParseAtomicFragment::class,
                        \App\Actions\ExtractMetadataEntities::class,
                        \App\Actions\GenerateAutoTitle::class,
                        \App\Actions\EnrichFragmentWithLlama::class,
                        \App\Actions\InferFragmentType::class,
                        \App\Actions\SuggestTags::class,
                        \App\Actions\RouteToVault::class,
                        \App\Actions\EmbedFragmentAction::class,
                    ])
                    ->thenReturn();
            })->onQueue('fragments');
        }

        $metadata = $fragment->metadata ?? [];

        $metadata = array_merge($metadata, [
            'children' => $children,
            'chaos_parsed_at' => now()->toISOString(),
            'child_count' => count($children),
            'chaos_lineage' => [
                'model' => 'llama3',
                'parsed_on' => now()->toISOString(),
                'child_ids' => $children,
            ],
        ]);

        $fragment->metadata = $metadata;
        $fragment->save();

        return [
            'status' => 'chaos_parsed',
            'fragment_id' => $fragment->id,
            'child_count' => count($children),
            'child_ids' => $children,
            'vault' => $fragment->vault,
            'summary' => 'Chaos fragment was split into '.count($children)." atomic fragments and routed to vault `{$fragment->vault}`.",
        ];

    }
}
