<?php

namespace App\Tools\Orchestration;

use App\Models\WorkSession;
use App\Services\Orchestration\SessionManager;
use App\Support\Orchestration\ModelResolver;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SessionTaskUpdateTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_session_task_update';

    protected string $title = 'Update active task context';

    protected string $description = 'Add context update to the active task in current session.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_key' => $schema->string()->required()->description('Session key (SESSION-XXX)'),
            'update' => $schema->string()->required()->description('Context update or progress note'),
        ];
    }

    public function handle(Request $request): Response
    {
        $sessionManager = app(SessionManager::class);

        try {
            $sessionKey = $request->get('session_key');
            $update = $request->get('update');

            $session = WorkSession::where('session_key', $sessionKey)->firstOrFail();
            $activeTask = $sessionManager->getActiveContext($session->id, 'task');

            if (!$activeTask) {
                return Response::json([
                    'success' => false,
                    'error' => 'No active task in session',
                    'instructions' => [
                        'note' => 'Activate a task first with orchestration_session_task_activate',
                    ],
                ]);
            }

            $taskId = $activeTask['id'];
            $taskCode = $activeTask['data']['task_code'] ?? $taskId;

            $sessionManager->logActivity($session->id, 'context_update', [
                'description' => $update,
                'task_id' => $taskId,
            ]);

            $service = ModelResolver::resolveService('task_service', 'App\\Services\\TaskOrchestrationService');
            $task = $service->find($taskId);

            $existingContext = $task['agent_content'] ?? '';
            $updatedContext = $existingContext 
                ? $existingContext . "\n\n" . $update 
                : $update;

            $service->save($taskId, [
                'agent_content' => $updatedContext,
            ]);

            return Response::json([
                'success' => true,
                'task' => [
                    'code' => $taskCode,
                    'update_added' => true,
                ],
                'instructions' => [
                    'next_actions' => [
                        'Continue working on task',
                        'Add more updates as progress is made',
                    ],
                    'suggested_tools' => [
                        'orchestration_session_task_update',
                        'orchestration_session_task_note',
                        'orchestration_session_task_deactivate',
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
        return 'orchestration_session_task_update';
    }

    public static function summaryTitle(): string
    {
        return 'Update active task context';
    }

    public static function summaryDescription(): string
    {
        return 'Add context/progress to active task.';
    }

    public static function schemaSummary(): array
    {
        return [
            'session_key' => 'Session key (SESSION-XXX)',
            'update' => 'Context update or progress note',
        ];
    }
}
