<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    protected $fillable = [
        'title',
        'summary',
        'messages',
        'metadata',
        'message_count',
        'last_activity_at',
        'is_active',
    ];

    protected $casts = [
        'messages' => 'array',
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
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

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit);
    }
}
