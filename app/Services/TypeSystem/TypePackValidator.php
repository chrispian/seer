<?php

namespace App\Services\TypeSystem;

use App\Services\AI\JsonSchemaValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TypePackValidator
{
    public function __construct(
        protected JsonSchemaValidator $jsonValidator
    ) {}

    /**
     * Validate fragment state against type schema
     */
    public function validateFragmentState(array $state, string $typeSlug): array
    {
        $typePack = app(TypePackLoader::class)->loadTypePack($typeSlug);
        
        if (!$typePack) {
            throw new \InvalidArgumentException("Type pack not found: {$typeSlug}");
        }

        $schema = $typePack['schema'] ?? null;
        if (!$schema) {
            // No schema means no validation required
            return $state;
        }

        return $this->validateAgainstJsonSchema($state, $schema, $typeSlug);
    }

    /**
     * Validate data against JSON schema
     */
    protected function validateAgainstJsonSchema(array $data, array $schema, string $typeSlug): array
    {
        $validator = new \JsonSchema\Validator();
        $dataObj = json_decode(json_encode($data));
        $schemaObj = json_decode(json_encode($schema));

        $validator->validate($dataObj, $schemaObj);

        if (!$validator->isValid()) {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = [
                    'property' => $error['property'],
                    'message' => $error['message'],
                    'constraint' => $error['constraint'] ?? null,
                ];
            }

            Log::warning('Type pack validation failed', [
                'type_slug' => $typeSlug,
                'errors' => $errors,
                'data' => $data,
            ]);

            throw ValidationException::withMessages([
                'state' => "Type validation failed for {$typeSlug}: " . 
                          collect($errors)->pluck('message')->implode('; ')
            ]);
        }

        return $data;
    }

    /**
     * Get validation errors in human-readable format
     */
    public function getValidationErrors(array $state, string $typeSlug): array
    {
        try {
            $this->validateFragmentState($state, $typeSlug);
            return [];
        } catch (ValidationException $e) {
            return $e->errors();
        } catch (\Exception $e) {
            return ['general' => [$e->getMessage()]];
        }
    }

    /**
     * Check if state is valid without throwing exceptions
     */
    public function isValidState(array $state, string $typeSlug): bool
    {
        try {
            $this->validateFragmentState($state, $typeSlug);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}