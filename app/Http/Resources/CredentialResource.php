<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CredentialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'credential_type' => $this->credential_type,
            'is_active' => $this->is_active,

            // Masked credential info (never expose raw credentials)
            'credential_info' => $this->getMaskedCredentialInfo(),

            // Status information
            'status' => [
                'is_expired' => $this->isExpired(),
                'expires_at' => $this->expires_at?->toISOString(),
                'last_used_at' => $this->last_used_at?->toISOString(),
            ],

            // Usage statistics
            'stats' => [
                'usage_count' => $this->usage_count,
                'total_cost' => (float) $this->total_cost,
            ],

            // Metadata (safe to expose)
            'metadata' => $this->metadata ?? [],
            'ui_metadata' => $this->getUIMetadata(),

            // Provider relationship
            'provider_config_id' => $this->provider_config_id,
            'provider_enabled' => $this->isProviderEnabled(),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Get masked credential information (never expose raw credentials)
     */
    protected function getMaskedCredentialInfo(): array
    {
        $credentials = $this->getCredentials();
        $masked = [];

        foreach ($credentials as $key => $value) {
            switch ($key) {
                case 'api_key':
                    if (is_string($value) && strlen($value) > 8) {
                        $masked[$key] = substr($value, 0, 8).str_repeat('*', strlen($value) - 8);
                    } else {
                        $masked[$key] = '***';
                    }
                    break;

                case 'organization':
                case 'organization_id':
                    $masked[$key] = $value; // Organization IDs are generally safe to expose
                    break;

                case 'base_url':
                case 'endpoint':
                    $masked[$key] = $value; // URLs are generally safe to expose
                    break;

                default:
                    // For any other sensitive data, mask it
                    if (is_string($value) && strlen($value) > 4) {
                        $masked[$key] = substr($value, 0, 4).str_repeat('*', max(0, strlen($value) - 4));
                    } else {
                        $masked[$key] = '***';
                    }
            }
        }

        return $masked;
    }
}
