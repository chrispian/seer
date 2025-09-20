<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Project;
use App\Models\Vault;
use App\Services\VaultRoutingRuleService;
use Illuminate\Support\Str;

class RoutingCommand implements HandlesCommand
{
    public function __construct(private readonly VaultRoutingRuleService $routingRules)
    {
    }

    public function handle(CommandRequest $command): CommandResponse
    {
        $filters = $this->buildFilters($command);

        $rules = $this->routingRules->list($filters)
            ->map(fn ($rule) => $rule->toArray())
            ->values()
            ->all();

        $panelData = [
            'rules' => $rules,
            'vaults' => Vault::query()->orderBy('name')->get(['id', 'name'])->toArray(),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'vault_id'])->toArray(),
            'filters' => $filters,
        ];

        return new CommandResponse(
            type: 'routing',
            shouldOpenPanel: true,
            panelData: $panelData,
            message: 'Routing rules ready. Use the panel to manage thresholds and destinations.',
        );
    }

    private function buildFilters(CommandRequest $command): array
    {
        $filters = [];

        $activeOnly = $command->arguments['active'] ?? $command->arguments['active_only'] ?? null;
        if (! is_null($activeOnly)) {
            $filters['active_only'] = filter_var($activeOnly, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        $currentVaultId = $command->arguments['vault_id'] ?? null;
        $currentProjectId = $command->arguments['project_id'] ?? null;

        if ($currentVaultId) {
            $filters['scope_vault_id'] = (int) $currentVaultId;
        }
        if ($currentProjectId) {
            $filters['scope_project_id'] = (int) $currentProjectId;
        }

        if ($vaultIdentifier = $command->arguments['vault'] ?? null) {
            if ($vault = $this->resolveVault($vaultIdentifier)) {
                $filters['scope_vault_id'] = $vault->id;
            }
        }

        if ($projectIdentifier = $command->arguments['project'] ?? null) {
            if ($project = $this->resolveProject($projectIdentifier)) {
                $filters['scope_project_id'] = $project->id;

                if (! isset($filters['scope_vault_id'])) {
                    $filters['scope_vault_id'] = $project->vault_id;
                }
            }
        }

        return $filters;
    }

    private function resolveVault(string $identifier): ?Vault
    {
        $identifier = trim($identifier);

        return Vault::query()
            ->where(function ($query) use ($identifier) {
                $query
                    ->when(is_numeric($identifier), fn ($q) => $q->orWhereKey((int) $identifier))
                    ->orWhere('name', 'LIKE', $identifier)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($identifier)])
                    ->orWhere('name', 'LIKE', '%'.Str::of($identifier)->replace('-', ' ')->toString().'%');
            })
            ->first();
    }

    private function resolveProject(string $identifier): ?Project
    {
        $identifier = trim($identifier);

        return Project::query()
            ->where(function ($query) use ($identifier) {
                $query
                    ->when(is_numeric($identifier), fn ($q) => $q->orWhereKey((int) $identifier))
                    ->orWhere('name', 'LIKE', $identifier)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($identifier)])
                    ->orWhere('name', 'LIKE', '%'.Str::of($identifier)->replace('-', ' ')->toString().'%');
            })
            ->first();
    }
}
