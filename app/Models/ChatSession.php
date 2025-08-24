<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    protected $fillable = [
        'vault_id',
        'project_id',
        'title',
        'summary',
        'messages',
        'metadata',
        'message_count',
        'last_activity_at',
        'is_active',
        'is_pinned',
        'sort_order',
    ];

    protected $casts = [
        'vault_id' => 'integer',
        'project_id' => 'integer',
        'messages' => 'array',
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
        'is_pinned' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChatSession $chatSession) {
            if (empty($chatSession->title)) {
                $chatSession->title = 'Chat '.now()->format('M j, g:i A');
            }
        });
    }

    public function addMessage(array $message): void
    {
        $messages = $this->getAttribute('messages') ?? [];
        $messages[] = $message;

        // Force Laravel to recognize the array change by using setAttribute
        $this->setAttribute('messages', $messages);
        $this->setAttribute('message_count', count($messages));
        $this->setAttribute('last_activity_at', now());
        $this->save();

        $this->updateTitleFromMessages();
    }

    public function updateTitleFromMessages(): void
    {
        $messages = $this->getAttribute('messages') ?? [];

        if ($this->message_count > 0 && ! empty($messages)) {
            // Find the first non-system message to use as title
            foreach ($messages as $message) {
                if (isset($message['type']) && $message['type'] !== 'system' && ! empty($message['message'])) {
                    $title = Str::limit(strip_tags($message['message']), 40);
                    if (! empty($title)) {
                        $this->setAttribute('title', $title);
                        $this->save();
                        break;
                    }
                }
            }
        }
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: 'Untitled Chat';
    }

    public function getLastMessagePreviewAttribute(): string
    {
        $messages = $this->getAttribute('messages') ?? [];

        if (empty($messages)) {
            return 'No messages';
        }

        $lastMessage = end($messages);

        return Str::limit(strip_tags($lastMessage['message'] ?? ''), 50);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true)->orderBy('sort_order')->orderBy('updated_at', 'desc');
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

    public function togglePin(): self
    {
        $this->is_pinned = ! $this->is_pinned;

        if ($this->is_pinned && ! $this->sort_order) {
            $maxOrder = static::where('is_pinned', true)->max('sort_order') ?? 0;
            $this->sort_order = $maxOrder + 1;
        }

        $this->save();

        return $this;
    }

    public function updateSortOrder(int $sortOrder): self
    {
        $this->sort_order = $sortOrder;
        $this->save();

        return $this;
    }
}
