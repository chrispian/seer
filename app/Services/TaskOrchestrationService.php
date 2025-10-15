<?php

namespace App\Services;

use App\Models\AgentProfile;
use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Models\TaskActivity;
use App\Models\TaskAssignment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TaskOrchestrationService
{
    private const DELEGATION_STATUSES = [
        'unassigned',
        'assigned',
        'in_progress',
        'blocked',
        'completed',
        'cancelled',
    ];

    private const ASSIGNMENT_STATUS_MAP = [
        'unassigned' => 'cancelled',
        'assigned' => 'assigned',
        'in_progress' => 'started',
        'blocked' => 'paused',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ];

    public function create(array $data, bool $upsert = true): OrchestrationTask
    {
        $taskCode = $data['task_code'] ?? null;

        if (! $taskCode) {
            throw new InvalidArgumentException('task_code is required');
        }

        $existing = null;
        if ($upsert) {
            $existing = OrchestrationTask::query()
                ->where('task_code', $taskCode)
                ->first();
        }

        if ($existing && ! $upsert) {
            throw new InvalidArgumentException("Task with code [{$taskCode}] already exists");
        }

        $model = $existing ?? new OrchestrationTask;
        $model->task_code = $taskCode;
        $model->type = $data['type'] ?? 'task';
        $model->status = $data['status'] ?? 'todo';
        $model->priority = $data['priority'] ?? 'medium';
        $model->delegation_status = $data['delegation_status'] ?? 'unassigned';
        $model->title = $data['task_name'] ?? $data['title'] ?? null;
        $model->description = $data['description'] ?? null;

        if (isset($data['tags'])) {
            $model->tags = is_array($data['tags']) ? $data['tags'] : [$data['tags']];
        } elseif (! $existing) {
            $model->tags = ['orchestration'];
        }

        if (isset($data['sprint_code'])) {
            $sprint = OrchestrationSprint::where('sprint_code', $data['sprint_code'])->first();
            if ($sprint) {
                $model->sprint_id = $sprint->id;
            }
        }

        if (isset($data['estimated_hours'])) {
            $model->estimated_hours = $data['estimated_hours'];
        } elseif (isset($data['estimate_text'])) {
            $hours = (float) preg_replace('/[^0-9.]/', '', $data['estimate_text']);
            $model->estimated_hours = $hours > 0 ? $hours : null;
        }

        $metadata = $model->metadata ?? [];
        if (isset($data['dependencies'])) {
            $metadata['dependencies'] = is_array($data['dependencies'])
                ? $data['dependencies']
                : [$data['dependencies']];
        }

        if (isset($data['acceptance'])) {
            $metadata['acceptance'] = $data['acceptance'];
        }

        $model->metadata = $metadata;

        if (isset($data['agent_content'])) {
            $model->agent_content = $data['agent_content'];
        }

        if (isset($data['estimated_hours'])) {
            $model->estimated_hours = $data['estimated_hours'];
        }

        $model->save();

        return $model->fresh();
    }

    public function resolveTask(string|OrchestrationTask $task): OrchestrationTask
    {
        if ($task instanceof OrchestrationTask) {
            return $task;
        }

        $identifier = trim($task);
        $model = null;

        // Priority 1: Row ID (numeric)
        if (is_numeric($identifier)) {
            $model = OrchestrationTask::where('id', $identifier)->first();
            if ($model) {
                return $model;
            }
        }

        // Priority 2: Task Code (exact match)
        $model = OrchestrationTask::where('task_code', $identifier)
            ->orWhere('task_code', strtoupper($identifier))
            ->first();
        if ($model) {
            return $model;
        }

        // Priority 3: UUID (if used as ID)
        if (Str::isUuid($identifier)) {
            $model = OrchestrationTask::where('id', $identifier)->first();
            if ($model) {
                return $model;
            }
        }

        // Priority 4: Fuzzy matching - find similar task codes
        $similarTasks = OrchestrationTask::where('task_code', 'like', "%{$identifier}%")
            ->limit(10)
            ->get(['id', 'task_code', 'title']);

        if ($similarTasks->isNotEmpty()) {
            // Return multiple matches for user to select
            $exception = new ModelNotFoundException;
            $exception->setModel(OrchestrationTask::class, [$identifier]);
            $exception->similarMatches = $similarTasks->map(fn($t) => [
                'id' => $t->id,
                'task_code' => $t->task_code,
                'task_name' => $t->title,
            ])->toArray();
            throw $exception;
        }

        throw (new ModelNotFoundException)->setModel(OrchestrationTask::class, [$identifier]);
    }

    public function resolveAgent(string|AgentProfile $agent): AgentProfile
    {
        if ($agent instanceof AgentProfile) {
            return $agent;
        }

        $identifier = trim($agent);
        $query = AgentProfile::query();

        if (Str::isUuid($identifier)) {
            $model = $query->whereKey($identifier)->first();
        } else {
            $model = $query->where('slug', $identifier)
                ->orWhere('name', $identifier)
                ->first();
        }

        if (! $model) {
            throw (new ModelNotFoundException)->setModel(AgentProfile::class, [$identifier]);
        }

        return $model;
    }

    /**
     * Assign an agent to a task, creating a task assignment record.
     *
     * @param  array{status?:string, note?:string, context?:array, assignment_status?:string}  $options
     */
    public function assignAgent(string|OrchestrationTask $task, string|AgentProfile $agent, array $options = []): array
    {
        $task = $this->resolveTask($task);
        $agent = $this->resolveAgent($agent);

        $delegationStatus = $this->normaliseDelegationStatus($options['status'] ?? 'assigned');
        $assignmentStatus = $this->normaliseAssignmentStatus($options['assignment_status'] ?? $delegationStatus);
        $note = $options['note'] ?? null;
        $context = $options['context'] ?? null;

        return DB::transaction(function () use ($task, $agent, $delegationStatus, $assignmentStatus, $note, $context) {
            $now = Carbon::now();

            // Close any active assignments before creating a new one.
            $task->assignments()->active()->update([
                'status' => 'cancelled',
                'completed_at' => $now,
            ]);

            $assignment = $task->assignments()->create([
                'agent_id' => $agent->id,
                'assigned_by' => null,
                'assigned_at' => $now,
                'status' => $assignmentStatus,
                'notes' => $note,
                'context' => $context,
            ]);

            if ($assignmentStatus === 'started' && ! $assignment->started_at) {
                $assignment->started_at = $now;
            }

            if (in_array($assignmentStatus, ['completed', 'cancelled'], true) && ! $assignment->completed_at) {
                $assignment->completed_at = $now;
            }

            $assignment->save();

            $task->assignee_type = 'agent';
            $task->assignee_id = $agent->id;
            $task->delegation_status = $delegationStatus;

            $contextPayload = $task->delegation_context ?? [];
            $contextPayload = array_merge($contextPayload, [
                'agent_recommendation' => $agent->slug,
                'assigned_agent' => [
                    'id' => $agent->id,
                    'slug' => $agent->slug,
                    'name' => $agent->name,
                ],
                'last_assignment_id' => $assignment->id,
                'last_assignment_status' => $assignment->status,
                'last_assigned_at' => $now->toIso8601String(),
            ]);

            $task->delegation_context = $contextPayload;
            $task->delegation_history = $this->appendHistory($task, [
                'action' => 'assigned',
                'assignment_id' => $assignment->id,
                'agent_id' => $agent->id,
                'agent_slug' => $agent->slug,
                'status' => $delegationStatus,
                'note' => $note,
            ]);

            $task->save();

            TaskActivity::logAssignment(
                taskId: $task->id,
                agentId: $agent->id,
                description: "Task assigned to {$agent->name}",
                metadata: [
                    'agent_slug' => $agent->slug,
                    'assignment_id' => $assignment->id,
                    'note' => $note,
                ]
            );

            return [
                'task' => $task->fresh(),
                'assignment' => $assignment->fresh(),
            ];
        });
    }

    /**
     * Update delegation status for a task and optionally reflect on the current assignment.
     *
     * @param  array{note?:string, assignment_status?:string}  $options
     */
    public function updateStatus(string|OrchestrationTask $task, string $status, array $options = []): OrchestrationTask
    {
        $task = $this->resolveTask($task);
        $delegationStatus = $this->normaliseDelegationStatus($status);
        $assignmentStatus = $this->normaliseAssignmentStatus($options['assignment_status'] ?? $delegationStatus);
        $note = $options['note'] ?? null;

        return DB::transaction(function () use ($task, $delegationStatus, $assignmentStatus, $note) {
            $now = Carbon::now();

            $current = $task->currentAssignment;
            if ($current) {
                $current->status = $assignmentStatus;

                if ($assignmentStatus === 'started' && ! $current->started_at) {
                    $current->started_at = $now;
                }

                if (in_array($assignmentStatus, ['completed', 'cancelled'], true) && ! $current->completed_at) {
                    $current->completed_at = $now;
                }

                $current->save();
            }

            $task->delegation_status = $delegationStatus;

            if ($delegationStatus === 'unassigned') {
                $task->assignee_type = null;
                $task->assignee_id = null;
            }

            $oldStatus = $task->delegation_status;

            $task->delegation_history = $this->appendHistory($task, [
                'action' => 'status_changed',
                'status' => $delegationStatus,
                'assignment_id' => $current?->id,
                'note' => $note,
            ]);

            if ($note) {
                $context = $task->delegation_context ?? [];
                $context['last_status_note'] = $note;
                $task->delegation_context = $context;
            }

            $task->save();

            TaskActivity::logStatusChange(
                taskId: $task->id,
                fromStatus: $oldStatus ?? 'unknown',
                toStatus: $delegationStatus,
                agentId: $current?->agent_id,
                description: $note
            );

            return $task->fresh();
        });
    }

    /**
     * Produce a rich detail payload for a task.
     *
     * @param  array{assignments_limit?:int, include_history?:bool}  $options
     */
    public function detail(string|OrchestrationTask $task, array $options = []): array
    {
        $task = $this->resolveTask($task);
        $limit = (int) ($options['assignments_limit'] ?? 10);
        $includeHistory = (bool) ($options['include_history'] ?? true);

        $sprintCode = null;
        if ($task->sprint_id) {
            $sprint = OrchestrationSprint::find($task->sprint_id);
            $sprintCode = $sprint?->sprint_code;
        }

        $agentName = null;
        if ($task->assignee_id) {
            if ($task->assignee_type && strtolower($task->assignee_type) === 'agent') {
                $agent = AgentProfile::find($task->assignee_id);
                $agentName = $agent?->name;
            }
        }

        // Load assignments
        $currentAssignment = $task->currentAssignment;
        $assignments = $task->assignments()
            ->with('agent')
            ->orderBy('assigned_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($a) => $this->presentAssignment($a))
            ->all();

        // Load activities
        $activities = $task->activities()
            ->with(['agent', 'user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'task_id' => $activity->task_id,
                    'agent_id' => $activity->agent_id,
                    'user_id' => $activity->user_id,
                    'activity_type' => $activity->activity_type,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'changes' => $activity->changes,
                    'metadata' => $activity->metadata,
                    'created_at' => $activity->created_at->toIso8601String(),
                    'agent' => $activity->agent ? [
                        'id' => $activity->agent->id,
                        'name' => $activity->agent->name,
                        'slug' => $activity->agent->slug ?? null,
                    ] : null,
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                    ] : null,
                ];
            })
            ->all();

        return [
            'task' => [
                'id' => $task->id,
                'task_code' => $task->task_code,
                'task_name' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'delegation_status' => $task->delegation_status ?? 'unassigned',
                'priority' => $task->priority,
                'sprint_code' => $sprintCode,
                'assignee_id' => $task->assignee_id,
                'assignee_name' => $agentName,
                'assignee_type' => $task->assignee_type,
                'estimate_text' => $task->estimated_hours ? $task->estimated_hours . ' hours' : null,
                'tags' => $task->tags ?? [],
                'metadata' => $task->metadata ?? [],
                'delegation_context' => $task->delegation_context ?? [],
                'delegation_history' => $includeHistory ? ($task->delegation_history ?? []) : null,
                'updated_at' => optional($task->updated_at)->toIso8601String(),
                'created_at' => optional($task->created_at)->toIso8601String(),
                'completed_at' => optional($task->completed_at)->toIso8601String(),
            ],
            'current_assignment' => $currentAssignment ? $this->presentAssignment($currentAssignment) : null,
            'assignments' => $assignments,
            'content' => [
                'agent' => $task->agent_content ?? null,
                'plan' => $task->plan_content ?? null,
                'context' => $task->context_content ?? null,
                'todo' => $task->todo_content ?? null,
                'summary' => $task->summary_content ?? null,
            ],
            'activities' => $activities,
        ];
    }

    private function presentAssignment(TaskAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'agent_id' => $assignment->agent_id,
            'agent_name' => $assignment->agent?->name,
            'agent_slug' => $assignment->agent?->slug,
            'status' => $assignment->status,
            'assigned_at' => optional($assignment->assigned_at)->toIso8601String(),
            'started_at' => optional($assignment->started_at)->toIso8601String(),
            'completed_at' => optional($assignment->completed_at)->toIso8601String(),
            'notes' => $assignment->notes,
            'context' => $assignment->context,
        ];
    }

    private function normaliseDelegationStatus(string $status): string
    {
        $normalised = Str::of($status)->lower()->replace('-', '_')->value();

        if (! in_array($normalised, self::DELEGATION_STATUSES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid delegation status [%s].', $status));
        }

        return $normalised;
    }

    private function normaliseAssignmentStatus(string $status): string
    {
        $delegation = $this->normaliseDelegationStatus($status);

        return self::ASSIGNMENT_STATUS_MAP[$delegation] ?? 'assigned';
    }

    private function appendHistory(OrchestrationTask $task, array $entry): array
    {
        $history = $task->delegation_history ?? [];
        $history[] = array_merge($entry, [
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        return $history;
    }
}
