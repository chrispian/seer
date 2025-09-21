<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Services\AI\AIProviderManager;
use App\Services\AI\JsonSchemaValidator;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;

class ParseChaosFragment
{
    public function __invoke(Fragment $fragment): Fragment|array
    {
        if (app()->runningUnitTests()) {
            return $fragment;
        }


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

        // Build context for model selection with parsing-specific parameters
        $context = [
            'operation_type' => 'text',
            'command' => 'parse_chaos',
            'vault' => $fragment->vault,
            'project_id' => $fragment->project_id,
        ];

        try {
            // Use AIProviderManager with deterministic controls
            $aiProvider = app(AIProviderManager::class);
            $aiResponse = $aiProvider->generateText($prompt, $context);

            Log::debug('AI chaos parsing response', [
                'fragment_id' => $fragment->id,
                'provider' => $aiResponse['provider'],
                'model' => $aiResponse['model'],
                'usage' => $aiResponse['usage'] ?? null,
            ]);

            $raw = $aiResponse['text'];

        } catch (\Exception $e) {
            Log::error('Chaos parse AI failed', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }

        // Validate and parse JSON response using schema validation with retry logic
        $validator = app(JsonSchemaValidator::class);
        $correlationId = $validator->generateCorrelationId();

        $validationResult = $validator->validateAndParse(
            $aiResponse['text'],
            'chaos_fragments',
            $correlationId,
            [
                'fragment_id' => $fragment->id,
                'provider' => $aiResponse['provider'],
                'model' => $aiResponse['model'],
            ]
        );

        if (!$validationResult['success']) {
            Log::error('Chaos fragment JSON validation failed', [
                'fragment_id' => $fragment->id,
                'correlation_id' => $correlationId,
                'error' => $validationResult['error'],
                'attempts' => $validationResult['attempts'],
                'provider' => $aiResponse['provider'],
                'model' => $aiResponse['model'],
            ]);

            return $fragment;
        }

        Log::info('Chaos fragment JSON validation successful', [
            'fragment_id' => $fragment->id,
            'correlation_id' => $correlationId,
            'attempts' => $validationResult['attempts'],
            'provider' => $aiResponse['provider'],
            'model' => $aiResponse['model'],
        ]);

        $atomicFragments = $validationResult['data'];
        $children = []; // Initialize the children array

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
                'provider' => $aiResponse['provider'] ?? 'unknown',
                'model' => $aiResponse['model'] ?? 'unknown',
                'parsed_on' => now()->toISOString(),
                'child_ids' => $children,
                'usage' => $aiResponse['usage'] ?? null,
                'correlation_id' => $correlationId,
                'validation_attempts' => $validationResult['attempts'],
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
