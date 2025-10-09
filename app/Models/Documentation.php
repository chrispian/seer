<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'documentation';

    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'file_path',
        'namespace',
        'file_hash',
        'subsystem',
        'purpose',
        'tags',
        'related_docs',
        'related_code_paths',
        'version',
        'last_modified',
        'git_hash',
    ];

    protected $casts = [
        'tags' => 'array',
        'related_docs' => 'array',
        'related_code_paths' => 'array',
        'version' => 'integer',
        'last_modified' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeByNamespace($query, string $namespace)
    {
        return $query->where('namespace', $namespace);
    }

    public function scopeBySubsystem($query, string $subsystem)
    {
        return $query->where('subsystem', $subsystem);
    }

    public function scopeByPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeWithAnyTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    public function scopeWithAllTags($query, array $tags)
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }

        return $query;
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->whereRaw(
            "to_tsvector('english', title || ' ' || content) @@ plainto_tsquery('english', ?)",
            [$searchTerm]
        );
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_modified', '>=', now()->subDays($days));
    }

    public function scopeOutdated($query, int $months = 6)
    {
        return $query->where('last_modified', '<', now()->subMonths($months))
            ->whereJsonLength('tags', 0);
    }

    public function addTag(string $tag): self
    {
        $tags = $this->tags ?? [];
        if (! in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }

        return $this;
    }

    public function removeTag(string $tag): self
    {
        $tags = $this->tags ?? [];
        $this->tags = array_values(array_filter($tags, fn ($t) => $t !== $tag));
        $this->save();

        return $this;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function addRelatedDoc(string $docPath): self
    {
        $related = $this->related_docs ?? [];
        if (! in_array($docPath, $related)) {
            $related[] = $docPath;
            $this->related_docs = $related;
            $this->save();
        }

        return $this;
    }

    public function addRelatedCodePath(string $codePath): self
    {
        $paths = $this->related_code_paths ?? [];
        if (! in_array($codePath, $paths)) {
            $paths[] = $codePath;
            $this->related_code_paths = $paths;
            $this->save();
        }

        return $this;
    }
}
