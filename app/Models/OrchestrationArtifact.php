<?php

namespace App\Models;

use App\Services\Orchestration\Artifacts\ContentStore;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrchestrationArtifact extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'orchestration_artifacts';

    protected $fillable = [
        'task_id',
        'hash',
        'filename',
        'mime_type',
        'size_bytes',
        'metadata',
        'fe_uri',
        'storage_path',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size_bytes' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'task_id');
    }

    public function scopeByTask($query, string $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeByHash($query, string $hash)
    {
        return $query->where('hash', $hash);
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            get: function () {
                $store = app(ContentStore::class);
                return $store->get($this->hash);
            }
        );
    }

    protected function sizeFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bytes = $this->size_bytes;
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                
                for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                    $bytes /= 1024;
                }
                
                return round($bytes, 2) . ' ' . $units[$i];
            }
        );
    }

    public function getPublicUrl(): string
    {
        return $this->fe_uri;
    }
}
