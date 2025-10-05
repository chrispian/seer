<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCredentialFormat implements ValidationRule
{
    protected string $provider;

    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('Credentials must be provided as an object.');

            return;
        }

        $errors = $this->validateCredentialFormat($value);

        if (! empty($errors)) {
            foreach ($errors as $error) {
                $fail($error);
            }
        }
    }

    /**
     * Validate credential format for the specific provider
     */
    protected function validateCredentialFormat(array $credentials): array
    {
        $errors = [];

        switch ($this->provider) {
            case 'openai':
                $errors = array_merge($errors, $this->validateOpenAICredentials($credentials));
                break;

            case 'anthropic':
                $errors = array_merge($errors, $this->validateAnthropicCredentials($credentials));
                break;

            case 'openrouter':
                $errors = array_merge($errors, $this->validateOpenRouterCredentials($credentials));
                break;

            case 'ollama':
                $errors = array_merge($errors, $this->validateOllamaCredentials($credentials));
                break;

            default:
                $errors = array_merge($errors, $this->validateGenericCredentials($credentials));
        }

        return $errors;
    }

    /**
     * Validate OpenAI credentials
     */
    protected function validateOpenAICredentials(array $credentials): array
    {
        $errors = [];

        // API Key validation
        if (empty($credentials['api_key'])) {
            $errors[] = 'OpenAI API key is required.';
        } elseif (! is_string($credentials['api_key'])) {
            $errors[] = 'OpenAI API key must be a string.';
        } elseif (! str_starts_with($credentials['api_key'], 'sk-')) {
            $errors[] = 'OpenAI API key must start with "sk-".';
        } elseif (strlen($credentials['api_key']) < 20) {
            $errors[] = 'OpenAI API key appears to be too short.';
        }

        // Organization ID validation (optional)
        if (isset($credentials['organization']) && ! empty($credentials['organization'])) {
            if (! is_string($credentials['organization'])) {
                $errors[] = 'Organization ID must be a string.';
            } elseif (! preg_match('/^org-[a-zA-Z0-9]+$/', $credentials['organization'])) {
                $errors[] = 'Organization ID must start with "org-" followed by alphanumeric characters.';
            }
        }

        return $errors;
    }

    /**
     * Validate Anthropic credentials
     */
    protected function validateAnthropicCredentials(array $credentials): array
    {
        $errors = [];

        // API Key validation
        if (empty($credentials['api_key'])) {
            $errors[] = 'Anthropic API key is required.';
        } elseif (! is_string($credentials['api_key'])) {
            $errors[] = 'Anthropic API key must be a string.';
        } elseif (! str_starts_with($credentials['api_key'], 'sk-ant-')) {
            $errors[] = 'Anthropic API key must start with "sk-ant-".';
        } elseif (strlen($credentials['api_key']) < 20) {
            $errors[] = 'Anthropic API key appears to be too short.';
        }

        return $errors;
    }

    /**
     * Validate OpenRouter credentials
     */
    protected function validateOpenRouterCredentials(array $credentials): array
    {
        $errors = [];

        // API Key validation
        if (empty($credentials['api_key'])) {
            $errors[] = 'OpenRouter API key is required.';
        } elseif (! is_string($credentials['api_key'])) {
            $errors[] = 'OpenRouter API key must be a string.';
        } elseif (! str_starts_with($credentials['api_key'], 'sk-or-')) {
            $errors[] = 'OpenRouter API key must start with "sk-or-".';
        } elseif (strlen($credentials['api_key']) < 20) {
            $errors[] = 'OpenRouter API key appears to be too short.';
        }

        return $errors;
    }

    /**
     * Validate Ollama credentials
     */
    protected function validateOllamaCredentials(array $credentials): array
    {
        $errors = [];

        // Base URL validation
        if (empty($credentials['base_url'])) {
            $errors[] = 'Ollama base URL is required.';
        } elseif (! is_string($credentials['base_url'])) {
            $errors[] = 'Ollama base URL must be a string.';
        } elseif (! filter_var($credentials['base_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Ollama base URL must be a valid URL.';
        } elseif (! preg_match('/^https?:\/\//', $credentials['base_url'])) {
            $errors[] = 'Ollama base URL must start with http:// or https://.';
        }

        // Optional API key validation
        if (isset($credentials['api_key']) && ! empty($credentials['api_key'])) {
            if (! is_string($credentials['api_key'])) {
                $errors[] = 'API key must be a string.';
            }
        }

        return $errors;
    }

    /**
     * Validate generic credentials (fallback)
     */
    protected function validateGenericCredentials(array $credentials): array
    {
        $errors = [];

        // Must have either api_key or base_url
        if (empty($credentials['api_key']) && empty($credentials['base_url'])) {
            $errors[] = 'Either API key or base URL is required.';
        }

        // Validate API key if provided
        if (isset($credentials['api_key']) && ! empty($credentials['api_key'])) {
            if (! is_string($credentials['api_key'])) {
                $errors[] = 'API key must be a string.';
            } elseif (strlen($credentials['api_key']) < 10) {
                $errors[] = 'API key appears to be too short.';
            }
        }

        // Validate base URL if provided
        if (isset($credentials['base_url']) && ! empty($credentials['base_url'])) {
            if (! is_string($credentials['base_url'])) {
                $errors[] = 'Base URL must be a string.';
            } elseif (! filter_var($credentials['base_url'], FILTER_VALIDATE_URL)) {
                $errors[] = 'Base URL must be a valid URL.';
            }
        }

        return $errors;
    }
}
