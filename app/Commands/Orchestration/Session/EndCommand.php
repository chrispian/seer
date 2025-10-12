<?php

namespace App\Commands\Orchestration\Session;

use App\Commands\BaseCommand;
use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Services\Orchestration\SessionPersistenceService;

class EndCommand extends BaseCommand
{
    protected ?string $sessionKey = null;
    protected ?string $summary = null;

    public function __construct(array $options = [])
    {
        $this->sessionKey = $options['session'] ?? $options[0] ?? null;
        $this->summary = $options['summary'] ?? null;
    }

    public function handle(): array
    {
        $sessionManager = app(SessionManager::class);
        $persistence = app(SessionPersistenceService::class);

        try {
            $session = $this->sessionKey
                ? WorkSession::where('session_key', $this->sessionKey)->firstOrFail()
                : WorkSession::where('status', 'active')->where('user_id', auth()->id())->latest()->firstOrFail();

            $validation = $sessionManager->validateCompletion($session->id);

            if (!$validation['valid']) {
                $message = "⚠️ **Cannot End Session**\n\n";
                $message .= "**Errors:**\n";
                foreach ($validation['errors'] as $error) {
                    $message .= "- {$error}\n";
                }
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => $message,
                ];
            }

            $session = $sessionManager->endSession($session->id, [
                'summary' => $this->summary,
            ]);

            $persistence->clearActiveSession();

            $duration = gmdate('H:i:s', $session->total_active_seconds);

            $message = "✅ **Session Ended: {$session->session_key}**\n\n";
            $message .= "**Duration:** {$duration}\n";
            $message .= "**Tasks Completed:** {$session->tasks_completed}\n";
            $message .= "**Artifacts Created:** {$session->artifacts_created}\n";

            if (!empty($validation['warnings'])) {
                $message .= "\n**Warnings:**\n";
                foreach ($validation['warnings'] as $warning) {
                    $message .= "- {$warning}\n";
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
                'message' => "❌ Error ending session: {$e->getMessage()}",
            ];
        }
    }

    public static function getName(): string
    {
        return 'Session End';
    }

    public static function getDescription(): string
    {
        return 'End the current work session';
    }

    public static function getUsage(): string
    {
        return '/session-end [session-key] [--summary="Session summary"]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
