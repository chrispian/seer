<?php

namespace App\Services\Commands\DSL\Steps;

use App\Services\AI\AIProviderManager;

class AiGenerateStep extends Step
{
    public function __construct(
        protected AIProviderManager $aiProvider
    ) {}

    public function getType(): string
    {
        return 'ai.generate';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        if (! config('fragments.ai.enabled', true)) {
            throw new \RuntimeException('AI generation is disabled');
        }

        $prompt = $config['prompt'] ?? '';
        $expect = $config['expect'] ?? 'text';
        $maxTokens = $config['max_tokens'] ?? 500;

        if (! $prompt) {
            throw new \InvalidArgumentException('AI generate step requires a prompt');
        }

        if ($dryRun) {
            return $expect === 'json' ? ['dry_run' => true] : 'AI generated content (dry run)';
        }

        try {
            // Use the AI provider to generate content
            $messages = [
                ['role' => 'user', 'content' => $prompt],
            ];

            $response = $this->aiProvider->chat($messages, [
                'max_tokens' => $maxTokens,
                'temperature' => 0.3,
            ]);

            $result = $response['choices'][0]['message']['content'] ?? '';

            // Handle expected output type
            if ($expect === 'json') {
                try {
                    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new \InvalidArgumentException("AI generate step expected JSON but got invalid JSON: {$e->getMessage()}");
                }
            }

            return $result;

        } catch (\Exception $e) {
            throw new \RuntimeException("AI generation failed: {$e->getMessage()}");
        }
    }

    public function validate(array $config): bool
    {
        return isset($config['prompt']);
    }
}
