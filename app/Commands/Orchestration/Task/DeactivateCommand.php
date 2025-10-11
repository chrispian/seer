<?php

namespace App\Commands\Orchestration\Task;

use App\Commands\BaseCommand;
use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Services\Orchestration\TimeTrackingService;
use App\Services\Orchestration\InstructionBuilder;

class DeactivateCommand extends BaseCommand
{
    protected ?string $summary = null;
    protected bool $complete = false;

    public function __construct(array $options = [])
    {
        $this->summary = $options['summary'] ?? $options[0] ?? null;
        $this->complete = $options['complete'] ?? false;
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

            $activeTask = $sessionManager->getActiveContext($session->id, 'task');

            if (!$activeTask) {
                return [
                    'type' => 'message',
                    'component' => null,
                    'message' => "❌ No active task in current session. Use `/task-activate T-XX` to activate a task.",
                ];
            }

            $taskId = $activeTask['id'];
            $taskCode = $activeTask['data']['task_code'] ?? $taskId;

            $task = \App\Models\WorkItem::find($taskId);

            $timeResult = $timeTracking->stopTracking($session->id, $taskId);

            $popped = $sessionManager->popContext($session->id, 'task');

            $instructionBuilder = app(InstructionBuilder::class);
            $instructions = $instructionBuilder->forTaskDeactivate($task, $timeResult);

            $message = "✅ **Task Deactivated: {$taskCode}**\n\n";

            if ($timeResult['success']) {
                $message .= "**Time Tracked:** {$timeResult['duration_formatted']}\n";
                $message .= "**Total Time:** {$timeResult['total_time_formatted']}\n";
                
                if (isset($timeResult['variance'])) {
                    $variance = $timeResult['variance'];
                    $message .= "**Estimate:** {$variance['estimate_formatted']}\n";
                    $message .= "**Variance:** {$variance['variance_formatted']} ({$variance['percentage']}%)\n";
                }
                $message .= "\n";
            }

            if ($this->complete) {
                $message .= "**Status:** Marked for completion\n";
                $message .= "Use orchestration commands to mark task as completed.\n\n";
            }

            if ($this->summary) {
                $sessionManager->logActivity($session->id, 'note', [
                    'description' => "Task summary: {$this->summary}",
                    'task_id' => $taskId,
                ]);
                $message .= "**Summary:** {$this->summary}\n\n";
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
                    'session' => $session->fresh()->toArray(),
                    'deactivated_task' => $popped,
                    'instructions' => $instructions,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'message',
                'component' => null,
                'message' => "❌ Error deactivating task: {$e->getMessage()}",
            ];
        }
    }

    public static function getName(): string
    {
        return 'Task Deactivate';
    }

    public static function getDescription(): string
    {
        return 'Remove active task from current session context';
    }

    public static function getUsage(): string
    {
        return '/task-deactivate [--summary="Task summary"] [--complete]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
