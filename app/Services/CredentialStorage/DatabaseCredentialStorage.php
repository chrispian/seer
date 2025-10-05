<?php

namespace App\Services\CredentialStorage;

use App\Contracts\CredentialStorageInterface;
use App\Models\AICredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseCredentialStorage implements CredentialStorageInterface
{
    public function store(string $provider, array $credentials, array $options = []): string
    {
        $type = $options['type'] ?? 'api_key';
        $metadata = $options['metadata'] ?? [];
        $expiresAt = $options['expires_at'] ?? null;

        // Add storage backend metadata
        $metadata = array_merge($metadata, [
            'storage_backend' => $this->getStorageType(),
            'created_via' => $options['created_via'] ?? 'database_storage',
        ]);

        $credential = AICredential::storeCredentials(
            $provider,
            $credentials,
            $type,
            $metadata,
            $expiresAt
        );

        Log::info('Credential stored via database storage', [
            'provider' => $provider,
            'type' => $type,
            'credential_id' => $credential->id,
        ]);

        return (string) $credential->id;
    }

    public function retrieve(string $credentialId): ?array
    {
        $credential = AICredential::find($credentialId);

        if (! $credential || ! $credential->is_active) {
            return null;
        }

        if ($credential->isExpired()) {
            Log::warning('Attempted to retrieve expired credential', [
                'credential_id' => $credentialId,
                'expires_at' => $credential->expires_at,
            ]);

            return null;
        }

        return $credential->getCredentials();
    }

    public function update(string $credentialId, array $credentials): bool
    {
        $credential = AICredential::find($credentialId);

        if (! $credential) {
            return false;
        }

        try {
            $credential->setCredentials($credentials);

            // Update metadata to track modification
            $metadata = $credential->metadata ?? [];
            $metadata['last_modified'] = now()->toISOString();
            $metadata['modified_via'] = 'database_storage';
            $credential->metadata = $metadata;

            $credential->save();

            Log::info('Credential updated via database storage', [
                'credential_id' => $credentialId,
                'provider' => $credential->provider,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update credential', [
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function delete(string $credentialId): bool
    {
        $credential = AICredential::find($credentialId);

        if (! $credential) {
            return false;
        }

        try {
            // Soft delete by marking as inactive
            $credential->is_active = false;

            // Update metadata to track deletion
            $metadata = $credential->metadata ?? [];
            $metadata['deleted_at'] = now()->toISOString();
            $metadata['deleted_via'] = 'database_storage';
            $credential->metadata = $metadata;

            $credential->save();

            Log::info('Credential soft deleted via database storage', [
                'credential_id' => $credentialId,
                'provider' => $credential->provider,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete credential', [
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function list(?string $provider = null): array
    {
        $query = AICredential::where('is_active', true);

        if ($provider) {
            $query->where('provider', $provider);
        }

        $credentials = $query->orderBy('provider')
            ->orderBy('credential_type')
            ->get();

        return $credentials->map(function (AICredential $credential) {
            return [
                'id' => (string) $credential->id,
                'provider' => $credential->provider,
                'type' => $credential->credential_type,
                'metadata' => $credential->metadata,
                'expires_at' => $credential->expires_at?->toISOString(),
                'created_at' => $credential->created_at->toISOString(),
                'storage_backend' => $this->getStorageType(),
                'is_expired' => $credential->isExpired(),
            ];
        })->toArray();
    }

    public function isAvailable(): bool
    {
        try {
            // Check database connectivity
            DB::connection()->getPdo();

            // Check if ai_credentials table exists
            $hasTable = DB::getSchemaBuilder()->hasTable('ai_credentials');

            return $hasTable;
        } catch (\Exception $e) {
            Log::warning('Database credential storage unavailable', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getStorageType(): string
    {
        return 'database';
    }

    public function getCapabilities(): array
    {
        return [
            'encryption' => 'laravel_crypt',
            'soft_delete' => true,
            'metadata' => true,
            'expiration' => true,
            'audit_trail' => true,
            'multi_tenant' => false,
            'hardware_backed' => false,
            'user_controlled' => false,
            'cross_device_sync' => false,
            'biometric_auth' => false,
        ];
    }

    public function getMetadata(string $credentialId): ?array
    {
        $credential = AICredential::find($credentialId);

        if (! $credential) {
            return null;
        }

        return [
            'id' => (string) $credential->id,
            'provider' => $credential->provider,
            'type' => $credential->credential_type,
            'metadata' => $credential->metadata,
            'expires_at' => $credential->expires_at?->toISOString(),
            'created_at' => $credential->created_at->toISOString(),
            'updated_at' => $credential->updated_at->toISOString(),
            'is_active' => $credential->is_active,
            'is_expired' => $credential->isExpired(),
            'storage_backend' => $this->getStorageType(),
        ];
    }

    public function exists(string $credentialId): bool
    {
        return AICredential::where('id', $credentialId)
            ->where('is_active', true)
            ->exists();
    }

    public function getHealthStatus(): array
    {
        try {
            $totalCredentials = AICredential::count();
            $activeCredentials = AICredential::where('is_active', true)->count();
            $expiredCredentials = AICredential::where('is_active', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count();

            return [
                'status' => 'healthy',
                'available' => $this->isAvailable(),
                'storage_type' => $this->getStorageType(),
                'statistics' => [
                    'total_credentials' => $totalCredentials,
                    'active_credentials' => $activeCredentials,
                    'expired_credentials' => $expiredCredentials,
                ],
                'capabilities' => $this->getCapabilities(),
                'last_checked' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'available' => false,
                'storage_type' => $this->getStorageType(),
                'error' => $e->getMessage(),
                'last_checked' => now()->toISOString(),
            ];
        }
    }
}
