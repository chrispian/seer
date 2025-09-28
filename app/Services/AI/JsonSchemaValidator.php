<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JsonSchemaValidator
{
    protected int $maxRetries;
    protected array $schemas;

    public function __construct(int $maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
        $this->schemas = $this->loadSchemas();
    }

    /**
     * Validate and parse JSON response with retry logic
     */
    public function validateAndParse(
        string $rawResponse,
        string $schemaType,
        ?string $correlationId = null,
        array $context = []
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        Log::info('JsonSchemaValidator: Starting validation', [
            'correlation_id' => $correlationId,
            'schema_type' => $schemaType,
            'context' => $context,
        ]);

        $attempts = 0;
        $lastError = null;

        while ($attempts < $this->maxRetries) {
            $attempts++;

            try {
                $cleanedJson = $this->cleanJsonResponse($rawResponse);
                $parsed = $this->parseJson($cleanedJson);
                $validated = $this->validateAgainstSchema($parsed, $schemaType);

                Log::info('JsonSchemaValidator: Validation successful', [
                    'correlation_id' => $correlationId,
                    'schema_type' => $schemaType,
                    'attempt' => $attempts,
                ]);

                return [
                    'success' => true,
                    'data' => $validated,
                    'correlation_id' => $correlationId,
                    'attempts' => $attempts,
                ];

            } catch (\Exception $e) {
                $lastError = $e;

                Log::warning('JsonSchemaValidator: Validation failed', [
                    'correlation_id' => $correlationId,
                    'schema_type' => $schemaType,
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                    'raw_response_length' => strlen($rawResponse),
                ]);

                // For retry attempts, try alternative cleaning strategies
                if ($attempts < $this->maxRetries) {
                    $rawResponse = $this->applyAlternativeCleaningStrategy($rawResponse, $attempts);
                }
            }
        }

        // All retries failed
        Log::error('JsonSchemaValidator: All validation attempts failed', [
            'correlation_id' => $correlationId,
            'schema_type' => $schemaType,
            'total_attempts' => $attempts,
            'final_error' => $lastError->getMessage(),
            'context' => $context,
        ]);

        return [
            'success' => false,
            'error' => $lastError->getMessage(),
            'correlation_id' => $correlationId,
            'attempts' => $attempts,
        ];
    }

    /**
     * Clean JSON response using multiple strategies
     */
    protected function cleanJsonResponse(string $raw): string
    {
        $raw = trim($raw);

        // Strategy 1: Extract from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*?\}|\[.*?\])\s*```/s', $raw, $matches)) {
            return $matches[1];
        }

        // Strategy 2: Handle responses that start with explanatory text (only on first attempt)
        if (str_starts_with($raw, 'Here')) {
            $parts = explode('```', $raw);
            if (count($parts) >= 2) {
                return trim($parts[1]);
            }
        }

        return $raw;
    }

    /**
     * Apply alternative cleaning strategies for retry attempts
     */
    protected function applyAlternativeCleaningStrategy(string $raw, int $attempt): string
    {
        switch ($attempt) {
            case 1:
                // More aggressive markdown extraction
                return preg_replace('/^.*?(\{|\[)/', '$1', trim($raw));

            case 2:
                // Extract JSON from mixed content as last resort
                if (preg_match('/(\{.*?\}|\[.*?\])/s', $raw, $matches)) {
                    return $matches[1];
                }
                break;
        }

        return $raw;
    }

    /**
     * Parse JSON with proper error handling
     */
    protected function parseJson(string $json): array
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Parsed JSON is not an array or object');
        }

        return $data;
    }

    /**
     * Validate data against schema
     */
    protected function validateAgainstSchema(array $data, string $schemaType): array
    {
        if (!isset($this->schemas[$schemaType])) {
            throw new \InvalidArgumentException("Unknown schema type: {$schemaType}");
        }

        $schema = $this->schemas[$schemaType];

        return $this->validateData($data, $schema, $schemaType);
    }

    /**
     * Validate data structure against schema rules
     */
    protected function validateData(array $data, array $schema, string $schemaType): array
    {
        switch ($schemaType) {
            case 'fragment_enrichment':
                return $this->validateFragmentEnrichment($data);

            case 'type_inference':
                return $this->validateTypeInference($data);

            default:
                throw new \InvalidArgumentException("Validation not implemented for schema: {$schemaType}");
        }
    }



    /**
     * Validate fragment enrichment structure
     */
    protected function validateFragmentEnrichment(array $data): array
    {
        $required = ['type', 'message'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $result = [
            'type' => $data['type'],
            'message' => $data['message'],
            'metadata' => $data['metadata'] ?? ['confidence' => 0.9],
            'state' => $data['state'] ?? ['status' => 'open'],
            'vault' => $data['vault'] ?? 'default',
        ];

        // Only set tags if explicitly provided to preserve inheritance
        if (isset($data['tags'])) {
            $result['tags'] = $data['tags'];
        }

        return $result;
    }

    /**
     * Validate type inference structure
     */
    protected function validateTypeInference(array $data): array
    {
        if (!isset($data['type']) || !is_string($data['type'])) {
            throw new \InvalidArgumentException("Missing or invalid 'type' field");
        }

        if (!isset($data['confidence']) || !is_numeric($data['confidence'])) {
            throw new \InvalidArgumentException("Missing or invalid 'confidence' field");
        }

        // Validate that type exists in available types (only in non-test environments)
        if (!app()->runningUnitTests()) {
            $availableTypes = \App\Models\Type::pluck('value')->toArray();
            if (!in_array($data['type'], $availableTypes)) {
                // Default to 'log' if type doesn't exist
                $data['type'] = 'log';
                $data['confidence'] = 0.0;
                $data['reasoning'] = 'Unknown type returned by AI, defaulting to log';
            }
        }

        return [
            'type' => $data['type'],
            'confidence' => max(0.0, min(1.0, (float) $data['confidence'])),
            'reasoning' => $data['reasoning'] ?? 'No reasoning provided',
        ];
    }

    /**
     * Load schema definitions
     */
    protected function loadSchemas(): array
    {
        return [
            'fragment_enrichment' => [
                'type' => 'object',
                'required' => ['type', 'message'],
                'properties' => [
                    'type' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'tags' => ['type' => 'array'],
                    'metadata' => ['type' => 'object'],
                    'state' => ['type' => 'object'],
                    'vault' => ['type' => 'string'],
                ],
            ],
            'type_inference' => [
                'type' => 'object',
                'required' => ['type', 'confidence'],
                'properties' => [
                    'type' => ['type' => 'string'],
                    'confidence' => ['type' => 'number'],
                    'reasoning' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * Get correlation ID for request tracking
     */
    public function generateCorrelationId(): string
    {
        return Str::uuid()->toString();
    }
}