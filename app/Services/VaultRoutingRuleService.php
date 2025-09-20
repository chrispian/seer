<?php

namespace App\Services;

use App\Models\Fragment;
use App\Models\Project;
use App\Models\Vault;
use App\Models\VaultRoutingRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VaultRoutingRuleService
{
    public function list(array $filters = []): Collection
    {
        $query = VaultRoutingRule::query()
            ->with(['targetVault', 'targetProject', 'contextVault', 'contextProject'])
            ->orderBy('priority')
            ->orderBy('name');

        if ($filters['active_only'] ?? false) {
            $query->where('is_active', true);
        }

        if ($filters['scope_vault_id'] ?? null) {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('scope_vault_id', $filters['scope_vault_id'])
                    ->orWhereNull('scope_vault_id');
            });
        }

        if ($filters['scope_project_id'] ?? null) {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('scope_project_id', $filters['scope_project_id'])
                    ->orWhereNull('scope_project_id');
            });
        }

        return $query->get();
    }

    public function create(array $attributes): VaultRoutingRule
    {
        return VaultRoutingRule::create($this->normalize($attributes));
    }

    public function update(VaultRoutingRule $rule, array $attributes): VaultRoutingRule
    {
        $rule->fill($this->normalize($attributes));
        $rule->save();

        return $rule->refresh();
    }

    public function delete(VaultRoutingRule $rule): void
    {
        $rule->delete();
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $position => $ruleId) {
                VaultRoutingRule::whereKey($ruleId)->update(['priority' => $position + 1]);
            }
        });
    }

    /**
     * Resolve routing target for a fragment based on active rules.
     */
    public function resolveForFragment(Fragment $fragment): ?array
    {
        // Get rules that could apply to this fragment's context
        $applicableRules = $this->list([
            'active_only' => true,
            'scope_vault_id' => $fragment->vault ?? null,
            'scope_project_id' => $fragment->project_id ?? null,
        ]);

        // Find the first matching rule (rules are already ordered by priority)
        foreach ($applicableRules as $rule) {
            if ($this->fragmentMatchesRule($fragment, $rule)) {
                return $this->buildRoutingTarget($rule);
            }
        }

        return null;
    }

    /**
     * Check if a fragment matches a routing rule.
     */
    protected function fragmentMatchesRule(Fragment $fragment, VaultRoutingRule $rule): bool
    {
        if (empty($rule->match_value)) {
            return false;
        }

        $content = $fragment->message ?? '';
        $fragmentType = $fragment->type?->value ?? $fragment->type ?? '';

        return match (strtolower($rule->match_type)) {
            'keyword' => stripos($content, $rule->match_value) !== false,
            'tag' => in_array($rule->match_value, $fragment->tags ?? []),
            'type' => $fragmentType === $rule->match_value,
            'regex' => preg_match('/'.$rule->match_value.'/i', $content),
            default => false,
        };
    }

    /**
     * Build routing target from a rule.
     */
    protected function buildRoutingTarget(VaultRoutingRule $rule): array
    {
        $target = [];

        if ($rule->target_vault_id) {
            $vault = Vault::find($rule->target_vault_id);
            if ($vault) {
                $target['vault'] = $vault->name;
                $target['vault_id'] = $vault->id;
            }
        }

        if ($rule->target_project_id) {
            $project = Project::find($rule->target_project_id);
            if ($project) {
                $target['project_id'] = $project->id;
            }
        } elseif (isset($target['vault_id'])) {
            // If rule specifies vault but no project, use default project for that vault
            $defaultProject = Project::getDefaultForVault($target['vault_id']);
            if ($defaultProject) {
                $target['project_id'] = $defaultProject->id;
            }
        }

        return $target;
    }

    protected function normalize(array $attributes): array
    {
        $normalized = Arr::only($attributes, [
            'name',
            'match_type',
            'match_value',
            'conditions',
            'target_vault_id',
            'target_project_id',
            'scope_vault_id',
            'scope_project_id',
            'priority',
            'is_active',
            'notes',
        ]);

        if (array_key_exists('conditions', $normalized)) {
            $conditions = $normalized['conditions'];

            if (is_string($conditions)) {
                $decoded = json_decode($conditions, true);
                $normalized['conditions'] = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }
        }

        if (isset($normalized['match_type'])) {
            $normalized['match_type'] = strtolower($normalized['match_type']);
        }

        $normalized['priority'] = (int) ($normalized['priority'] ?? 100);

        if (array_key_exists('is_active', $normalized)) {
            $normalized['is_active'] = filter_var($normalized['is_active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return $normalized;
    }
}
