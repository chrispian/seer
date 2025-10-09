<?php

namespace App\Services;

use App\Enums\AgentStatus;
use App\Models\AgentProfile;
use App\Models\TaskAssignment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AgentOrchestrationService
{
    public function __construct(private readonly AgentProfileService $agents) {}

    public function resolveAgent(string|AgentProfile $agent): AgentProfile
    {
        if ($agent instanceof AgentProfile) {
            return $agent;
        }

        $identifier = trim($agent);

        if ($identifier === '') {
            throw (new ModelNotFoundException)->setModel(AgentProfile::class, [$identifier]);
        }

        if (Str::isUuid($identifier)) {
            $model = AgentProfile::query()->find($identifier);

            if ($model) {
                return $model;
            }
        }

        $model = $this->agents->findBySlug($identifier);

        if (! $model) {
            $model = AgentProfile::query()
                ->where('name', $identifier)
                ->orWhere('name', 'like', $identifier)
                ->first();
        }

        if (! $model) {
            throw (new ModelNotFoundException)->setModel(AgentProfile::class, [$identifier]);
        }

        return $model;
    }

    /**
     * Save an agent profile (create or update when upsert enabled).
     */
    public function save(array $attributes, bool $upsert = true): AgentProfile
    {
        $identifier = $attributes['id'] ?? $attributes['slug'] ?? null;
        $agent = null;

        if ($identifier) {
            try {
                $agent = $this->resolveAgent($identifier);
            } catch (ModelNotFoundException $e) {
                if (! $upsert) {
                    throw $e;
                }
            }
        }

        if ($agent) {
            return $this->agents->update($agent, $attributes);
        }

        if (! $upsert && $agent === null) {
            throw new InvalidArgumentException('Unable to locate agent for update.');
        }

        return $this->agents->create($attributes);
    }

    public function detail(string|AgentProfile $agent, array $options = []): array
    {
        $agent = $this->resolveAgent($agent);
        $limit = (int) ($options['assignments_limit'] ?? 10);
        $includeHistory = (bool) ($options['include_history'] ?? true);

        $recentAssignments = TaskAssignment::query()
            ->with(['workItem', 'agent'])
            ->where('agent_id', $agent->id)
            ->orderByDesc('assigned_at')
            ->limit($limit)
            ->get();

        $stats = [
            'assignments_total' => TaskAssignment::where('agent_id', $agent->id)->count(),
            'assignments_active' => TaskAssignment::where('agent_id', $agent->id)->active()->count(),
            'assignments_completed' => TaskAssignment::where('agent_id', $agent->id)->completed()->count(),
        ];

        return [
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'slug' => $agent->slug,
                'type' => $agent->type?->value,
                'mode' => $agent->mode?->value,
                'status' => $agent->status?->value,
                'description' => $agent->description,
                'capabilities' => $agent->capabilities ?? [],
                'constraints' => $agent->constraints ?? [],
                'tools' => $agent->tools ?? [],
                'metadata' => $agent->metadata ?? [],
                'updated_at' => optional($agent->updated_at)->toIso8601String(),
            ],
            'stats' => $stats,
            'recent_assignments' => $recentAssignments->map(function (TaskAssignment $assignment) use ($includeHistory) {
                return [
                    'id' => $assignment->id,
                    'work_item_id' => $assignment->work_item_id,
                    'work_item_code' => Arr::get($assignment->workItem?->metadata, 'task_code'),
                    'status' => $assignment->status,
                    'assigned_at' => optional($assignment->assigned_at)->toIso8601String(),
                    'started_at' => optional($assignment->started_at)->toIso8601String(),
                    'completed_at' => optional($assignment->completed_at)->toIso8601String(),
                    'notes' => $assignment->notes,
                    'context' => $assignment->context,
                    'history' => $includeHistory ? $assignment->workItem?->delegation_history ?? [] : null,
                ];
            })->all(),
        ];
    }

    public function setStatus(string|AgentProfile $agent, string $status): AgentProfile
    {
        $agent = $this->resolveAgent($agent);
        $status = Str::of($status)->lower()->value();

        return match ($status) {
            'active' => $this->agents->activate($agent),
            'archived' => $this->agents->archive($agent),
            'inactive' => $this->agents->update($agent, ['status' => AgentStatus::Inactive->value]),
            default => throw new InvalidArgumentException(sprintf('Unsupported agent status [%s].', $status)),
        };
    }
}
