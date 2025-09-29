<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'name',
        'fragment_ids',
        'last_viewed_at',
        'vault_id',
        'project_id',
    ];

    protected $casts = [
        'fragment_ids' => 'array',
        'last_viewed_at' => 'datetime',
        'vault_id' => 'integer',
        'project_id' => 'integer',
    ];

    public function fragments()
    {
        return Fragment::whereIn('id', $this->fragment_ids ?? []);
    }

    public function getFirstFragmentAttribute()
    {
        $fragmentIds = $this->fragment_ids ?? [];
        if (empty($fragmentIds)) {
            return null;
        }

        return Fragment::find($fragmentIds[0]);
    }

    public function updateLastViewed(): void
    {
        $this->update(['last_viewed_at' => now()]);
    }

    public function vault()
    {
        return $this->belongsTo(Vault::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeForVault($query, $vaultId)
    {
        return $query->where('vault_id', $vaultId);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForVaultAndProject($query, $vaultId, $projectId = null)
    {
        $query = $query->where('vault_id', $vaultId);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query;
    }
}
