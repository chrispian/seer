<?php

namespace App\Services;

use App\Models\Sprint;
use App\Models\SprintItem;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SprintOrchestrationService
{
    /**
     * Locate a sprint by code or UUID.
     */
    public function resolveSprint(string|Sprint $sprint): Sprint
    {
        if ($sprint instanceof Sprint) {
            return $sprint;
        }

        $identifier = trim($sprint);
        $query = Sprint::query();

        if (Str::isUuid($identifier)) {
            $model = $query->whereKey($identifier)->first();
        } else {
            $model = $query->where('code', $this->normaliseCode($identifier))->first();
        }

        if (! $model) {
            throw (new ModelNotFoundException())->setModel(Sprint::class, [$identifier]);
        }

        return $model;
    }

    /**
     * Create a sprint; optionally update when code already exists.
     */
    public function create(array $attributes, bool $updateIfExists = false): Sprint
    {
        $code = $this->normaliseCode($attributes['code'] ?? '');

        if ($code === '') {
            throw new InvalidArgumentException('Sprint code is required.');
        }

        $payload = $this->prepareAttributes($attributes, $code);

        if ($updateIfExists && Sprint::where('code', $code)->exists()) {
            return $this->update($code, $attributes);
        }

        return Sprint::create($payload);
    }

    /**
     * Update meta/dates for an existing sprint.
     */
    public function update(string|Sprint $sprint, array $attributes): Sprint
    {
        $sprint = $this->resolveSprint($sprint);
        $payload = $this->prepareAttributes($attributes, $sprint->code, $sprint);

        $sprint->fill($payload);
        $sprint->save();

        return $sprint->fresh();
    }

    /**
     * Update sprint status (stored in meta) and optionally append a note.
     */
    public function updateStatus(string|Sprint $sprint, string $status, ?string $note = null): Sprint
    {
        $sprint = $this->resolveSprint($sprint);
        $meta = $sprint->meta ?? [];
        $meta['status'] = Str::of($status)->trim()->lower()->headline();

        if ($note) {
            $notes = Arr::get($meta, 'notes', []);
            $notes[] = $note;
            $meta['notes'] = array_values(array_unique($notes));
        }

        $sprint->meta = $meta;
        $sprint->save();

        return $sprint->fresh();
    }

    /**
     * Attach one or more tasks to a sprint; returns summary payload.
     */
    public function attachTasks(string|Sprint $sprint, array $tasks, array $options = []): array
    {
        $sprint = $this->resolveSprint($sprint);
        $taskService = app(TaskOrchestrationService::class);
        $codes = array_values(array_filter(array_map(static fn ($task) => trim((string) $task), $tasks)));

        if ($codes === []) {
            throw new InvalidArgumentException('At least one task must be provided.');
        }

        DB::transaction(function () use ($sprint, $taskService, $codes) {
            $position = (int) SprintItem::where('sprint_id', $sprint->id)->max('position') + 1;

            foreach ($codes as $code) {
                $workItem = $taskService->resolveTask($code);

                $metadata = $workItem->metadata ?? [];
                $metadata['sprint_code'] = $sprint->code;
                $workItem->metadata = $metadata;

                $context = $workItem->delegation_context ?? [];
                $context['sprint_code'] = $sprint->code;
                $workItem->delegation_context = $context;
                $workItem->save();

                SprintItem::updateOrCreate(
                    ['sprint_id' => $sprint->id, 'work_item_id' => $workItem->id],
                    ['position' => $position++]
                );
            }
        });

        return $this->detail($sprint, $options);
    }

    /**
     * Retrieve a detailed snapshot for a sprint.
     */
    public function detail(string|Sprint $sprint, array $options = []): array
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
    public function summarise(Sprint $sprint, bool $withTasks = false, int $tasksLimit = 5, bool $includeAssignments = false): array
    {
        // Get tasks via sprint_items relationship AND legacy metadata approach
        $taskQuery = WorkItem::query()->where(function ($query) use ($sprint) {
            $query->where('metadata->sprint_code', $sprint->code)
                  ->orWhereHas('sprintItems', function ($subQuery) use ($sprint) {
                      $subQuery->where('sprint_id', $sprint->id);
                  });
        });

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

            $tasks = $taskCollection->map(function (WorkItem $item) use ($includeAssignments) {
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
                    'task_code' => Arr::get($metadata, 'task_code'),
                    'task_name' => Arr::get($metadata, 'task_name'),
                    'status' => $item->status,
                    'delegation_status' => $item->delegation_status,
                    'agent_recommendation' => Arr::get($context, 'agent_recommendation'),
                    'current_agent' => $assignment ? [
                        'id' => $assignment->agent?->id,
                        'name' => $assignment->agent?->name,
                        'slug' => $assignment->agent?->slug,
                        'status' => $assignment->status,
                    ] : null,
                    'estimate_text' => Arr::get($metadata, 'estimate_text'),
                    'todo_progress' => Arr::get($metadata, 'todo_progress', []),
                    'updated_at' => optional($item->updated_at)->toIso8601String(),
                ];
            })->values()->all();
        }

        $meta = $sprint->meta ?? [];

        return [
            'id' => $sprint->id,
            'code' => $sprint->code,
            'title' => Arr::get($meta, 'title', $sprint->code),
            'priority' => Arr::get($meta, 'priority'),
            'status' => Arr::get($meta, 'status'),
            'estimate' => Arr::get($meta, 'estimate'),
            'notes' => Arr::get($meta, 'notes', []),
            'starts_on' => optional($this->asCarbon($sprint->starts_on))->toDateString(),
            'ends_on' => optional($this->asCarbon($sprint->ends_on))->toDateString(),
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'blocked' => $blocked,
                'unassigned' => $unassigned,
            ],
            'tasks' => $tasks,
            'updated_at' => optional($this->asCarbon($sprint->updated_at))->toIso8601String(),
        ];
    }

    private function prepareAttributes(array $attributes, string $code, ?Sprint $existing = null): array
    {
        $payload = [
            'code' => $code,
        ];

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

        $meta = $existing?->meta ?? [];
        $meta = array_merge($meta, Arr::only($attributes, ['title', 'priority', 'estimate', 'notes', 'status']));

        if (isset($attributes['notes']) && is_string($attributes['notes'])) {
            $meta['notes'] = array_filter(array_map('trim', preg_split('/[\r\n]+/', $attributes['notes'])));
        }

        if (isset($attributes['notes']) && is_array($attributes['notes'])) {
            $meta['notes'] = array_values(array_filter($attributes['notes']));
        }

        if (! empty($attributes['meta']) && is_array($attributes['meta'])) {
            $meta = array_merge($meta, $attributes['meta']);
        }

        $payload['meta'] = $meta;

        return $payload;
    }

    private function normaliseCode(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        if (preg_match('/^\d+$/', $code)) {
            return 'SPRINT-' . str_pad($code, 2, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^(?:sprint-)?(\d+)$/i', $code, $matches)) {
            return 'SPRINT-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
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
