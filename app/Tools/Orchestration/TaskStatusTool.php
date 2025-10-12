<?php

namespace App\Tools\Orchestration;

use App\Commands\Orchestration\Task\UpdateStatusCommand;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TaskStatusTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_tasks_status';

    protected string $title = 'Update orchestration task status';

    protected string $description = 'Change delegation status for a work item and sync the active assignment.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->optional()->description('Optional session key (SESSION-XXX) to infer task from active context'),
            'task' => $schema->string()->optional()->description('Task UUID or delegation task code. Required if session_key not provided or no active task'),
            'status' => $schema->string()->enum([
                'unassigned', 'assigned', 'in_progress', 'blocked', 'completed', 'cancelled',
            ])->required(),
            'assignment_status' => $schema->string()->enum([
                'unassigned', 'assigned', 'in_progress', 'blocked', 'completed', 'cancelled',
            ])->description('Override assignment status; defaults to status value'),
            'note' => $schema->string()->description('Optional note stored alongside history'),
            'assignments_limit' => $schema->integer()->min(1)->max(20)->default(10),
            'include_history' => $schema->boolean()->default(true),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'session_key' => ['nullable', 'string'],
            'task' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'assignment_status' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'assignments_limit' => ['nullable', 'integer'],
            'include_history' => ['nullable', 'boolean'],
        ]);

        $taskCode = $validated['task'] ?? null;

        if (!$taskCode && !empty($validated['session_key'])) {
            $sessionManager = app(\App\Services\Orchestration\SessionManager::class);
            $session = \App\Models\WorkSession::where('session_key', $validated['session_key'])->first();
            
            if ($session) {
                $activeTask = $sessionManager->getActiveContext($session->id, 'task');
                if ($activeTask) {
                    $taskCode = $activeTask['data']['task_code'] ?? $activeTask['id'];
                }
            }
        }

        if (!$taskCode) {
            return Response::json([
                'success' => false,
                'error' => 'task required when no active task in session',
            ]);
        }

        $command = new UpdateStatusCommand([
            'task_code' => $taskCode,
            'delegation_status' => $validated['status'],
            'note' => $validated['note'] ?? null,
        ]);
        
        $command->setContext('mcp');
        $result = $command->handle();

        return Response::json($result);
    }

    public static function summaryName(): string
    {
        return 'orchestration_tasks_status';
    }

    public static function summaryTitle(): string
    {
        return 'Update orchestration task status';
    }

    public static function summaryDescription(): string
    {
        return 'Set delegation status and reflect it on the most recent assignment.';
    }

    public static function schemaSummary(): array
    {
        return [
            'task' => 'Task UUID or code',
            'status' => 'unassigned|assigned|in_progress|blocked|completed|cancelled',
            'note' => 'optional note captured in history',
        ];
    }
}
