<?php

namespace App\Commands;

class SessionListCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get chat sessions
        $sessions = $this->getChatSessions();

        return [
            'type' => 'session',
            'component' => 'SessionListModal',
            'data' => $sessions,
        ];
    }

    private function getChatSessions(): array
    {
        if (class_exists(\App\Models\ChatSession::class)) {
            $sessions = \App\Models\ChatSession::query()
                ->where('is_active', true)
                ->orderBy('last_activity_at', 'desc')
                ->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'title' => $session->title,
                        'channel_display' => $session->channel_display,
                        'message_count' => $session->message_count,
                        'is_pinned' => $session->is_pinned,
                        'is_active' => $session->is_active,
                        'vault_id' => $session->vault_id,
                        'project_id' => $session->project_id,
                        'last_activity_at' => $session->last_activity_at?->toISOString(),
                        'created_at' => $session->created_at?->toISOString(),
                        'updated_at' => $session->updated_at?->toISOString(),
                        'last_activity_human' => $session->last_activity_at?->diffForHumans(),
                        'created_human' => $session->created_at?->diffForHumans(),
                    ];
                })
                ->all();

            return $sessions;
        }

        return [];
    }

    public static function getName(): string
    {
        return 'Session List';
    }

    public static function getDescription(): string
    {
        return 'List all chat sessions with activity info';
    }

    public static function getUsage(): string
    {
        return '/session';
    }

    public static function getCategory(): string
    {
        return 'Navigation';
    }
}
