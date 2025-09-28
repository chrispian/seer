<?php

namespace App\Actions;

class ValidateStreamingProvider
{
    private array $supportedProviders = [
        'ollama' => [
            'streaming' => true,
            'config_key' => 'prism.providers.ollama.url',
            'default_url' => 'http://localhost:11434',
        ],
        // Future providers can be added here
        // 'openai' => [
        //     'streaming' => false, // For now
        //     'config_key' => 'prism.providers.openai.url',
        //     'default_url' => 'https://api.openai.com',
        // ],
    ];

    public function __invoke(string $provider): array
    {
        if (! isset($this->supportedProviders[$provider])) {
            abort(400, "Provider '{$provider}' is not supported");
        }

        $providerConfig = $this->supportedProviders[$provider];

        if (! $providerConfig['streaming']) {
            abort(400, "Provider '{$provider}' does not support streaming");
        }

        return [
            'provider' => $provider,
            'base_url' => config($providerConfig['config_key'], $providerConfig['default_url']),
            'config' => $providerConfig,
        ];
    }
}
