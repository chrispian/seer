<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationContextBrokerService;
use App\Services\Orchestration\OrchestrationEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrchestrationAgentController extends Controller
{
    public function __construct(
        protected OrchestrationContextBrokerService $contextBroker,
        protected OrchestrationEventService $eventService
    ) {}

    public function init(Request $request): JsonResponse
    {
        if ($request->has('resume_session')) {
            return $this->resumeSession($request->resume_session);
        }

        $validated = $request->validate([
            'entity_type' => 'required|in:sprint,task',
            'entity_code' => 'required|string',
            'agent_id' => 'nullable|integer',
        ]);

        $entityType = $validated['entity_type'];
        $entityCode = $validated['entity_code'];
        $agentId = $validated['agent_id'] ?? null;

        $entity = null;
        $context = null;

        if ($entityType === 'sprint') {
            $entity = OrchestrationSprint::where('sprint_code', $entityCode)->first();
            if (!$entity) {
                return response()->json([
                    'success' => false,
                    'message' => "Sprint '{$entityCode}' not found",
                ], 404);
            }
            $context = $this->contextBroker->assembleSprintContext($entityCode);
        } elseif ($entityType === 'task') {
            $entity = OrchestrationTask::where('task_code', $entityCode)->first();
            if (!$entity) {
                return response()->json([
                    'success' => false,
                    'message' => "Task '{$entityCode}' not found",
                ], 404);
            }
            $context = $this->contextBroker->assembleTaskContext($entityCode);
        }

        $sessionKey = (string) Str::uuid();

        $this->eventService->emitSessionStarted(
            $entity,
            $sessionKey,
            $agentId
        );

        $nextSteps = $this->generateNextSteps($entityType, $entity, $context);

        return response()->json([
            'success' => true,
            'session_key' => $sessionKey,
            'entity' => $entity,
            'context' => $context,
            'message' => "Agent initialized on {$entityType} '{$entityCode}'",
            'next_steps' => $nextSteps,
        ], 201);
    }

    public function getSessionContext(string $sessionKey): JsonResponse
    {
        $context = $this->contextBroker->assembleSessionContext($sessionKey);

        if (!$context) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'context' => $context,
        ]);
    }

    public function logActivity(Request $request, string $sessionKey): JsonResponse
    {
        $validated = $request->validate([
            'activity_type' => 'required|string',
            'payload' => 'nullable|array',
        ]);

        $sessionContext = $this->contextBroker->assembleSessionContext($sessionKey);

        if (!$sessionContext) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $entityType = $sessionContext['session']['entity_type'];
        $entityId = $sessionContext['session']['entity_id'];

        $entity = null;
        if ($entityType === 'sprint') {
            $entity = OrchestrationSprint::find($entityId);
        } elseif ($entityType === 'task') {
            $entity = OrchestrationTask::find($entityId);
        }

        if (!$entity) {
            return response()->json([
                'success' => false,
                'message' => 'Entity not found',
            ], 404);
        }

        $this->eventService->emit(
            "orchestration.session.activity.{$validated['activity_type']}",
            $entity,
            $validated['payload'] ?? [],
            $sessionKey
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity logged',
        ]);
    }

    private function resumeSession(string $sessionKey): JsonResponse
    {
        $context = $this->contextBroker->assembleSessionContext($sessionKey);

        if (!$context) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $entityType = $context['session']['entity_type'];
        $entityId = $context['session']['entity_id'];

        $entity = null;
        if ($entityType === 'sprint') {
            $entity = OrchestrationSprint::find($entityId);
        } elseif ($entityType === 'task') {
            $entity = OrchestrationTask::find($entityId);
        }

        if (!$entity) {
            return response()->json([
                'success' => false,
                'message' => 'Entity no longer exists',
            ], 404);
        }

        $this->eventService->emitSessionResumed($entity, $sessionKey);

        return response()->json([
            'success' => true,
            'session_key' => $sessionKey,
            'resumed' => true,
            'entity' => $entity,
            'context' => $context['entity_context'],
            'session_state' => $context['session'],
            'message' => 'Session resumed',
        ]);
    }

    private function generateNextSteps(string $entityType, $entity, ?array $context): array
    {
        $steps = [];

        if ($entityType === 'task') {
            if (isset($context['content']['task_md'])) {
                $steps[] = "Review task requirements in TASK.md";
            }
            if (isset($context['content']['agent_yml'])) {
                $steps[] = "Check agent configuration in AGENT.yml";
            }
            if (isset($context['task']['metadata']['acceptance_criteria'])) {
                $steps[] = "Review acceptance criteria";
            }
            if (isset($context['sprint'])) {
                $steps[] = "Check sprint context and related tasks";
            }
        } elseif ($entityType === 'sprint') {
            if (isset($context['tasks']) && count($context['tasks']) > 0) {
                $pendingCount = collect($context['tasks'])->where('status', 'pending')->count();
                if ($pendingCount > 0) {
                    $steps[] = "Review {$pendingCount} pending task(s)";
                }
            }
            if (isset($context['sprint']['metadata']['goal'])) {
                $steps[] = "Review sprint goal";
            }
            $steps[] = "Check sprint progress and task statuses";
        }

        if (isset($context['recent_events']) && count($context['recent_events']) > 0) {
            $steps[] = "Review recent activity (" . count($context['recent_events']) . " events)";
        }

        return $steps;
    }
}
