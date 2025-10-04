<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ScheduleRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Get all schedules with status information
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->get('status');
            $limit = $request->get('limit', 50);

            $query = Schedule::query()->with(['runs' => function ($query) {
                $query->latest()->limit(5);
            }]);

            if ($status) {
                $query->where('status', $status);
            }

            $schedules = $query->orderBy('next_run_at', 'asc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            $schedules = $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'command_slug' => $schedule->command_slug,
                    'status' => $schedule->status,
                    'recurrence_type' => $schedule->recurrence_type,
                    'recurrence_value' => $schedule->recurrence_value,
                    'timezone' => $schedule->timezone,
                    'next_run_at' => $schedule->next_run_at,
                    'last_run_at' => $schedule->last_run_at,
                    'run_count' => $schedule->run_count,
                    'max_runs' => $schedule->max_runs,
                    'is_due' => $schedule->isDue(),
                    'is_locked' => $schedule->isLocked(),
                    'recent_runs' => $schedule->runs->map(function ($run) {
                        return [
                            'id' => $run->id,
                            'status' => $run->status,
                            'planned_run_at' => $run->planned_run_at,
                            'started_at' => $run->started_at,
                            'completed_at' => $run->completed_at,
                            'duration_ms' => $run->duration_ms,
                            'error_message' => $run->error_message,
                        ];
                    }),
                    'created_at' => $schedule->created_at,
                    'updated_at' => $schedule->updated_at,
                ];
            });

            return response()->json([
                'data' => $schedules,
                'total' => $schedules->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch schedules',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get schedule statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Schedule::count(),
                'active' => Schedule::active()->count(),
                'due' => Schedule::due()->count(),
                'locked' => Schedule::whereNotNull('locked_at')
                    ->where('locked_at', '>', now()->subMinutes(5))
                    ->count(),
                'by_status' => Schedule::selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'recent_runs' => [
                    'total_today' => ScheduleRun::whereDate('created_at', today())->count(),
                    'completed_today' => ScheduleRun::whereDate('created_at', today())
                        ->where('status', 'completed')
                        ->count(),
                    'failed_today' => ScheduleRun::whereDate('created_at', today())
                        ->where('status', 'failed')
                        ->count(),
                    'running_now' => ScheduleRun::where('status', 'running')->count(),
                ],
                'next_runs' => Schedule::active()
                    ->whereNotNull('next_run_at')
                    ->orderBy('next_run_at', 'asc')
                    ->limit(5)
                    ->get(['id', 'name', 'command_slug', 'next_run_at'])
                    ->map(function ($schedule) {
                        return [
                            'id' => $schedule->id,
                            'name' => $schedule->name,
                            'command_slug' => $schedule->command_slug,
                            'next_run_at' => $schedule->next_run_at,
                            'time_until' => $schedule->next_run_at ?
                                $schedule->next_run_at->diffForHumans() : null,
                        ];
                    }),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch schedule statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific schedule
     */
    public function show(int $id): JsonResponse
    {
        try {
            $schedule = Schedule::with(['runs' => function ($query) {
                $query->latest()->limit(20);
            }])->findOrFail($id);

            return response()->json([
                'id' => $schedule->id,
                'name' => $schedule->name,
                'command_slug' => $schedule->command_slug,
                'payload' => $schedule->payload,
                'status' => $schedule->status,
                'recurrence_type' => $schedule->recurrence_type,
                'recurrence_value' => $schedule->recurrence_value,
                'timezone' => $schedule->timezone,
                'next_run_at' => $schedule->next_run_at,
                'last_run_at' => $schedule->last_run_at,
                'run_count' => $schedule->run_count,
                'max_runs' => $schedule->max_runs,
                'is_due' => $schedule->isDue(),
                'is_locked' => $schedule->isLocked(),
                'locked_at' => $schedule->locked_at,
                'lock_owner' => $schedule->lock_owner,
                'last_tick_at' => $schedule->last_tick_at,
                'runs' => $schedule->runs->map(function ($run) {
                    return [
                        'id' => $run->id,
                        'status' => $run->status,
                        'planned_run_at' => $run->planned_run_at,
                        'started_at' => $run->started_at,
                        'completed_at' => $run->completed_at,
                        'duration_ms' => $run->duration_ms,
                        'output' => $run->output,
                        'error_message' => $run->error_message,
                        'created_at' => $run->created_at,
                    ];
                }),
                'created_at' => $schedule->created_at,
                'updated_at' => $schedule->updated_at,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch schedule details',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get recent schedule runs across all schedules
     */
    public function runs(Request $request): JsonResponse
    {
        try {
            $status = $request->get('status');
            $limit = $request->get('limit', 25);

            $query = ScheduleRun::with('schedule:id,name,command_slug');

            if ($status) {
                $query->where('status', $status);
            }

            $runs = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($run) {
                    return [
                        'id' => $run->id,
                        'schedule_id' => $run->schedule_id,
                        'schedule_name' => $run->schedule->name ?? 'Unknown',
                        'command_slug' => $run->schedule->command_slug ?? 'Unknown',
                        'status' => $run->status,
                        'planned_run_at' => $run->planned_run_at,
                        'started_at' => $run->started_at,
                        'completed_at' => $run->completed_at,
                        'duration_ms' => $run->duration_ms,
                        'error_message' => $run->error_message,
                        'created_at' => $run->created_at,
                    ];
                });

            return response()->json([
                'data' => $runs,
                'total' => $runs->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch schedule runs',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
