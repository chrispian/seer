<?php

namespace App\Actions;

class ExtractTokenUsage
{
    public function __invoke(string $provider, ?array $response): array
    {
        if (! $response) {
            return ['input' => null, 'output' => null];
        }

        return match ($provider) {
            'openai' => [
                'input' => $response['usage']['prompt_tokens'] ?? null,
                'output' => $response['usage']['completion_tokens'] ?? null,
            ],
            'anthropic' => [
                'input' => $response['usage']['input_tokens'] ?? null,
                'output' => $response['usage']['output_tokens'] ?? null,
            ],
            'ollama' => [
                'input' => $response['prompt_eval_count'] ?? null,
                'output' => $response['eval_count'] ?? null,
            ],
            'openrouter' => [
                // OpenRouter typically uses OpenAI format
                'input' => $response['usage']['prompt_tokens'] ?? null,
                'output' => $response['usage']['completion_tokens'] ?? null,
            ],
            default => ['input' => null, 'output' => null],
        };
    }
}
