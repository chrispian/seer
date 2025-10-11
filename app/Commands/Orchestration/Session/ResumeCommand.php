<?php

namespace App\Commands\Orchestration\Session;

use App\Commands\BaseCommand;
use App\Services\Orchestration\SessionPersistenceService;

class ResumeCommand extends BaseCommand
{
    protected ?string $sessionKey = null;

    public function __construct(array $options = [])
    {
        $this->sessionKey = $options['session'] ?? $options[0] ?? null;
    }

    public function handle(): array
    {
        $persistence = app(SessionPersistenceService::class);

        try {
            $savedSession = $persistence->loadActiveSession();

            if (!$savedSession && !$this->sessionKey) {
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => "❌ No saved session found. Use `/session-start` to create a new session.",
                ];
            }

            $sessionId = $this->sessionKey 
                ? \App\Models\WorkSession::where('session_key', $this->sessionKey)->firstOrFail()->id
                : $savedSession['session_id'];

            $session = $persistence->resumeSession($sessionId);

            if (!$session) {
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => "❌ Session not found or already completed.",
                ];
            }

            $persistence->saveActiveSession($session->session_key, $session->id);

            $context = $persistence->getSessionContext($session);

            $message = "🔄 **Session Resumed: {$session->session_key}**\n\n";
            $message .= "**Type:** {$session->session_type}\n";
            $message .= "**Started:** {$session->started_at->format('Y-m-d H:i:s')}\n";
            $message .= "**Status:** {$session->status}\n\n";

            if ($context['active_sprint']) {
                $message .= "**Active Sprint:** {$context['active_sprint']['data']['code']}\n";
            }
            if ($context['active_task']) {
                $message .= "**Active Task:** {$context['active_task']['data']['task_code']}\n";
            }

            if ($context['last_activity_at']) {
                $lastActivity = \Carbon\Carbon::parse($context['last_activity_at']);
                $message .= "\n**Last Activity:** {$lastActivity->diffForHumans()}\n";
            }

            $message .= "\n**Use `/session-status` to view full context**\n";

            return [
                'type' => 'message',
                'component' => null,
                'message' => $message,
                'data' => [
                    'session' => $session->toArray(),
                    'context' => $context,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "❌ Error resuming session: {$e->getMessage()}",
            ];
        }
    }

    public static function getName(): string
    {
        return 'Session Resume';
    }

    public static function getDescription(): string
    {
        return 'Resume a previously saved work session';
    }

    public static function getUsage(): string
    {
        return '/session-resume [session-key]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
