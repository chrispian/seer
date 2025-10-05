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
        $timeout = $config['timeout'] ?? 30; // Default 30 second timeout

        if (! $prompt) {
            throw new \InvalidArgumentException('AI generate step requires a prompt');
        }

        if ($dryRun) {
            return $expect === 'json' ? ['dry_run' => true] : 'AI generated content (dry run)';
        }

        // Simple cache key based on prompt hash
        $cacheKey = 'ai_response_'.md5($prompt);
        $cacheEnabled = $config['cache'] ?? false;

        if ($cacheEnabled && cache()->has($cacheKey)) {
            \Log::info('AI Generate Step Cache Hit', ['cache_key' => $cacheKey]);
            $result = cache()->get($cacheKey);
        } else {
            $startTime = microtime(true);

            try {
                // Use the AI provider to generate content with timeout
                $response = $this->aiProvider->generateText($prompt, [], [
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.3,
                    'timeout' => $timeout,
                ]);

                $duration = round((microtime(true) - $startTime) * 1000, 2);
                \Log::info('AI Generate Step Performance', [
                    'duration_ms' => $duration,
                    'tokens' => $maxTokens,
                    'prompt_length' => strlen($prompt),
                ]);

                $result = $response['content'] ?? $response['text'] ?? $response ?? '';

                // Cache successful responses if enabled
                if ($cacheEnabled && ! empty($result)) {
                    cache()->put($cacheKey, $result, now()->addMinutes(60));
                }

            } catch (\Exception $e) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                \Log::error('AI Generate Step Failed', [
                    'duration_ms' => $duration,
                    'error' => $e->getMessage(),
                    'prompt_length' => strlen($prompt),
                ]);

                // Return fallback for non-critical failures
                if ($config['fallback'] ?? false) {
                    $result = $config['fallback_text'] ?? 'AI processing unavailable';
                } else {
                    throw new \RuntimeException("AI generation failed: {$e->getMessage()}");
                }
            }
        }

        // Handle expected output type
        if ($expect === 'json') {
            // Try to extract JSON from the response
            $jsonResult = $this->extractJson($result);

            try {
                return json_decode($jsonResult, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                \Log::error('AI Generate JSON Parse Error', [
                    'raw_result' => substr($result, 0, 200),
                    'extracted_json' => substr($jsonResult, 0, 200),
                    'error' => $e->getMessage(),
                ]);
                throw new \InvalidArgumentException("AI generate step expected JSON but got invalid JSON: {$e->getMessage()}. Raw response: ".substr($result, 0, 200));
            }
        }

        return $result;
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
