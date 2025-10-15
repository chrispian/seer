<?php

namespace App\Services;

use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SprintOrchestrationService
{
    /**
     * Locate a sprint by code or ID.
     */
    public function resolveSprint(string|int|OrchestrationSprint $sprint): OrchestrationSprint
    {
        if ($sprint instanceof OrchestrationSprint) {
            return $sprint;
        }

        $identifier = is_string($sprint) ? trim($sprint) : $sprint;
        $query = OrchestrationSprint::query();

        if (is_numeric($identifier)) {
            $model = $query->whereKey($identifier)->first();
        } else {
            $model = $query->where('sprint_code', $this->normaliseCode($identifier))->first();
        }

        if (! $model) {
            throw (new ModelNotFoundException)->setModel(OrchestrationSprint::class, [$identifier]);
        }

        return $model;
    }

    /**
     * Create a sprint; optionally update when code already exists.
     */
    public function create(array $attributes, bool $updateIfExists = false): OrchestrationSprint
    {
        $code = $this->normaliseCode($attributes['code'] ?? $attributes['sprint_code'] ?? '');

        if ($code === '') {
            throw new InvalidArgumentException('Sprint code is required.');
        }

        $payload = $this->prepareAttributes($attributes, $code);

        if ($updateIfExists && OrchestrationSprint::where('sprint_code', $code)->exists()) {
            return $this->update($code, $attributes);
        }

        return OrchestrationSprint::create($payload);
    }

    /**
     * Update metadata/dates for an existing sprint.
     */
    public function update(string|int|OrchestrationSprint $sprint, array $attributes): OrchestrationSprint
    {
        $sprint = $this->resolveSprint($sprint);
        $payload = $this->prepareAttributes($attributes, $sprint->sprint_code, $sprint);

        $sprint->fill($payload);
        $sprint->save();

        return $sprint->fresh();
    }

    /**
     * Update sprint status and optionally append a note.
     */
    public function updateStatus(string|int|OrchestrationSprint $sprint, string $status, ?string $note = null): OrchestrationSprint
    {
        $sprint = $this->resolveSprint($sprint);
        $sprint->status = Str::of($status)->trim()->lower();

        if ($note) {
            $metadata = $sprint->metadata ?? [];
            $notes = Arr::get($metadata, 'notes', []);
            $notes[] = $note;
            $metadata['notes'] = array_values(array_unique($notes));
            $sprint->metadata = $metadata;
        }

        $sprint->save();

        return $sprint->fresh();
    }

    /**
     * Attach one or more tasks to a sprint; returns summary payload.
     */
    public function attachTasks(string|int|OrchestrationSprint $sprint, array $tasks, array $options = []): array
    {
        $sprint = $this->resolveSprint($sprint);
        $taskService = app(TaskOrchestrationService::class);
        $codes = array_values(array_filter(array_map(static fn ($task) => trim((string) $task), $tasks)));

        if ($codes === []) {
            throw new InvalidArgumentException('At least one task must be provided.');
        }

        DB::transaction(function () use ($sprint, $taskService, $codes) {
            foreach ($codes as $code) {
                $task = $taskService->resolveTask($code);
                
                $task->sprint_id = $sprint->id;
                
                $context = $task->delegation_context ?? [];
                $context['sprint_code'] = $sprint->sprint_code;
                $task->delegation_context = $context;
                
                $task->save();
            }
        });

        return $this->detail($sprint, $options);
    }

    /**
     * Retrieve a detailed snapshot for a sprint.
     */
    public function detail(string|int|OrchestrationSprint $sprint, array $options = []): array
    {
        $sprint = $this->resolveSprint($sprint);
        $withTasks = (bool) ($options['include_tasks'] ?? true);
        $tasksLimit = (int) ($options['tasks_limit'] ?? 10);
        $includeAssignments = (bool) ($options['include_assignments'] ?? false);

        $summary = $this->summarise($sprint, $withTasks, $tasksLimit, $includeAssignments);

        return [
            'sprint' => $summary,
        ];
    }

    /**
     * Generate sprint summary identical to list output but reusable.
     */
    public function summarise(OrchestrationSprint $sprint, bool $withTasks = false, int $tasksLimit = 5, bool $includeAssignments = false): array
    {
        // Get tasks by sprint_id
        $taskQuery = OrchestrationTask::query()->where('sprint_id', $sprint->id);

        $total = (clone $taskQuery)->count();
        $completed = (clone $taskQuery)->where('delegation_status', 'completed')->count();
        $inProgress = (clone $taskQuery)->whereIn('delegation_status', ['assigned', 'in_progress'])->count();
        $blocked = (clone $taskQuery)->where('delegation_status', 'blocked')->count();
        $unassigned = (clone $taskQuery)->where('delegation_status', 'unassigned')->count();

        $tasks = [];

        if ($withTasks && $total > 0) {
            $taskCollection = (clone $taskQuery)
                ->orderByDesc('created_at')
                ->limit($tasksLimit)
                ->get();

            if ($includeAssignments) {
                $taskCollection->load(['currentAssignment.agent']);
            }

            $tasks = $taskCollection->map(function (OrchestrationTask $item) use ($includeAssignments) {
                $metadata = $item->metadata ?? [];
                $context = $item->delegation_context ?? [];
                $assignment = null;

                if ($includeAssignments) {
                    if ($item->relationLoaded('currentAssignment')) {
                        $assignment = $item->getRelation('currentAssignment');
                    } else {
                        $assignment = $item->currentAssignment()->with('agent')->first();
                    }
                }

                return [
                    'id' => $item->id,
                    'task_code' => $item->task_code,
                    'task_name' => $item->title,
                    'status' => $item->status,
                    'delegation_status' => $item->delegation_status,
                    'agent_recommendation' => Arr::get($context, 'agent_recommendation'),
                    'current_agent' => $assignment ? [
                        'id' => $assignment->agent?->id,
                        'name' => $assignment->agent?->name,
                        'slug' => $assignment->agent?->slug,
                        'status' => $assignment->status,
                    ] : null,
                    'estimate_text' => $item->estimated_hours ? "{$item->estimated_hours}h" : null,
                    'estimated_hours' => $item->estimated_hours,
                    'todo_progress' => Arr::get($metadata, 'todo_progress', []),
                    'updated_at' => optional($item->updated_at)->toIso8601String(),
                ];
            })->values()->all();
        }

        $metadata = $sprint->metadata ?? [];

        return [
            'id' => $sprint->id,
            'code' => $sprint->sprint_code,
            'sprint_code' => $sprint->sprint_code,
            'title' => $sprint->title,
            'owner' => $sprint->owner,
            'priority' => Arr::get($metadata, 'priority'),
            'status' => $sprint->status ?? 'planning',
            'estimate' => Arr::get($metadata, 'estimate'),
            'notes' => Arr::get($metadata, 'notes', []),
            'starts_on' => $sprint->starts_on?->toDateString(),
            'ends_on' => $sprint->ends_on?->toDateString(),
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'blocked' => $blocked,
                'unassigned' => $unassigned,
            ],
            'tasks' => $tasks,
            'updated_at' => optional($sprint->updated_at)->toIso8601String(),
        ];
    }

    private function prepareAttributes(array $attributes, string $code, ?OrchestrationSprint $existing = null): array
    {
        $payload = [
            'sprint_code' => $code,
        ];

        // Title is now a direct field
        if (array_key_exists('title', $attributes)) {
            $payload['title'] = $attributes['title'];
        } elseif ($existing) {
            $payload['title'] = $existing->title;
        } else {
            $payload['title'] = $code;
        }

        // Owner
        if (array_key_exists('owner', $attributes)) {
            $payload['owner'] = $attributes['owner'];
        } elseif ($existing) {
            $payload['owner'] = $existing->owner;
        }

        // Status is now a direct field
        if (array_key_exists('status', $attributes)) {
            $payload['status'] = $attributes['status'];
        } elseif ($existing) {
            $payload['status'] = $existing->status;
        }

        // Dates
        if (array_key_exists('starts_on', $attributes)) {
            $payload['starts_on'] = $this->normaliseDate($attributes['starts_on']);
        } elseif ($existing) {
            $payload['starts_on'] = $existing->starts_on;
        }

        if (array_key_exists('ends_on', $attributes)) {
            $payload['ends_on'] = $this->normaliseDate($attributes['ends_on']);
        } elseif ($existing) {
            $payload['ends_on'] = $existing->ends_on;
        }

        // Everything else goes in metadata
        $metadata = $existing?->metadata ?? [];
        $metadata = array_merge($metadata, Arr::only($attributes, ['priority', 'estimate', 'notes']));

        if (isset($attributes['notes']) && is_string($attributes['notes'])) {
            $metadata['notes'] = array_filter(array_map('trim', preg_split('/[\r\n]+/', $attributes['notes'])));
        }

        if (isset($attributes['notes']) && is_array($attributes['notes'])) {
            $metadata['notes'] = array_values(array_filter($attributes['notes']));
        }

        if (! empty($attributes['metadata']) && is_array($attributes['metadata'])) {
            $metadata = array_merge($metadata, $attributes['metadata']);
        }

        $payload['metadata'] = $metadata;

        return $payload;
    }

    private function normaliseCode(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        if (preg_match('/^\d+$/', $code)) {
            return 'SPRINT-'.str_pad($code, 2, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^(?:sprint-)?(\d+)$/i', $code, $matches)) {
            return 'SPRINT-'.str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }

        return Str::upper($code);
    }

    private function normaliseDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        $date = Carbon::parse($value);

        return $date->toDateString();
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
