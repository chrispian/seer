<?php

namespace App\Console\Commands\AI;

use App\Models\AICredential;
use App\Services\CredentialStorageManager;
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
                            {--key= : API key (for non-interactive mode)}
                            {--storage= : Storage backend to use (database, browser_keychain, native_keychain)}';

    protected $description = 'Set AI provider credentials securely';

    public function handle(CredentialStorageManager $storageManager): int
    {
        $provider = $this->argument('provider');
        $type = $this->option('type');
        $key = $this->option('key');
        $storageType = $this->option('storage');

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

        // Select storage backend
        $storage = $this->selectStorageBackend($storageManager, $storageType);
        if (! $storage) {
            return self::FAILURE;
        }

        // Store the credentials
        try {
            $credentialId = $storage->store(
                $provider,
                $credentials,
                [
                    'type' => $type,
                    'metadata' => [
                        'created_by' => 'artisan',
                        'created_at' => now()->toISOString(),
                    ],
                ]
            );

            $this->info("✅ Credentials for {$provider} stored successfully (ID: {$credentialId})");

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

    protected function selectStorageBackend(CredentialStorageManager $storageManager, ?string $requestedType)
    {
        // If specific storage type requested, validate and use it
        if ($requestedType) {
            try {
                $storage = $storageManager->getStorage($requestedType);
                if (! $storage->isAvailable()) {
                    $this->error("Storage backend '{$requestedType}' is not available");

                    return null;
                }

                $this->info("Using {$requestedType} storage backend");

                return $storage;
            } catch (\InvalidArgumentException $e) {
                $this->error("Unknown storage backend: {$requestedType}");

                $availableTypes = $storageManager->getAvailableStorageTypes();
                $typeNames = array_column($availableTypes, 'type');
                $this->info('Available backends: '.implode(', ', $typeNames));

                return null;
            }
        }

        // Use default storage backend
        try {
            $defaultType = $storageManager->getDefaultStorageType();
            $storage = $storageManager->getStorage($defaultType);

            if (! $storage->isAvailable()) {
                $this->warn("Default storage backend '{$defaultType}' is not available, finding alternative...");

                // Try to find the best available storage
                $availableTypes = $storageManager->getAvailableStorageTypes();
                if (empty($availableTypes)) {
                    $this->error('No storage backends are available');

                    return null;
                }

                $bestType = $availableTypes[0]['type'];
                $storage = $storageManager->getStorage($bestType);
                $this->info("Using {$bestType} storage backend");
            } else {
                $this->info("Using default {$defaultType} storage backend");
            }

            return $storage;
        } catch (\Exception $e) {
            $this->error("Failed to initialize storage backend: {$e->getMessage()}");

            return null;
        }
    }
}
