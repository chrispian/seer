<?php

namespace App\Tools\Orchestration;

use App\Models\WorkItem;
use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionTaskActivateTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_task_activate';

    protected string $title = 'Activate task in session';

    protected string $description = 'Set active task in current session context and update status to in_progress.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->required()->description('Session key (SESSION-XXX)'),
            'task_code' => $schema->string()->required()->description('Task code (T-XX-YYY)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $sessionManager = app(SessionManager::class);

        try {
            $sessionKey = $request->get('session_key');
            $taskCode = $request->get('task_code');

            $session = WorkSession::where('session_key', $sessionKey)->firstOrFail();
            $task = WorkItem::where('id', $taskCode)
                ->orWhere('metadata->task_code', $taskCode)
                ->firstOrFail();

            $resolvedTaskCode = $task->metadata['task_code'] ?? $task->id;

            if ($task->delegation_status === 'unassigned') {
                $task->update(['delegation_status' => 'in_progress']);
            }

            if ($task->status === 'todo') {
                $task->update(['status' => 'in_progress']);
            }

            $sessionManager->pushContext($session->id, 'task', $task->id, [
                'task_code' => $resolvedTaskCode,
                'task_name' => $task->metadata['task_name'] ?? null,
                'priority' => $task->priority,
            ]);

            return Response::json([
                'success' => true,
                'task' => [
                    'code' => $resolvedTaskCode,
                    'name' => $task->metadata['task_name'] ?? null,
                    'status' => $task->delegation_status,
                    'priority' => $task->priority,
                    'estimate' => $task->metadata['estimate_text'] ?? null,
                ],
                'instructions' => [
                    'next_actions' => [
                        'Begin working on the task',
                        'Use contextual update tools to log progress',
                        'Deactivate task when complete',
                    ],
                    'suggested_tools' => [
                        'orchestration_session_task_update',
                        'orchestration_session_task_note',
                        'orchestration_session_task_deactivate',
                    ],
                    'context_reminder' => [
                        'session' => $sessionKey,
                        'active_task' => $resolvedTaskCode,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function summaryName(): string
    {
        return 'orchestration_session_task_activate';
    }

    public static function summaryTitle(): string
    {
        return 'Activate task in session';
    }

    public static function summaryDescription(): string
    {
        return 'Set active task context and mark in progress.';
    }

    public static function schemaSummary(): array
    {
        return [
            'session_key' => 'Session key (SESSION-XXX)',
            'task_code' => 'Task code (T-XX-YYY)',
        ];
    }
}
