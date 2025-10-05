<?php

namespace App\Contracts;

interface CredentialStorageInterface
{
    /**
     * Store credentials for a provider
     */
    public function store(string $provider, array $credentials, array $options = []): string;

    /**
     * Retrieve credentials by credential ID
     */
    public function retrieve(string $credentialId): ?array;

    /**
     * Update existing credentials
     */
    public function update(string $credentialId, array $credentials): bool;

    /**
     * Delete credentials (supports soft delete)
     */
    public function delete(string $credentialId): bool;

    /**
     * List credentials with optional provider filtering
     */
    public function list(?string $provider = null): array;

    /**
     * Check if storage backend is available
     */
    public function isAvailable(): bool;

    /**
     * Get storage backend type identifier
     */
    public function getStorageType(): string;

    /**
     * Get storage backend capabilities
     */
    public function getCapabilities(): array;

    /**
     * Get credential metadata without decrypting credentials
     */
    public function getMetadata(string $credentialId): ?array;

    /**
     * Check if credential exists
     */
    public function exists(string $credentialId): bool;

    /**
     * Get storage health status
     */
    public function getHealthStatus(): array;
}
