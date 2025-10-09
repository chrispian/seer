<?php

namespace App\Http\Controllers\Orchestration;

use App\Http\Controllers\Controller;
use App\Models\TaskActivity;
use App\Models\WorkItem;
use Illuminate\Http\Request;

class TaskActivityController extends Controller
{
    public function index(Request $request, string $taskId)
    {
        $request->validate([
            'type' => 'sometimes|string|in:status_change,content_update,assignment,note,error,artifact_attached',
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $task = WorkItem::findOrFail($taskId);

        $query = TaskActivity::query()
            ->forTask($taskId)
            ->with(['agent:id,name,slug', 'user:id,name']);

        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        $limit = $request->input('limit', 20);
        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'data' => $activities->items(),
            'meta' => [
                'task_id' => $taskId,
                'task_code' => $task->metadata['task_code'] ?? null,
                'current_page' => $activities->currentPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
            ],
        ]);
    }

    public function store(Request $request, string $taskId)
    {
        $task = WorkItem::findOrFail($taskId);

        $validated = $request->validate([
            'activity_type' => 'required|string|in:note,error',
            'action' => 'sometimes|string|max:100',
            'description' => 'required|string',
            'metadata' => 'sometimes|array',
        ]);

        $activity = TaskActivity::create([
            'task_id' => $taskId,
            'agent_id' => $request->input('agent_id'),
            'user_id' => auth()->id(),
            'activity_type' => $validated['activity_type'],
            'action' => $validated['action'] ?? ($validated['activity_type'] === 'note' ? 'note_added' : 'error_encountered'),
            'description' => $validated['description'],
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $activity->load(['agent:id,name,slug', 'user:id,name']);

        return response()->json([
            'success' => true,
            'activity' => $activity,
        ], 201);
    }

    public function show(string $taskId, string $activityId)
    {
        $activity = TaskActivity::query()
            ->forTask($taskId)
            ->with(['agent:id,name,slug', 'user:id,name', 'task'])
            ->findOrFail($activityId);

        return response()->json([
            'data' => $activity,
        ]);
    }

    public function summary(string $taskId)
    {
        $task = WorkItem::findOrFail($taskId);

        $summary = TaskActivity::query()
            ->forTask($taskId)
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->get()
            ->keyBy('activity_type');

        $recent = TaskActivity::query()
            ->forTask($taskId)
            ->with(['agent:id,name', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'task_id' => $taskId,
            'task_code' => $task->metadata['task_code'] ?? null,
            'summary' => $summary,
            'recent_activities' => $recent,
        ]);
    }
}
