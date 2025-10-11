<?php

namespace App\Commands\Orchestration\Session;

use App\Commands\BaseCommand;
use App\Services\Orchestration\SessionManager;
use App\Services\Orchestration\SessionPersistenceService;
use App\Services\Orchestration\InstructionBuilder;

class StartCommand extends BaseCommand
{
    protected ?string $agentId = null;
    protected ?string $type = null;
    protected ?string $source = null;

    public function __construct(array $options = [])
    {
        $this->agentId = $options['agent_id'] ?? $options['agent'] ?? null;
        $this->type = $options['type'] ?? 'work';
        $this->source = $options['source'] ?? 'cli';
    }

    public function handle(): array
    {
        $sessionManager = app(SessionManager::class);
        $persistence = app(SessionPersistenceService::class);
        $instructionBuilder = app(InstructionBuilder::class);

        try {
            if ($persistence->shouldAutoResume()) {
                $savedSession = $persistence->loadActiveSession();
                $existingSession = $persistence->resumeSession($savedSession['session_id']);
                
                if ($existingSession) {
                    $context = $persistence->getSessionContext($existingSession);
                    $instructions = $instructionBuilder->forSessionResume($existingSession, $context);

                    $message = "🔄 **Resuming Existing Session: {$existingSession->session_key}**\n\n";
                    $message .= "**Type:** {$existingSession->session_type}\n";
                    $message .= "**Started:** {$existingSession->started_at->format('Y-m-d H:i:s')}\n";
                    $message .= "**Status:** Resumed\n\n";
                    
                    if ($context['active_sprint']) {
                        $message .= "**Active Sprint:** {$context['active_sprint']['data']['code']}\n";
                    }
                    if ($context['active_task']) {
                        $message .= "**Active Task:** {$context['active_task']['data']['task_code']}\n";
                    }
                    
                    $message .= "\n**Next Actions:**\n";
                    foreach ($instructions['next_actions'] as $action) {
                        $message .= "- {$action}\n";
                    }
                    
                    return [
                        'type' => 'message',
                        'component' => null,
                        'message' => $message,
                        'data' => [
                            'session' => $existingSession->toArray(),
                            'resumed' => true,
                            'instructions' => $instructions,
                        ],
                    ];
                }
            }

            $session = $sessionManager->startSession([
                'agent_id' => $this->agentId,
                'user_id' => auth()->id(),
                'source' => $this->source,
                'session_type' => $this->type,
            ]);

            $persistence->saveActiveSession($session->session_key, $session->id);

            $instructions = $instructionBuilder->forSessionStart($session);

            $message = "✅ **Session Started: {$session->session_key}**\n\n";
            $message .= "**Type:** {$session->session_type}\n";
            $message .= "**Source:** {$session->source}\n";
            $message .= "**Started:** {$session->started_at->format('Y-m-d H:i:s')}\n\n";
            $message .= "**Next Actions:**\n";
            foreach ($instructions['next_actions'] as $action) {
                $message .= "- {$action}\n";
            }

            return [
                'type' => 'message',
                'component' => null,
                'message' => $message,
                'data' => [
                    'session' => $session->toArray(),
                    'instructions' => $instructions,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "❌ Error starting session: {$e->getMessage()}",
            ];
        }
    }

    public static function getName(): string
    {
        return 'Session Start';
    }

    public static function getDescription(): string
    {
        return 'Start a new work session for context tracking';
    }

    public static function getUsage(): string
    {
        return '/session-start [--type=work|planning|review]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
