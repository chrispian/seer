<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationEventService;
use App\Services\Orchestration\OrchestrationFileSyncService;
use App\Services\Orchestration\OrchestrationHashService;
use App\Services\Orchestration\OrchestrationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrchestrationTaskController extends Controller
{
    public function __construct(
        protected OrchestrationHashService $hashService,
        protected OrchestrationEventService $eventService,
        protected OrchestrationTemplateService $templateService,
        protected OrchestrationFileSyncService $fileSyncService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = OrchestrationTask::query();

        if ($request->has('sprint_id')) {
            $query->where('sprint_id', $request->sprint_id);
        }

        if ($request->has('sprint_code')) {
            $query->whereHas('sprint', fn($q) => $q->where('sprint_code', $request->sprint_code));
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('phase')) {
            $query->where('phase', $request->phase);
        }

        $tasks = $query->with(['sprint', 'events' => fn($q) => $q->recent(5)])
            ->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sprint_id' => 'nullable|exists:orchestration_sprints,id',
            'task_code' => 'required|string|unique:orchestration_tasks,task_code',
            'title' => 'required|string|max:255',
            'status' => 'sometimes|in:pending,in_progress,completed,blocked',
            'priority' => 'sometimes|in:P0,P1,P2,P3',
            'phase' => 'nullable|integer',
            'metadata' => 'nullable|array',
            'agent_config' => 'nullable|array',
            'file_path' => 'nullable|string',
        ]);

        $task = OrchestrationTask::create($validated);
        
        $this->eventService->emitTaskCreated($task, $request->header('X-Session-Key'));

        return response()->json([
            'success' => true,
            'task' => $task->load('sprint'),
        ], 201);
    }

    public function show(string $code): JsonResponse
    {
        $task = OrchestrationTask::where('task_code', $code)
            ->with(['sprint', 'events' => fn($q) => $q->recent(20)])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $task = OrchestrationTask::where('task_code', $code)->firstOrFail();

        $validated = $request->validate([
            'sprint_id' => 'nullable|exists:orchestration_sprints,id',
            'title' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,in_progress,completed,blocked',
            'priority' => 'sometimes|in:P0,P1,P2,P3',
            'phase' => 'nullable|integer',
            'metadata' => 'nullable|array',
            'agent_config' => 'nullable|array',
            'file_path' => 'nullable|string',
        ]);

        $oldStatus = $task->status;
        $changes = $this->hashService->detectChanges($task, $validated);
        
        $task->update($validated);
        
        if (isset($validated['status']) && $oldStatus !== $validated['status']) {
            $this->eventService->emitTaskStatusChanged(
                $task, 
                $oldStatus, 
                $validated['status'], 
                $request->header('X-Session-Key')
            );
        } elseif (!empty($changes)) {
            $this->eventService->emitTaskUpdated($task, $changes, $request->header('X-Session-Key'));
        }

        return response()->json([
            'success' => true,
            'task' => $task->load('sprint'),
            'changes' => $changes,
        ]);
    }

    public function destroy(string $code): JsonResponse
    {
        $task = OrchestrationTask::where('task_code', $code)->firstOrFail();
        
        $this->eventService->emit('orchestration.task.deleted', $task, [
            'task_code' => $task->task_code,
        ], request()->header('X-Session-Key'));

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }

    public function createFromTemplate(Request $request, string $sprintCode): JsonResponse
    {
        $sprint = OrchestrationSprint::where('sprint_code', $sprintCode)->firstOrFail();

        $validated = $request->validate([
            'template_name' => 'required|string',
            'tasks' => 'required|array|min:1',
            'tasks.*.task_code' => 'required|string',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.priority' => 'sometimes|in:P0,P1,P2,P3',
            'tasks.*.variables' => 'nullable|array',
        ]);

        $templateFileName = $validated['template_name'];
        if (!str_ends_with($templateFileName, '.md')) {
            $templateFileName .= '.md';
        }

        $template = $this->templateService->loadTemplate('task', $templateFileName);
        
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => "Template '{$validated['template_name']}' not found",
            ], 404);
        }

        $createdTasks = [];

        foreach ($validated['tasks'] as $taskData) {
            $taskCode = $taskData['task_code'];
            
            $existing = OrchestrationTask::where('task_code', $taskCode)->first();
            if ($existing) {
                continue;
            }

            $variables = array_merge([
                'task_code' => $taskCode,
                'title' => $taskData['title'],
                'sprint_code' => $sprintCode,
                'status' => 'pending',
                'priority' => $taskData['priority'] ?? 'P2',
            ], $taskData['variables'] ?? []);

            $task = OrchestrationTask::create([
                'task_code' => $taskCode,
                'sprint_id' => $sprint->id,
                'title' => $taskData['title'],
                'status' => 'pending',
                'priority' => $taskData['priority'] ?? 'P2',
                'metadata' => $taskData['variables'] ?? [],
                'agent_config' => [],
                'file_path' => "delegation/sprints/{$sprintCode}/{$taskCode}",
            ]);

            $this->fileSyncService->syncTaskToFile($task);

            $this->eventService->emit('orchestration.task.generated', $task, [
                'task_code' => $task->task_code,
                'template_name' => $validated['template_name'],
            ], $request->header('X-Session-Key'));

            $createdTasks[] = $task;
        }

        return response()->json([
            'success' => true,
            'tasks' => $createdTasks,
            'message' => count($createdTasks) . ' task(s) created from template',
        ], 201);
    }
}
