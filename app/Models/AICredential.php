<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AICredential extends Model
{
    protected $fillable = [
        'provider',
        'credential_type',
        'encrypted_credentials',
        'metadata',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Set credentials with encryption
     */
    public function setCredentials(array $credentials): void
    {
        $this->encrypted_credentials = Crypt::encrypt(json_encode($credentials));
    }

    /**
     * Get decrypted credentials
     */
    public function getCredentials(): array
    {
        try {
            $decrypted = Crypt::decrypt($this->encrypted_credentials);

            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if credentials are expired (for OAuth tokens)
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get active credential for a provider
     */
    public static function getActiveCredential(string $provider, string $type = 'api_key'): ?self
    {
        return static::where('provider', $provider)
            ->where('credential_type', $type)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Store or update credentials for a provider
     */
    public static function storeCredentials(
        string $provider,
        array $credentials,
        string $type = 'api_key',
        array $metadata = [],
        ?\DateTime $expiresAt = null
    ): self {
        // Encrypt the credentials first
        $encryptedCredentials = Crypt::encrypt(json_encode($credentials));
        
        $credential = static::updateOrCreate(
            [
                'provider' => $provider,
                'credential_type' => $type,
            ],
            [
                'encrypted_credentials' => $encryptedCredentials,
                'metadata' => $metadata,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        );

        return $credential;
    }
}
