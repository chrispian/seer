<?php

namespace App\Services\AI;

class Embeddings
{
    protected AIProviderManager $providerManager;

    public function __construct(AIProviderManager $providerManager)
    {
        $this->providerManager = $providerManager;
    }

    public function embed(string $text, array $context = []): array
    {
        // Normalize whitespace & length (keep under ~8k tokens)
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $this->providerManager->generateEmbedding($text, $context);
    }

    /**
     * Legacy method for backward compatibility
     */
    public function embedLegacy(string $text, string $provider = 'openai'): array
    {
        return $this->embed($text, ['command_model_override' => $provider]);
    }
}
