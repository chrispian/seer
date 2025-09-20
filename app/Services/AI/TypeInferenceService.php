<?php

namespace App\Services\AI;

use App\Models\Fragment;
use App\Models\Type;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;

class TypeInferenceService
{
    protected array $availableTypes;

    protected float $confidenceThreshold;

    protected ModelSelectionService $modelSelection;

    public function __construct(ModelSelectionService $modelSelection, float $confidenceThreshold = 0.7)
    {
        $this->modelSelection = $modelSelection;
        $this->confidenceThreshold = $confidenceThreshold;
        $this->availableTypes = $this->getAvailableTypes();
    }

    /**
     * Infer the type of a fragment using AI
     */
    public function inferType(Fragment $fragment): array
    {
        try {
            // Build context for model selection
            $context = [
                'operation_type' => 'text',
                'command' => 'type_inference',
                'vault' => $fragment->vault,
                'project_id' => $fragment->project_id,
            ];

            // Select appropriate model
            $selectedModel = $this->modelSelection->selectTextModel($context);

            $response = Prism::text()
                ->using($selectedModel['provider'], $selectedModel['model'])
                ->withPrompt($this->buildPrompt($fragment))
                ->generate();

            $result = $this->parseResponse($response->text);

            // Add model metadata to result
            $result['model_provider'] = $selectedModel['provider'];
            $result['model_name'] = $selectedModel['model'];

            Log::info('TypeInferenceService: AI response', [
                'fragment_id' => $fragment->id,
                'result' => $result,
                'model_provider' => $selectedModel['provider'],
                'model_name' => $selectedModel['model'],
                'usage' => $response->usage ? (array) $response->usage : null,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('TypeInferenceService: AI inference failed', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to default
            return [
                'type' => 'log',
                'confidence' => 0.0,
                'reasoning' => 'AI inference failed, using default',
                'model_provider' => null,
                'model_name' => null,
            ];
        }
    }

    /**
     * Apply the inferred type to a fragment if confidence is high enough
     */
    public function applyTypeToFragment(Fragment $fragment): Fragment
    {
        $inference = $this->inferType($fragment);

        if ($inference['confidence'] >= $this->confidenceThreshold) {
            $type = Type::where('value', $inference['type'])->first();
            if ($type) {
                $fragment->update([
                    'type' => $inference['type'],
                    'type_id' => $type->id,
                    'model_provider' => $inference['model_provider'],
                    'model_name' => $inference['model_name'],
                ]);

                Log::info('TypeInferenceService: Applied type to fragment', [
                    'fragment_id' => $fragment->id,
                    'type' => $inference['type'],
                    'confidence' => $inference['confidence'],
                    'model_provider' => $inference['model_provider'],
                    'model_name' => $inference['model_name'],
                ]);
            }
        } else {
            // Low confidence, default to 'log'
            $logType = Type::where('value', 'log')->first();
            if ($logType) {
                $fragment->update([
                    'type' => 'log',
                    'type_id' => $logType->id,
                    'model_provider' => $inference['model_provider'],
                    'model_name' => $inference['model_name'],
                ]);

                Log::info('TypeInferenceService: Applied default log type', [
                    'fragment_id' => $fragment->id,
                    'confidence' => $inference['confidence'],
                    'reason' => 'Low confidence',
                    'model_provider' => $inference['model_provider'],
                    'model_name' => $inference['model_name'],
                ]);
            }
        }

        return $fragment->fresh();
    }

    /**
     * Build the AI prompt for type inference
     */
    protected function buildPrompt(Fragment $fragment): string
    {
        $typesJson = json_encode($this->availableTypes, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a text classifier that categorizes fragments of information into specific types.

Available types (JSON format):
{$typesJson}

Fragment to classify:
"{$fragment->message}"

Instructions:
1. Analyze the fragment content carefully
2. Choose the most appropriate type from the available types
3. Provide a confidence score between 0.0 and 1.0 (1.0 = completely confident)
4. If you're not confident (< 0.7), default to 'log'
5. Respond in this exact JSON format:

{
  "type": "selected_type_value",
  "confidence": 0.85,
  "reasoning": "Brief explanation of why this type was chosen"
}

Only respond with valid JSON, no additional text.
PROMPT;
    }

    /**
     * Parse the AI response into structured data
     */
    protected function parseResponse(string $response): array
    {
        try {
            // Clean up the response - remove markdown code blocks if present
            $cleanedResponse = preg_replace('/```json\s*|\s*```/', '', trim($response));
            $data = json_decode($cleanedResponse, true, 512, JSON_THROW_ON_ERROR);

            // Validate required fields
            if (! isset($data['type']) || ! isset($data['confidence'])) {
                throw new \InvalidArgumentException('Invalid AI response format');
            }

            // Ensure type exists in our available types
            $typeExists = collect($this->availableTypes)->contains('value', $data['type']);
            if (! $typeExists) {
                $data['type'] = 'log';
                $data['confidence'] = 0.0;
                $data['reasoning'] = 'Unknown type returned by AI, defaulting to log';
            }

            // Normalize confidence to 0-1 range
            $data['confidence'] = max(0.0, min(1.0, (float) $data['confidence']));

            return $data;

        } catch (\Exception $e) {
            Log::error('TypeInferenceService: Failed to parse AI response', [
                'response' => $response,
                'error' => $e->getMessage(),
            ]);

            return [
                'type' => 'log',
                'confidence' => 0.0,
                'reasoning' => 'Failed to parse AI response',
            ];
        }
    }

    /**
     * Get available types from database
     */
    protected function getAvailableTypes(): array
    {
        return Type::select(['id', 'value', 'label'])
            ->orderBy('value')
            ->get()
            ->toArray();
    }

    /**
     * Update available types cache
     */
    public function refreshAvailableTypes(): self
    {
        $this->availableTypes = $this->getAvailableTypes();

        return $this;
    }
}
