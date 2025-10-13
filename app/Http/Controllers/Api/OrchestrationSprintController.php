<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrchestrationSprint;
use App\Services\Orchestration\OrchestrationEventService;
use App\Services\Orchestration\OrchestrationHashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrchestrationSprintController extends Controller
{
    public function __construct(
        protected OrchestrationHashService $hashService,
        protected OrchestrationEventService $eventService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = OrchestrationSprint::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('owner')) {
            $query->where('owner', $request->owner);
        }

        $sprints = $query->with('tasks')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($sprints);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sprint_code' => 'required|string|unique:orchestration_sprints,sprint_code',
            'title' => 'required|string|max:255',
            'status' => 'sometimes|in:planning,active,completed,on_hold',
            'owner' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'file_path' => 'nullable|string',
        ]);

        $sprint = OrchestrationSprint::create($validated);
        
        $this->eventService->emitSprintCreated($sprint, $request->header('X-Session-Key'));

        return response()->json([
            'success' => true,
            'sprint' => $sprint->load('tasks'),
        ], 201);
    }

    public function show(string $code): JsonResponse
    {
        $sprint = OrchestrationSprint::where('sprint_code', $code)
            ->with(['tasks', 'events' => fn($q) => $q->recent(20)])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'sprint' => $sprint,
        ]);
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $sprint = OrchestrationSprint::where('sprint_code', $code)->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:planning,active,completed,on_hold',
            'owner' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'file_path' => 'nullable|string',
        ]);

        $changes = $this->hashService->detectChanges($sprint, $validated);
        
        $sprint->update($validated);
        
        if (!empty($changes)) {
            $this->eventService->emitSprintUpdated($sprint, $changes, $request->header('X-Session-Key'));
        }

        return response()->json([
            'success' => true,
            'sprint' => $sprint->load('tasks'),
            'changes' => $changes,
        ]);
    }

    public function destroy(string $code): JsonResponse
    {
        $sprint = OrchestrationSprint::where('sprint_code', $code)->firstOrFail();
        
        $this->eventService->emit('orchestration.sprint.deleted', $sprint, [
            'sprint_code' => $sprint->sprint_code,
        ], request()->header('X-Session-Key'));

        $sprint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sprint deleted successfully',
        ]);
    }
}
