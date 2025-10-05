<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ResetSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', 'in:preferences,ai,notifications,layout'],
            'confirmation_token' => ['required', 'string', 'size:32'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'sections.required' => 'Please select at least one section to reset.',
            'sections.min' => 'Please select at least one section to reset.',
            'sections.*.in' => 'Invalid settings section selected.',
            'confirmation_token.required' => 'Reset confirmation token is required.',
            'confirmation_token.size' => 'Invalid confirmation token format.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Verify the token exists in session and hasn't expired
        $sessionToken = session('settings_reset_token');
        $tokenExpires = session('settings_reset_token_expires');

        if (! $sessionToken || ! $tokenExpires || now()->isAfter($tokenExpires)) {
            $this->merge([
                'confirmation_token' => 'invalid',
            ]);

            return;
        }

        // Verify token matches
        if ($this->input('confirmation_token') !== $sessionToken) {
            $this->merge([
                'confirmation_token' => 'invalid',
            ]);

            return;
        }

        // Clear the token after use
        session()->forget(['settings_reset_token', 'settings_reset_token_expires']);
    }
}
