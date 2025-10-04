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
            $response = $this->aiProvider->generateText($prompt, [], [
                'max_tokens' => $maxTokens,
                'temperature' => 0.3,
            ]);

            // Log the full response to debug the structure
            \Log::info('AI Provider Full Response', ['response' => $response]);

            $result = $response['content'] ?? $response['text'] ?? $response ?? '';

            // Handle expected output type
            if ($expect === 'json') {
                // Log the raw AI response for debugging
                \Log::info('AI Generate Step Raw Response', ['result' => $result]);
                
                // Try to extract JSON from the response
                $jsonResult = $this->extractJson($result);
                
                try {
                    return json_decode($jsonResult, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    \Log::error('AI Generate JSON Parse Error', [
                        'raw_result' => $result,
                        'extracted_json' => $jsonResult,
                        'error' => $e->getMessage()
                    ]);
                    throw new \InvalidArgumentException("AI generate step expected JSON but got invalid JSON: {$e->getMessage()}. Raw response: " . substr($result, 0, 200));
                }
            }

            return $result;

        } catch (\Exception $e) {
            throw new \RuntimeException("AI generation failed: {$e->getMessage()}");
        }
    }

    /**
     * Extract JSON from AI response that might contain extra text
     */
    private function extractJson(string $text): string
    {
        // Remove common AI response prefixes/suffixes
        $text = trim($text);
        
        // Look for JSON object patterns
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/', $text, $matches)) {
            return $matches[0];
        }
        
        // Look for JSON array patterns
        if (preg_match('/\[[^\[\]]*(?:\[[^\[\]]*\][^\[\]]*)*\]/', $text, $matches)) {
            return $matches[0];
        }
        
        // If no JSON pattern found, return original text
        return $text;
    }

    public function validate(array $config): bool
    {
        return isset($config['prompt']);
    }
}
