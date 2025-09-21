<?php

namespace App\Console\Commands\AI;

use App\Models\AICredential;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CredentialsSet extends Command
{
    protected $signature = 'ai:credentials:set
                            {provider? : Provider name (openai, anthropic, ollama, openrouter)}
                            {--type=api_key : Credential type}
                            {--key= : API key (for non-interactive mode)}';

    protected $description = 'Set AI provider credentials securely';

    public function handle(): int
    {
        $provider = $this->argument('provider');
        $type = $this->option('type');
        $key = $this->option('key');

        // Get available providers from config
        $availableProviders = array_keys(config('fragments.models.providers', []));

        if (! $provider) {
            $provider = select(
                'Which AI provider would you like to configure?',
                $availableProviders
            );
        }

        if (! in_array($provider, $availableProviders)) {
            $this->error("Unknown provider: {$provider}");
            $this->info('Available providers: '.implode(', ', $availableProviders));

            return self::FAILURE;
        }

        $providerConfig = config("fragments.models.providers.{$provider}");
        $configKeys = $providerConfig['config_keys'] ?? [];

        if (empty($configKeys)) {
            $this->error("No configuration requirements found for provider: {$provider}");

            return self::FAILURE;
        }

        $credentials = [];

        // Handle different credential types
        if ($type === 'api_key') {
            $credentials = $this->collectApiKeyCredentials($provider, $configKeys, $key);
        } else {
            $this->error("Unsupported credential type: {$type}");

            return self::FAILURE;
        }

        if (empty($credentials)) {
            $this->error('No credentials provided');

            return self::FAILURE;
        }

        // Check if credentials already exist
        $existing = AICredential::getActiveCredential($provider, $type);
        if ($existing) {
            if (! confirm("Credentials for {$provider} already exist. Overwrite?")) {
                $this->info('Operation cancelled');

                return self::SUCCESS;
            }
        }

        // Store the credentials
        try {
            AICredential::storeCredentials(
                $provider,
                $credentials,
                $type,
                [
                    'created_by' => 'artisan',
                    'created_at' => now()->toISOString(),
                ]
            );

            $this->info("✅ Credentials for {$provider} stored successfully");

            // Test the credentials
            if (confirm('Would you like to test the credentials now?')) {
                $this->call('ai:health', ['provider' => $provider]);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to store credentials: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function collectApiKeyCredentials(string $provider, array $configKeys, ?string $providedKey): array
    {
        $credentials = [];

        foreach ($configKeys as $configKey) {
            $displayName = $this->formatConfigKeyName($configKey);

            if ($configKey === 'OPENAI_API_KEY' || $configKey === 'ANTHROPIC_API_KEY' ||
                $configKey === 'OPENROUTER_API_KEY') {

                if ($providedKey) {
                    $value = $providedKey;
                } else {
                    $value = password("Enter {$displayName}");
                }

                if (empty($value)) {
                    $this->error("API key is required for {$provider}");

                    continue;
                }

                $credentials['key'] = $value;
            } elseif ($configKey === 'OLLAMA_BASE_URL') {
                $default = 'http://127.0.0.1:11434';
                $value = text("Enter {$displayName}", default: $default);
                $credentials['base'] = $value;
            } else {
                $value = text("Enter {$displayName}");
                if (! empty($value)) {
                    $key = strtolower(str_replace(['_URL', '_KEY'], ['', ''], $configKey));
                    $credentials[$key] = $value;
                }
            }
        }

        return $credentials;
    }

    protected function formatConfigKeyName(string $configKey): string
    {
        return ucwords(str_replace('_', ' ', strtolower($configKey)));
    }
}
