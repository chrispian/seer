<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolDefinition extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'version',
        'source',
        'mcp_server',
        'summary',
        'selection_hint',
        'syntax',
        'args_schema',
        'examples',
        'weights',
        'permissions',
        'constraints',
        'metadata',
        'enabled',
        'overridden',
        'synced_at',
    ];

    protected $casts = [
        'args_schema' => 'array',
        'examples' => 'array',
        'weights' => 'array',
        'permissions' => 'array',
        'constraints' => 'array',
        'metadata' => 'array',
        'enabled' => 'boolean',
        'overridden' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeBuiltin($query)
    {
        return $query->where('source', 'builtin');
    }

    public function scopeMcp($query)
    {
        return $query->where('source', 'mcp');
    }

    public function scopeByServer($query, string $server)
    {
        return $query->where('mcp_server', $server);
    }

    public function toPromptFormat(): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'version' => $this->version,
            'summary' => $this->summary,
            'selection_hint' => $this->selection_hint,
            'syntax' => $this->syntax,
            'args_schema' => $this->args_schema,
            'examples' => $this->examples ?? [],
            'weights' => $this->weights ?? ['priority' => 0.33, 'cost_hint' => 0.33, 'success_hint' => 0.33],
            'permissions' => $this->permissions ?? [],
            'source' => $this->source,
        ];
    }

    public function getWeightAttribute($value)
    {
        if ($value && is_array($value)) {
            return $value;
        }

        return [
            'priority' => 0.33,
            'cost_hint' => 0.33,
            'success_hint' => 0.33,
        ];
    }
}
