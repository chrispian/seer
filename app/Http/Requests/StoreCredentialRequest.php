<?php

namespace App\Http\Requests;

use App\Rules\ValidCredentialFormat;
use Illuminate\Foundation\Http\FormRequest;

class StoreCredentialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $provider = $this->route('provider');

        return [
            'credentials' => [
                'required',
                'array',
                new ValidCredentialFormat($provider),
            ],
            'credential_type' => 'sometimes|string|in:api_key,oauth,basic_auth,bearer_token',
            'metadata' => 'sometimes|array',
            'ui_metadata' => 'sometimes|array',
            'expires_at' => 'sometimes|nullable|date',
            'test_on_create' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'credentials.required' => 'Credentials are required.',
            'credentials.array' => 'Credentials must be provided as an object.',
            'credential_type.in' => 'Credential type must be one of: api_key, oauth, basic_auth, bearer_token.',
            'expires_at.date' => 'Expiration date must be a valid date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default credential type if not provided
        if (! $this->has('credential_type')) {
            $this->merge(['credential_type' => 'api_key']);
        }

        // Default to testing credentials on creation
        if (! $this->has('test_on_create')) {
            $this->merge(['test_on_create' => true]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $provider = $this->route('provider');
            $credentials = $this->input('credentials', []);

            // Provider-specific validation
            $this->validateProviderSpecificRequirements($validator, $provider, $credentials);
        });
    }

    /**
     * Validate provider-specific requirements
     */
    protected function validateProviderSpecificRequirements($validator, string $provider, array $credentials): void
    {
        switch ($provider) {
            case 'openai':
                if (empty($credentials['api_key'])) {
                    $validator->errors()->add('credentials.api_key', 'OpenAI API key is required.');
                }
                break;

            case 'anthropic':
                if (empty($credentials['api_key'])) {
                    $validator->errors()->add('credentials.api_key', 'Anthropic API key is required.');
                }
                break;

            case 'openrouter':
                if (empty($credentials['api_key'])) {
                    $validator->errors()->add('credentials.api_key', 'OpenRouter API key is required.');
                }
                break;

            case 'ollama':
                if (empty($credentials['base_url'])) {
                    $validator->errors()->add('credentials.base_url', 'Ollama base URL is required.');
                }
                break;
        }
    }
}
