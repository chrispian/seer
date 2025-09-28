<?php

namespace App\Actions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ConfigureProviderClient
{
    public function __invoke(array $providerConfig, string $model, array $messages): Response
    {
        $baseUrl = $providerConfig['base_url'];
        $provider = $providerConfig['provider'];

        return match ($provider) {
            'ollama' => $this->configureOllamaClient($baseUrl, $model, $messages),
            default => throw new \InvalidArgumentException("Provider client configuration not implemented for: {$provider}"),
        };
    }

    private function configureOllamaClient(string $baseUrl, string $model, array $messages): Response
    {
        return Http::withOptions(['stream' => true, 'timeout' => 0])
            ->post(rtrim($baseUrl, '/').'/api/chat', [
                'model' => $model,
                'messages' => $messages,
                'stream' => true,
            ]);
    }

    // Future provider configurations can be added here
    // private function configureOpenAIClient(string $baseUrl, string $model, array $messages): Response
    // {
    //     return Http::withHeaders([
    //         'Authorization' => 'Bearer ' . config('prism.providers.openai.api_key'),
    //         'Content-Type' => 'application/json',
    //     ])->withOptions(['stream' => true, 'timeout' => 0])
    //         ->post(rtrim($baseUrl, '/') . '/v1/chat/completions', [
    //             'model' => $model,
    //             'messages' => $messages,
    //             'stream' => true,
    //         ]);
    // }
}
