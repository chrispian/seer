<?php

namespace App\Services;

use App\Contracts\CredentialStorageInterface;
use App\Services\CredentialStorage\DatabaseCredentialStorage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CredentialStorageManager
{
    protected array $storageBackends = [];

    protected array $registeredBackends = [];

    public function __construct()
    {
        $this->registerDefaultBackends();
    }

    /**
     * Register default storage backends
     */
    protected function registerDefaultBackends(): void
    {
        $this->registerBackend('database', DatabaseCredentialStorage::class);

        // Future backends will be registered here:
        // $this->registerBackend('browser_keychain', BrowserKeychainStorage::class);
        // $this->registerBackend('native_keychain', NativeKeychainStorage::class);
    }

    /**
     * Register a storage backend
     */
    public function registerBackend(string $type, string $className): void
    {
        $this->registeredBackends[$type] = $className;
    }

    /**
     * Get storage backend instance
     */
    public function getStorage(?string $type = null): CredentialStorageInterface
    {
        $type = $type ?? $this->getDefaultStorageType();

        if (! isset($this->storageBackends[$type])) {
            $this->storageBackends[$type] = $this->createStorageBackend($type);
        }

        return $this->storageBackends[$type];
    }

    /**
     * Create storage backend instance
     */
    protected function createStorageBackend(string $type): CredentialStorageInterface
    {
        if (! isset($this->registeredBackends[$type])) {
            throw new \InvalidArgumentException("Unknown storage backend type: {$type}");
        }

        $className = $this->registeredBackends[$type];
        $storage = new $className;

        if (! $storage instanceof CredentialStorageInterface) {
            throw new \InvalidArgumentException('Storage backend must implement CredentialStorageInterface');
        }

        return $storage;
    }

    /**
     * Get default storage type from configuration
     */
    public function getDefaultStorageType(): string
    {
        return Config::get('fragments.credential_storage.default', 'database');
    }

    /**
     * Get available storage types
     */
    public function getAvailableStorageTypes(): array
    {
        $types = [];

        foreach ($this->registeredBackends as $type => $className) {
            try {
                $storage = $this->getStorage($type);
                if ($storage->isAvailable()) {
                    $types[] = [
                        'type' => $type,
                        'name' => ucfirst(str_replace('_', ' ', $type)),
                        'capabilities' => $storage->getCapabilities(),
                        'health' => $storage->getHealthStatus(),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Storage backend {$type} is not available", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $types;
    }

    /**
     * Get the best available storage type
     */
    public function getBestAvailableStorageType(): string
    {
        $preferenceOrder = Config::get('fragments.credential_storage.preference_order', [
            'native_keychain',
            'browser_keychain',
            'database',
        ]);

        foreach ($preferenceOrder as $type) {
            if (isset($this->registeredBackends[$type])) {
                try {
                    $storage = $this->getStorage($type);
                    if ($storage->isAvailable()) {
                        return $type;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Fallback to database
        return 'database';
    }

    /**
     * Migrate credentials between storage backends
     */
    public function migrate(string $fromType, string $toType, array $options = []): array
    {
        $fromStorage = $this->getStorage($fromType);
        $toStorage = $this->getStorage($toType);

        if (! $fromStorage->isAvailable()) {
            throw new \RuntimeException("Source storage backend '{$fromType}' is not available");
        }

        if (! $toStorage->isAvailable()) {
            throw new \RuntimeException("Target storage backend '{$toType}' is not available");
        }

        $provider = $options['provider'] ?? null;
        $dryRun = $options['dry_run'] ?? false;

        $credentials = $fromStorage->list($provider);
        $results = [
            'total' => count($credentials),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($credentials as $credentialMeta) {
            try {
                $credentialData = $fromStorage->retrieve($credentialMeta['id']);

                if (! $credentialData) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to retrieve credential {$credentialMeta['id']}";

                    continue;
                }

                if (! $dryRun) {
                    // Store in new backend
                    $newId = $toStorage->store(
                        $credentialMeta['provider'],
                        $credentialData,
                        [
                            'type' => $credentialMeta['type'],
                            'metadata' => array_merge($credentialMeta['metadata'] ?? [], [
                                'migrated_from' => $fromType,
                                'migrated_at' => now()->toISOString(),
                                'original_id' => $credentialMeta['id'],
                            ]),
                            'expires_at' => $credentialMeta['expires_at'] ?
                                new \DateTime($credentialMeta['expires_at']) : null,
                        ]
                    );

                    Log::info('Credential migrated between storage backends', [
                        'from_type' => $fromType,
                        'to_type' => $toType,
                        'provider' => $credentialMeta['provider'],
                        'original_id' => $credentialMeta['id'],
                        'new_id' => $newId,
                    ]);
                }

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Failed to migrate credential {$credentialMeta['id']}: {$e->getMessage()}";

                Log::error('Credential migration failed', [
                    'from_type' => $fromType,
                    'to_type' => $toType,
                    'credential_id' => $credentialMeta['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get storage status for all registered backends
     */
    public function getStorageStatus(): array
    {
        $status = [];

        foreach ($this->registeredBackends as $type => $className) {
            try {
                $storage = $this->getStorage($type);
                $status[$type] = $storage->getHealthStatus();
            } catch (\Exception $e) {
                $status[$type] = [
                    'status' => 'error',
                    'available' => false,
                    'storage_type' => $type,
                    'error' => $e->getMessage(),
                    'last_checked' => now()->toISOString(),
                ];
            }
        }

        return $status;
    }

    /**
     * Validate credential storage configuration
     */
    public function validateConfiguration(): array
    {
        $issues = [];
        $defaultType = $this->getDefaultStorageType();

        // Check if default storage type is registered
        if (! isset($this->registeredBackends[$defaultType])) {
            $issues[] = "Default storage type '{$defaultType}' is not registered";
        }

        // Check if default storage type is available
        try {
            $defaultStorage = $this->getStorage($defaultType);
            if (! $defaultStorage->isAvailable()) {
                $issues[] = "Default storage type '{$defaultType}' is not available";
            }
        } catch (\Exception $e) {
            $issues[] = "Default storage type '{$defaultType}' failed to initialize: {$e->getMessage()}";
        }

        // Check for at least one available backend
        $availableBackends = $this->getAvailableStorageTypes();
        if (empty($availableBackends)) {
            $issues[] = 'No storage backends are available';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'default_type' => $defaultType,
            'available_backends' => count($availableBackends),
        ];
    }
}
