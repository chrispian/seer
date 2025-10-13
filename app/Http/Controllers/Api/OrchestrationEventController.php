<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrchestrationEvent;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationReplayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrchestrationEventController extends Controller
{
    public function __construct(
        protected OrchestrationReplayService $replayService
    ) {}
    public function index(Request $request)
    {
        $query = OrchestrationEvent::query();

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('session_key')) {
            $query->where('session_key', $request->session_key);
        }

        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        $events = $query->recent($request->limit ?? 50)->get();

        return response()->json($events);
    }

    public function correlation(string $correlationId)
    {
        $events = OrchestrationEvent::byCorrelation($correlationId)->get();

        return response()->json([
            'correlation_id' => $correlationId,
            'event_count' => $events->count(),
            'events' => $events,
        ]);
    }

    public function session(string $sessionKey)
    {
        $events = OrchestrationEvent::bySession($sessionKey)->get();

        return response()->json([
            'session_key' => $sessionKey,
            'event_count' => $events->count(),
            'events' => $events,
        ]);
    }

    public function timeline(Request $request)
    {
        $query = OrchestrationEvent::query();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        } else {
            $query->where('emitted_at', '>=', now()->subDays(7));
        }

        $events = $query->orderBy('emitted_at', 'asc')->get();

        $timeline = $events->groupBy(function ($event) {
            return $event->emitted_at->format('Y-m-d H:00');
        })->map(function ($hourEvents, $hour) {
            return [
                'hour' => $hour,
                'event_count' => $hourEvents->count(),
                'event_types' => $hourEvents->groupBy('event_type')->map->count(),
                'events' => $hourEvents,
            ];
        })->values();

        return response()->json([
            'timeline' => $timeline,
            'total_events' => $events->count(),
        ]);
    }

    public function stats(Request $request)
    {
        $baseQuery = OrchestrationEvent::query();

        if ($request->has('start_date') && $request->has('end_date')) {
            $baseQuery->byDateRange($request->start_date, $request->end_date);
        } else {
            $baseQuery->where('emitted_at', '>=', now()->subDays(30));
        }

        $eventsByType = (clone $baseQuery)->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->get()
            ->pluck('count', 'event_type');

        $eventsByEntity = (clone $baseQuery)->select('entity_type', DB::raw('count(*) as count'))
            ->groupBy('entity_type')
            ->get()
            ->pluck('count', 'entity_type');

        $eventsByActor = (clone $baseQuery)->select('agent_id', DB::raw('count(*) as count'))
            ->whereNotNull('agent_id')
            ->groupBy('agent_id')
            ->get()
            ->pluck('count', 'agent_id');

        $totalEvents = (clone $baseQuery)->count();

        return response()->json([
            'total_events' => $totalEvents,
            'by_type' => $eventsByType,
            'by_entity' => $eventsByEntity,
            'by_actor' => $eventsByActor,
            'date_range' => [
                'start' => $request->start_date ?? now()->subDays(30)->toIso8601String(),
                'end' => $request->end_date ?? now()->toIso8601String(),
            ],
        ]);
    }

    public function replay(Request $request)
    {
        $validated = $request->validate([
            'correlation_id' => 'required|string',
            'dry_run' => 'sometimes|boolean',
        ]);

        $result = $this->replayService->replayEvents(
            $validated['correlation_id'],
            $validated['dry_run'] ?? true
        );

        return response()->json($result);
    }

    public function sprintHistory(string $code)
    {
        $sprint = OrchestrationSprint::where('sprint_code', $code)->firstOrFail();
        
        $events = OrchestrationEvent::byEntity('sprint', $sprint->id)
            ->orderBy('emitted_at', 'desc')
            ->get();

        $stateHistory = [];
        foreach ($events as $event) {
            if (isset($event->payload['entity_snapshot'])) {
                $stateHistory[] = [
                    'timestamp' => $event->emitted_at->toIso8601String(),
                    'event_type' => $event->event_type,
                    'state' => $event->payload['entity_snapshot'],
                ];
            }
        }

        return response()->json([
            'sprint_code' => $code,
            'event_count' => $events->count(),
            'history' => $stateHistory,
        ]);
    }

    public function taskHistory(string $code)
    {
        $task = OrchestrationTask::where('task_code', $code)->firstOrFail();
        
        $events = OrchestrationEvent::byEntity('task', $task->id)
            ->orderBy('emitted_at', 'desc')
            ->get();

        $stateHistory = [];
        foreach ($events as $event) {
            if (isset($event->payload['entity_snapshot'])) {
                $stateHistory[] = [
                    'timestamp' => $event->emitted_at->toIso8601String(),
                    'event_type' => $event->event_type,
                    'state' => $event->payload['entity_snapshot'],
                ];
            }
        }

        return response()->json([
            'task_code' => $code,
            'event_count' => $events->count(),
            'history' => $stateHistory,
        ]);
    }
}
