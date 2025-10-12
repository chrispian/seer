<?php

namespace App\Commands\Orchestration\Sprint;

use App\Commands\BaseCommand;
use App\Models\Sprint;
use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;

class ActivateCommand extends BaseCommand
{
    protected ?string $sprintCode = null;

    public function __construct(array $options = [])
    {
        $this->sprintCode = $options['sprint'] ?? $options['code'] ?? $options[0] ?? null;
    }

    public function handle(): array
    {
        $sessionManager = app(SessionManager::class);

        try {
            $session = WorkSession::where('status', 'active')
                ->where('user_id', auth()->id())
                ->latest()
                ->firstOrFail();

            if (!$this->sprintCode) {
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => "❌ Please provide a sprint code. Usage: /sprint-activate SPRINT-XX",
                ];
            }

            $sprintCode = $this->sprintCode;
            if (is_numeric($sprintCode)) {
                $sprintCode = 'SPRINT-' . $sprintCode;
            }

            $sprint = Sprint::where('code', $sprintCode)->firstOrFail();

            $sessionManager->pushContext($session->id, 'sprint', $sprint->id, [
                'code' => $sprint->code,
                'title' => $sprint->title,
            ]);

            $message = "✅ **Sprint Activated: {$sprint->code}**\n\n";
            $message .= "**Title:** {$sprint->title}\n";
            $message .= "**Priority:** {$sprint->priority}\n";
            $message .= "**Status:** {$sprint->status}\n\n";

            $stats = $sprint->stats ?? ['total' => 0, 'completed' => 0];
            $message .= "**Tasks:** {$stats['completed']}/{$stats['total']} completed\n\n";
            $message .= "**Next Steps:**\n";
            $message .= "- Use `/task-activate T-XX` to start working on a task\n";
            $message .= "- Use `/session-status` to view active context\n";

            return [
                'type' => 'message',
                'component' => null,
                'message' => $message,
                'data' => [
                    'sprint' => $sprint->toArray(),
                    'session' => $session->fresh()->toArray(),
                ],
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if (str_contains($e->getMessage(), 'WorkSession')) {
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => "❌ No active session found. Use `/session-start` to create one.",
                ];
            }

            return [
                'type' => 'message',
                'component' => null,
                'message' => "❌ Sprint '{$this->sprintCode}' not found. Use `/sprints` to see available sprints.",
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "❌ Error activating sprint: {$e->getMessage()}",
            ];
        }
    }

    public static function getName(): string
    {
        return 'Sprint Activate';
    }

    public static function getDescription(): string
    {
        return 'Set active sprint in current session context';
    }

    public static function getUsage(): string
    {
        return '/sprint-activate [sprint-code]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
