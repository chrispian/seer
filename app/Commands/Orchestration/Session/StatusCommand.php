<?php

namespace App\Commands\Orchestration\Session;

use App\Commands\BaseCommand;
use App\Models\WorkSession;

class StatusCommand extends BaseCommand
{
    protected ?string $sessionKey = null;

    public function __construct(array $options = [])
    {
        $this->sessionKey = $options['session'] ?? $options[0] ?? null;
    }

    public function handle(): array
    {
        try {
            $session = $this->sessionKey
                ? WorkSession::where('session_key', $this->sessionKey)->firstOrFail()
                : WorkSession::where('status', 'active')->where('user_id', auth()->id())->latest()->firstOrFail();

            $duration = gmdate('H:i:s', $session->getDurationInSecondsAttribute());
            $contextStack = $session->context_stack ?? [];

            $message = "📊 **Session Status: {$session->session_key}**\n\n";
            $message .= "**Type:** {$session->session_type}\n";
            $message .= "**Status:** {$session->status}\n";
            $message .= "**Source:** {$session->source}\n";
            $message .= "**Duration:** {$duration}\n";
            $message .= "**Started:** {$session->started_at->format('Y-m-d H:i:s')}\n\n";

            if (!empty($contextStack)) {
                $message .= "**Context Stack:**\n";
                foreach ($contextStack as $context) {
                    $message .= "- {$context['type']}: {$context['id']}\n";
                }
            } else {
                $message .= "**Context Stack:** Empty\n";
            }

            $recentActivities = $session->activities()->recent()->limit(5)->get();
            if ($recentActivities->isNotEmpty()) {
                $message .= "\n**Recent Activities:**\n";
                foreach ($recentActivities as $activity) {
                    $time = $activity->occurred_at->format('H:i');
                    $message .= "- [{$time}] {$activity->activity_type}";
                    if ($activity->description) {
                        $message .= ": {$activity->description}";
                    }
                    $message .= "\n";
                }
            }

            return [
                'type' => 'message',
                'component' => null,
                'message' => $message,
                'data' => [
                    'session' => $session->toArray(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "❌ No active session found. Use `/session-start` to create one.",
            ];
        }
    }

    public static function getName(): string
    {
        return 'Session Status';
    }

    public static function getDescription(): string
    {
        return 'View current session status and context';
    }

    public static function getUsage(): string
    {
        return '/session-status [session-key]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
