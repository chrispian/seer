/**
 * Keychain Manager Interface and Types
 * 
 * Provides TypeScript interfaces and base classes for credential storage
 * across different storage backends (database, browser keychain, native keychain).
 */

export interface CredentialMetadata {
    id: string;
    provider: string;
    type: string;
    created_at: string;
    expires_at?: string;
    is_expired: boolean;
    storage_backend: string;
    metadata?: Record<string, any>;
}

export interface StorageCapabilities {
    encryption: string;
    soft_delete: boolean;
    metadata: boolean;
    expiration: boolean;
    audit_trail: boolean;
    multi_tenant: boolean;
    hardware_backed: boolean;
    user_controlled: boolean;
    cross_device_sync: boolean;
    biometric_auth: boolean;
}

export interface StorageHealthStatus {
    status: 'healthy' | 'degraded' | 'unhealthy';
    available: boolean;
    storage_type: string;
    statistics?: {
        total_credentials: number;
        active_credentials: number;
        expired_credentials: number;
    };
    capabilities: StorageCapabilities;
    error?: string;
    last_checked: string;
}

export interface CredentialStorageOptions {
    type?: string;
    metadata?: Record<string, any>;
    expires_at?: Date;
    created_via?: string;
}

/**
 * Base error class for keychain operations
 */
export class KeychainError extends Error {
    constructor(
        message: string,
        public code: string,
        public operation: string,
        public recoverable: boolean = true
    ) {
        super(message);
        this.name = 'KeychainError';
    }
}

/**
 * User denied access to keychain
 */
export class KeychainAccessDeniedError extends KeychainError {
    constructor(operation: string) {
        super(
            'User denied access to keychain storage',
            'ACCESS_DENIED',
            operation,
            true
        );
    }
}

/**
 * Keychain feature not supported by browser
 */
export class KeychainNotSupportedError extends KeychainError {
    constructor(operation: string, feature?: string) {
        super(
            `Keychain feature not supported${feature ? `: ${feature}` : ''}`,
            'NOT_SUPPORTED',
            operation,
            false
        );
    }
}

/**
 * Biometric authentication failed
 */
export class BiometricAuthFailedError extends KeychainError {
    constructor(operation: string) {
        super(
            'Biometric authentication failed or unavailable',
            'BIOMETRIC_FAILED',
            operation,
            true
        );
    }
}

/**
 * Abstract base interface for credential storage implementations
 */
export interface CredentialStorageInterface {
    /**
     * Store credentials for a provider
     */
    store(provider: string, credentials: Record<string, any>, options?: CredentialStorageOptions): Promise<string>;

    /**
     * Retrieve credentials by credential ID
     */
    retrieve(credentialId: string): Promise<Record<string, any> | null>;

    /**
     * Update existing credentials
     */
    update(credentialId: string, credentials: Record<string, any>): Promise<boolean>;

    /**
     * Delete credentials
     */
    delete(credentialId: string): Promise<boolean>;

    /**
     * List credentials with optional provider filtering
     */
    list(provider?: string): Promise<CredentialMetadata[]>;

    /**
     * Check if storage backend is available
     */
    isAvailable(): Promise<boolean>;

    /**
     * Get storage backend type identifier
     */
    getStorageType(): string;

    /**
     * Get storage backend capabilities
     */
    getCapabilities(): Promise<StorageCapabilities>;

    /**
     * Get credential metadata without decrypting credentials
     */
    getMetadata(credentialId: string): Promise<CredentialMetadata | null>;

    /**
     * Check if credential exists
     */
    exists(credentialId: string): Promise<boolean>;

    /**
     * Get storage health status
     */
    getHealthStatus(): Promise<StorageHealthStatus>;
}

/**
 * Abstract base class for credential storage implementations
 */
export abstract class BaseCredentialStorage implements CredentialStorageInterface {
    abstract store(provider: string, credentials: Record<string, any>, options?: CredentialStorageOptions): Promise<string>;
    abstract retrieve(credentialId: string): Promise<Record<string, any> | null>;
    abstract update(credentialId: string, credentials: Record<string, any>): Promise<boolean>;
    abstract delete(credentialId: string): Promise<boolean>;
    abstract list(provider?: string): Promise<CredentialMetadata[]>;
    abstract isAvailable(): Promise<boolean>;
    abstract getStorageType(): string;
    abstract getCapabilities(): Promise<StorageCapabilities>;
    abstract getMetadata(credentialId: string): Promise<CredentialMetadata | null>;
    abstract exists(credentialId: string): Promise<boolean>;
    abstract getHealthStatus(): Promise<StorageHealthStatus>;

    /**
     * Validate provider name
     */
    protected validateProvider(provider: string): void {
        if (!provider || typeof provider !== 'string') {
            throw new KeychainError('Invalid provider name', 'INVALID_PROVIDER', 'validate');
        }
    }

    /**
     * Validate credentials object
     */
    protected validateCredentials(credentials: Record<string, any>): void {
        if (!credentials || typeof credentials !== 'object') {
            throw new KeychainError('Invalid credentials object', 'INVALID_CREDENTIALS', 'validate');
        }
    }

    /**
     * Validate credential ID
     */
    protected validateCredentialId(credentialId: string): void {
        if (!credentialId || typeof credentialId !== 'string') {
            throw new KeychainError('Invalid credential ID', 'INVALID_CREDENTIAL_ID', 'validate');
        }
    }

    /**
     * Generate unique credential ID
     */
    protected generateCredentialId(): string {
        return `cred_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Get current timestamp as ISO string
     */
    protected getCurrentTimestamp(): string {
        return new Date().toISOString();
    }
}

/**
 * Storage type enumeration
 */
export enum StorageType {
    DATABASE = 'database',
    BROWSER_KEYCHAIN = 'browser_keychain',
    NATIVE_KEYCHAIN = 'native_keychain',
    HYBRID = 'hybrid',
}

/**
 * Storage selection criteria
 */
export interface StorageSelectionCriteria {
    preferHardwareBacked?: boolean;
    requireBiometric?: boolean;
    allowFallback?: boolean;
    userPreference?: StorageType;
}

/**
 * Keychain manager for storage backend selection and management
 */
export class KeychainManager {
    private storageBackends: Map<StorageType, CredentialStorageInterface> = new Map();
    private defaultStorage?: CredentialStorageInterface;

    /**
     * Register a storage backend
     */
    registerStorage(type: StorageType, storage: CredentialStorageInterface): void {
        this.storageBackends.set(type, storage);
    }

    /**
     * Set default storage backend
     */
    setDefaultStorage(storage: CredentialStorageInterface): void {
        this.defaultStorage = storage;
    }

    /**
     * Get storage backend by type
     */
    getStorage(type?: StorageType): CredentialStorageInterface | undefined {
        if (type) {
            return this.storageBackends.get(type);
        }
        return this.defaultStorage;
    }

    /**
     * Get best available storage based on criteria
     */
    async getBestStorage(criteria: StorageSelectionCriteria = {}): Promise<CredentialStorageInterface | null> {
        const availableStorages: Array<{type: StorageType, storage: CredentialStorageInterface, score: number}> = [];

        for (const [type, storage] of this.storageBackends) {
            if (await storage.isAvailable()) {
                const capabilities = await storage.getCapabilities();
                let score = 0;

                // Score based on criteria
                if (criteria.preferHardwareBacked && capabilities.hardware_backed) score += 10;
                if (criteria.requireBiometric && capabilities.biometric_auth) score += 10;
                if (criteria.userPreference === type) score += 5;

                // Base scores for different storage types
                switch (type) {
                    case StorageType.NATIVE_KEYCHAIN:
                        score += 8;
                        break;
                    case StorageType.BROWSER_KEYCHAIN:
                        score += 6;
                        break;
                    case StorageType.DATABASE:
                        score += 4;
                        break;
                    case StorageType.HYBRID:
                        score += 7;
                        break;
                }

                availableStorages.push({ type, storage, score });
            }
        }

        if (availableStorages.length === 0) {
            if (criteria.allowFallback && this.defaultStorage) {
                return this.defaultStorage;
            }
            return null;
        }

        // Sort by score (highest first) and return the best
        availableStorages.sort((a, b) => b.score - a.score);
        return availableStorages[0].storage;
    }

    /**
     * Get all available storage types
     */
    async getAvailableStorageTypes(): Promise<StorageType[]> {
        const available: StorageType[] = [];

        for (const [type, storage] of this.storageBackends) {
            if (await storage.isAvailable()) {
                available.push(type);
            }
        }

        return available;
    }

    /**
     * Get comprehensive storage status report
     */
    async getStorageReport(): Promise<Record<string, StorageHealthStatus>> {
        const report: Record<string, StorageHealthStatus> = {};

        for (const [type, storage] of this.storageBackends) {
            try {
                report[type] = await storage.getHealthStatus();
            } catch (error) {
                report[type] = {
                    status: 'unhealthy',
                    available: false,
                    storage_type: type,
                    capabilities: await storage.getCapabilities().catch(() => ({} as StorageCapabilities)),
                    error: error instanceof Error ? error.message : 'Unknown error',
                    last_checked: new Date().toISOString(),
                };
            }
        }

        return report;
    }
}