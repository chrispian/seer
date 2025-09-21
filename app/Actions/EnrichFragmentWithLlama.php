<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Services\AI\AIProviderManager;
use App\Services\AI\JsonSchemaValidator;
use App\Services\AI\ModelSelectionService;
use Illuminate\Support\Facades\Log;

class EnrichFragmentWithLlama
{
    protected ModelSelectionService $modelSelection;

    public function __construct(ModelSelectionService $modelSelection)
    {
        $this->modelSelection = $modelSelection;
    }

    public function handle(Fragment $fragment, $next)
    {
        $result = $this->__invoke($fragment);

        return $next($result ?? $fragment);
    }

    public function __invoke(Fragment $fragment): ?Fragment
    {
        if (app()->runningUnitTests()) {
            return $fragment;
        }

        Log::debug('EnrichFragmentWithLlama::invoke()');

        // Build context for model selection with enrichment-specific parameters
        $context = [
            'operation_type' => 'text',
            'command' => 'enrich_fragment',
            'vault' => $fragment->vault,
            'project_id' => $fragment->project_id,
        ];

        // Select appropriate model with deterministic parameters
        $selectedModel = $this->modelSelection->selectTextModel($context);

        $prompt = <<<PROMPT
Given the following user input, return a structured fragment in JSON.

Input:
{$fragment->message}

Output format:
{
  "type": "log",
  "message": "...",
  "tags": ["tag1", "tag2"],
  "metadata": {
    "confidence": 0.9
  },
  "state": {
    "status": "open"
  },
  "vault": "default"
}
Only return JSON. No markdown, no explanation.
PROMPT;

        try {
            // Use AIProviderManager with deterministic controls
            $aiProvider = app(AIProviderManager::class);
            $aiResponse = $aiProvider->generateText($prompt, $context);

            Log::info('Fragment enrichment AI response', [
                'fragment_id' => $fragment->id,
                'provider' => $aiResponse['provider'],
                'model' => $aiResponse['model'],
                'usage' => $aiResponse['usage'] ?? null,
            ]);

            // Validate and parse JSON response using schema validation with retry logic
            $validator = app(JsonSchemaValidator::class);
            $correlationId = $validator->generateCorrelationId();

            $validationResult = $validator->validateAndParse(
                $aiResponse['text'],
                'fragment_enrichment',
                $correlationId,
                [
                    'fragment_id' => $fragment->id,
                    'provider' => $aiResponse['provider'],
                    'model' => $aiResponse['model'],
                ]
            );

            if (!$validationResult['success']) {
                Log::error('Fragment enrichment JSON validation failed', [
                    'fragment_id' => $fragment->id,
                    'correlation_id' => $correlationId,
                    'error' => $validationResult['error'],
                    'attempts' => $validationResult['attempts'],
                    'provider' => $aiResponse['provider'],
                    'model' => $aiResponse['model'],
                ]);

                return null;
            }

            Log::info('Fragment enrichment JSON validation successful', [
                'fragment_id' => $fragment->id,
                'correlation_id' => $correlationId,
                'attempts' => $validationResult['attempts'],
                'provider' => $aiResponse['provider'],
                'model' => $aiResponse['model'],
            ]);

            $parsed = $validationResult['data'];

        } catch (\Exception $e) {
            Log::error('AI enrichment failed', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        // Save enrichment with model metadata
        $fragment->metadata = array_merge((array) $fragment->metadata, [
            'enrichment' => $parsed,
            'enrichment_validation' => [
                'correlation_id' => $correlationId,
                'validation_attempts' => $validationResult['attempts'],
                'validated_at' => now()->toISOString(),
            ],
        ]);

        if (! empty($parsed['type'])) {
            // Find type by value and set both type string and type_id
            $typeModel = \App\Models\Type::where('value', $parsed['type'])->first();
            if ($typeModel) {
                $fragment->type = $parsed['type'];
                $fragment->type_id = $typeModel->id;
            }
        }

        // Store model metadata from actual AI response
        $fragment->model_provider = $aiResponse['provider'];
        $fragment->model_name = $aiResponse['model'];

        $fragment->save();

        Log::info('Fragment enriched with AI', [
            'fragment_id' => $fragment->id,
            'provider' => $aiResponse['provider'],
            'model' => $aiResponse['model'],
            'usage' => $aiResponse['usage'] ?? null,
        ]);

        return $fragment;
    }
}
