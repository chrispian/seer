<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaultRoutingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'match_type',
        'match_value',
        'conditions',
        'target_vault_id',
        'target_project_id',
        'scope_vault_id',
        'scope_project_id',
        'priority',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function targetVault()
    {
        return $this->belongsTo(Vault::class, 'target_vault_id');
    }

    public function targetProject()
    {
        return $this->belongsTo(Project::class, 'target_project_id');
    }

    public function contextVault()
    {
        return $this->belongsTo(Vault::class, 'scope_vault_id');
    }

    public function contextProject()
    {
        return $this->belongsTo(Project::class, 'scope_project_id');
    }
}
