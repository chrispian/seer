<?php

namespace App\Services;

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
