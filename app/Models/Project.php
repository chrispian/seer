<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'vault_id',
        'name',
        'description',
        'is_default',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'vault_id' => 'integer',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function fragments(): HasMany
    {
        return $this->hasMany(Fragment::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForVault($query, $vaultId)
    {
        return $query->where('vault_id', $vaultId);
    }

    public static function getDefaultForVault($vaultId): ?self
    {
        return static::forVault($vaultId)->default()->first()
            ?? static::forVault($vaultId)->first();
    }
}
