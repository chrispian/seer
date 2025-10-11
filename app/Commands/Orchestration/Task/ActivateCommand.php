<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Models\WorkItem;
use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Services\Orchestration\TimeTrackingService;
use App\Services\Orchestration\InstructionBuilder;

class ActivateCommand extends BaseCommand
{
    protected ?string $taskCode = null;

    public function __construct(array $options = [])
    {
        $this->taskCode = $options['task'] ?? $options['code'] ?? $options[0] ?? null;
    }

    public function handle(): array
    {
        $sessionManager = app(SessionManager::class);
        $timeTracking = app(TimeTrackingService::class);

        try {
            $session = WorkSession::where('status', 'active')
                ->where('user_id', auth()->id())
                ->latest()
                ->firstOrFail();

            if (!$this->taskCode) {
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => "❌ Please provide a task code. Usage: /task-activate T-XX",
                ];
            }

            $task = WorkItem::where('id', $this->taskCode)
                ->orWhere('metadata->task_code', $this->taskCode)
                ->firstOrFail();

            $taskCode = $task->metadata['task_code'] ?? $task->id;

            if ($task->delegation_status === 'unassigned') {
                $task->update(['delegation_status' => 'in_progress']);
            }

            if ($task->status === 'todo') {
                $task->update(['status' => 'in_progress']);
            }

            $sessionManager->pushContext($session->id, 'task', $task->id, [
                'task_code' => $taskCode,
                'task_name' => $task->metadata['task_name'] ?? null,
                'priority' => $task->priority,
            ]);

            $session->update(['active_task_id' => $task->id]);

            $timeTracking->startTracking($session->id, $task->id);

            $instructionBuilder = app(InstructionBuilder::class);
            $instructions = $instructionBuilder->forTaskActivate($task, $session);

            $message = "✅ **Task Activated: {$taskCode}**\n\n";
            if (!empty($task->metadata['task_name'])) {
                $message .= "**Name:** {$task->metadata['task_name']}\n";
            }
            $message .= "**Status:** {$task->delegation_status}\n";
            $message .= "**Priority:** {$task->priority}\n";
            
            if (!empty($task->metadata['estimate_text'])) {
                $message .= "**Estimate:** {$task->metadata['estimate_text']}\n";
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
                    'task' => $task->toArray(),
                    'session' => $session->fresh()->toArray(),
                    'instructions' => $instructions,
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
                'message' => "❌ Task '{$this->taskCode}' not found. Use `/tasks` to see available tasks.",
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "❌ Error activating task: {$e->getMessage()}",
            ];
        }
    }

    public static function getName(): string
    {
        return 'Task Activate';
    }

    public static function getDescription(): string
    {
        return 'Set active task in current session context';
    }

    public static function getUsage(): string
    {
        return '/task-activate [task-code]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
