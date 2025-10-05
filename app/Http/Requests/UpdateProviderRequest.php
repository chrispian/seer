<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderRequest extends FormRequest
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
        return [
            'enabled' => 'sometimes|boolean',
            'ui_preferences' => 'sometimes|array',
            'ui_preferences.color' => 'sometimes|nullable|string',
            'ui_preferences.icon' => 'sometimes|nullable|string',
            'ui_preferences.display_name' => 'sometimes|nullable|string|max:255',
            'ui_preferences.description' => 'sometimes|nullable|string|max:1000',
            'ui_preferences.tags' => 'sometimes|array',
            'ui_preferences.tags.*' => 'string|max:50',
            'ui_preferences.featured' => 'sometimes|boolean',
            'ui_preferences.hidden' => 'sometimes|boolean',
            'capabilities' => 'sometimes|array',
            'rate_limits' => 'sometimes|array',
            'rate_limits.requests_per_minute' => 'sometimes|integer|min:1',
            'rate_limits.requests_per_hour' => 'sometimes|integer|min:1',
            'rate_limits.requests_per_day' => 'sometimes|integer|min:1',
            'rate_limits.tokens_per_minute' => 'sometimes|integer|min:1',
            'priority' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'enabled.boolean' => 'Enabled status must be true or false.',
            'ui_preferences.array' => 'UI preferences must be an object.',
            'ui_preferences.display_name.max' => 'Display name cannot exceed 255 characters.',
            'ui_preferences.description.max' => 'Description cannot exceed 1000 characters.',
            'ui_preferences.tags.array' => 'Tags must be an array.',
            'ui_preferences.tags.*.string' => 'Each tag must be a string.',
            'ui_preferences.tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'ui_preferences.featured.boolean' => 'Featured status must be true or false.',
            'ui_preferences.hidden.boolean' => 'Hidden status must be true or false.',
            'capabilities.array' => 'Capabilities must be an object.',
            'rate_limits.array' => 'Rate limits must be an object.',
            'rate_limits.requests_per_minute.integer' => 'Requests per minute must be an integer.',
            'rate_limits.requests_per_minute.min' => 'Requests per minute must be at least 1.',
            'rate_limits.requests_per_hour.integer' => 'Requests per hour must be an integer.',
            'rate_limits.requests_per_hour.min' => 'Requests per hour must be at least 1.',
            'rate_limits.requests_per_day.integer' => 'Requests per day must be an integer.',
            'rate_limits.requests_per_day.min' => 'Requests per day must be at least 1.',
            'rate_limits.tokens_per_minute.integer' => 'Tokens per minute must be an integer.',
            'rate_limits.tokens_per_minute.min' => 'Tokens per minute must be at least 1.',
            'priority.integer' => 'Priority must be an integer.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 100.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            $this->validateRateLimits($validator);
            $this->validateCapabilities($validator);
        });
    }

    /**
     * Validate rate limit consistency
     */
    protected function validateRateLimits($validator): void
    {
        $rateLimits = $this->input('rate_limits', []);

        if (! empty($rateLimits)) {
            $perMinute = $rateLimits['requests_per_minute'] ?? null;
            $perHour = $rateLimits['requests_per_hour'] ?? null;
            $perDay = $rateLimits['requests_per_day'] ?? null;

            // Validate that higher time periods have higher limits
            if ($perMinute && $perHour && ($perMinute * 60) > $perHour) {
                $validator->errors()->add('rate_limits.requests_per_hour',
                    'Requests per hour should be at least equal to requests per minute × 60.');
            }

            if ($perHour && $perDay && ($perHour * 24) > $perDay) {
                $validator->errors()->add('rate_limits.requests_per_day',
                    'Requests per day should be at least equal to requests per hour × 24.');
            }
        }
    }

    /**
     * Validate capabilities against provider configuration
     */
    protected function validateCapabilities($validator): void
    {
        $capabilities = $this->input('capabilities', []);
        $provider = $this->route('provider');

        if (! empty($capabilities)) {
            // Get provider config to validate against known capabilities
            $providerConfig = config("fragments.models.providers.{$provider}", []);

            if (empty($providerConfig)) {
                $validator->errors()->add('capabilities',
                    "Unknown provider '{$provider}' - cannot validate capabilities.");

                return;
            }

            // Validate that custom capabilities don't override core ones
            $coreCapabilities = ['text_models', 'embedding_models', 'supports_streaming', 'supports_function_calling'];

            foreach ($coreCapabilities as $coreCapability) {
                if (isset($capabilities[$coreCapability])) {
                    $validator->errors()->add("capabilities.{$coreCapability}",
                        "Core capability '{$coreCapability}' cannot be modified through this endpoint.");
                }
            }
        }
    }
}
