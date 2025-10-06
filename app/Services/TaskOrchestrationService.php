<?php

namespace App\Services;

use App\Models\AgentProfile;
use App\Models\TaskAssignment;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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

    public function resolveTask(string|WorkItem $task): WorkItem
    {
        if ($task instanceof WorkItem) {
            return $task;
        }

        $identifier = trim($task);
        $query = WorkItem::query();

        if (Str::isUuid($identifier)) {
            $model = $query->whereKey($identifier)->first();
        } else {
            $model = $query->where('metadata->task_code', $identifier)
                ->orWhere('metadata->task_code', strtoupper($identifier))
                ->first();
        }

        if (! $model) {
            throw (new ModelNotFoundException())->setModel(WorkItem::class, [$identifier]);
        }

        return $model;
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
            throw (new ModelNotFoundException())->setModel(AgentProfile::class, [$identifier]);
        }

        return $model;
    }

    /**
     * Assign an agent to a task, creating a task assignment record.
     *
     * @param  array{status?:string, note?:string, context?:array, assignment_status?:string}  $options
     */
    public function assignAgent(string|WorkItem $task, string|AgentProfile $agent, array $options = []): array
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
    public function updateStatus(string|WorkItem $task, string $status, array $options = []): WorkItem
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

            return $task->fresh();
        });
    }

    /**
     * Produce a rich detail payload for a task.
     *
     * @param  array{assignments_limit?:int, include_history?:bool}  $options
     */
    public function detail(string|WorkItem $task, array $options = []): array
    {
        $task = $this->resolveTask($task);
        $limit = (int) ($options['assignments_limit'] ?? 10);
        $includeHistory = (bool) ($options['include_history'] ?? true);

        $task->loadMissing(['assignments' => function ($query) use ($limit) {
            $query->with('agent')->orderByDesc('assigned_at')->limit($limit);
        }, 'assignedAgent']);

        $current = $task->currentAssignment;

        return [
            'task' => [
                'id' => $task->id,
                'task_code' => Arr::get($task->metadata, 'task_code'),
                'task_name' => Arr::get($task->metadata, 'task_name'),
                'status' => $task->status,
                'delegation_status' => $task->delegation_status,
                'priority' => $task->priority,
                'metadata' => $task->metadata,
                'delegation_context' => $task->delegation_context,
                'delegation_history' => $includeHistory ? ($task->delegation_history ?? []) : null,
                'todo_progress' => Arr::get($task->metadata, 'todo_progress', []),
                'updated_at' => optional($task->updated_at)->toIso8601String(),
            ],
            'current_assignment' => $current ? $this->presentAssignment($current) : null,
            'assignments' => $task->assignments->map(fn (TaskAssignment $assignment) => $this->presentAssignment($assignment))->all(),
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

    private function appendHistory(WorkItem $task, array $entry): array
    {
        $history = $task->delegation_history ?? [];
        $history[] = array_merge($entry, [
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        return $history;
    }
}
