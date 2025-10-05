<?php

namespace App\Rules;

use App\Models\Provider;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProviderExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Provider name must be a string.');
            return;
        }

        // Check if provider exists in database
        $provider = Provider::where('provider', $value)
            ->orWhere('name', $value)
            ->orWhere('id', $value)
            ->first();

        if (!$provider) {
            $availableProviders = Provider::where('enabled', true)
                ->pluck('name')
                ->take(10)
                ->implode(', ');
            
            $fail("Provider '{$value}' is not supported. Available providers: {$availableProviders}");
            return;
        }

        // Check if provider is enabled
        if (!$provider->enabled) {
            $fail("Provider '{$value}' is currently disabled.");
            return;
        }

        // Check if provider has models
        if ($provider->models()->count() === 0) {
            $fail("Provider '{$value}' has no models configured.");
            return;
        }
    }
}
