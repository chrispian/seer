<?php

namespace App\Livewire;

use App\Models\VaultRoutingRule;
use App\Services\VaultRoutingRuleService;
use Livewire\Component;

class RoutingRulesManager extends Component
{
    public array $rules = [];

    public array $vaults = [];

    public array $projects = [];

    public array $filters = [];

    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $matchType = 'keyword';

    public ?string $matchValue = null;

    public string $conditionsInput = '';

    public ?int $targetVaultId = null;

    public ?int $targetProjectId = null;

    public ?int $scopeVaultId = null;

    public ?int $scopeProjectId = null;

    public int $priority = 100;

    public bool $isActive = true;

    public ?string $notes = null;

    public ?int $pendingDeletionId = null;

    public bool $showDeleteConfirm = false;

    public ?array $flash = null;

    protected $listeners = [
        'routing-rules:refresh' => 'refreshRules',
    ];

    public function mount(array $rules = [], array $vaults = [], array $projects = [], array $filters = []): void
    {
        $this->rules = $rules;
        $this->vaults = $vaults;
        $this->projects = $projects;
        $this->filters = $filters;

        $this->scopeVaultId = $filters['scope_vault_id'] ?? null;
        $this->scopeProjectId = $filters['scope_project_id'] ?? null;
    }

    public function render()
    {
        return view('livewire.routing-rules-manager');
    }

    public function getProjectOptionsProperty(): array
    {
        if (! $this->targetVaultId) {
            return $this->projects;
        }

        return array_values(array_filter($this->projects, fn ($project) => (int) ($project['vault_id'] ?? null) === (int) $this->targetVaultId));
    }

    public function getScopeProjectOptionsProperty(): array
    {
        if (! $this->scopeVaultId) {
            return $this->projects;
        }

        return array_values(array_filter($this->projects, fn ($project) => (int) ($project['vault_id'] ?? null) === (int) $this->scopeVaultId));
    }

    public function updatedTargetVaultId(): void
    {
        if ($this->targetProjectId && ! collect($this->projectOptions)->contains(fn ($project) => (int) $project['id'] === (int) $this->targetProjectId)) {
            $this->targetProjectId = null;
        }
    }

    public function updatedScopeVaultId(): void
    {
        if ($this->scopeProjectId && ! collect($this->scopeProjectOptions)->contains(fn ($project) => (int) $project['id'] === (int) $this->scopeProjectId)) {
            $this->scopeProjectId = null;
        }
    }

    public function refreshRules(): void
    {
        $this->rules = app(VaultRoutingRuleService::class)
            ->list($this->filters)
            ->map(fn ($rule) => $rule->toArray())
            ->values()
            ->all();
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->showEditor = true;
    }

    public function startEdit(int $ruleId): void
    {
        $rule = VaultRoutingRule::query()->with(['targetVault', 'targetProject', 'contextVault', 'contextProject'])->findOrFail($ruleId);

        $this->editingId = $rule->id;
        $this->name = $rule->name;
        $this->matchType = $rule->match_type;
        $this->matchValue = $rule->match_value;
        $this->conditionsInput = $rule->conditions ? json_encode($rule->conditions, JSON_PRETTY_PRINT) : '';
        $this->targetVaultId = $rule->target_vault_id;
        $this->targetProjectId = $rule->target_project_id;
        $this->scopeVaultId = $rule->scope_vault_id;
        $this->scopeProjectId = $rule->scope_project_id;
        $this->priority = $rule->priority;
        $this->isActive = $rule->is_active;
        $this->notes = $rule->notes;

        $this->showEditor = true;
    }

    public function cancelEditor(): void
    {
        $this->resetForm();
        $this->showEditor = false;
    }

    public function confirmDelete(int $ruleId): void
    {
        $this->pendingDeletionId = $ruleId;
        $this->showDeleteConfirm = true;
    }

    public function deleteRule(VaultRoutingRuleService $service): void
    {
        if (! $this->pendingDeletionId) {
            return;
        }

        $rule = VaultRoutingRule::find($this->pendingDeletionId);

        if (! $rule) {
            $this->pendingDeletionId = null;

            return;
        }

        $service->delete($rule);

        $this->pendingDeletionId = null;
        $this->showDeleteConfirm = false;

        $this->refreshRules();

        $this->flash = [
            'title' => 'Routing rule deleted',
            'body' => 'The rule has been removed and will no longer be evaluated.',
            'variant' => 'danger',
        ];
    }

    public function saveRule(VaultRoutingRuleService $service): void
    {
        $validated = $this->validate($this->rules(), $this->validationMessages(), $this->validationAttributes());

        $isUpdate = (bool) $this->editingId;

        $conditions = null;
        if ($this->conditionsInput !== '') {
            $decoded = json_decode($this->conditionsInput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('conditionsInput', 'Conditions must be valid JSON.');

                return;
            }

            $conditions = $decoded;
        }

        $payload = [
            'name' => $validated['name'],
            'match_type' => $validated['matchType'],
            'match_value' => $validated['matchValue'] ?? null,
            'conditions' => $conditions,
            'target_vault_id' => $validated['targetVaultId'],
            'target_project_id' => $validated['targetProjectId'] ?? null,
            'scope_vault_id' => $validated['scopeVaultId'] ?? null,
            'scope_project_id' => $validated['scopeProjectId'] ?? null,
            'priority' => $validated['priority'],
            'is_active' => $validated['isActive'],
            'notes' => $validated['notes'] ?? null,
        ];
        if ($isUpdate) {
            $rule = VaultRoutingRule::findOrFail($this->editingId);
            $service->update($rule, $payload);
        } else {
            $rule = $service->create($payload);
            $this->editingId = $rule->id;
        }

        $this->showEditor = false;

        $this->refreshRules();

        $this->flash = [
            'title' => $isUpdate ? 'Routing rule updated' : 'Routing rule created',
            'body' => 'Routing rules have been synced. New fragments will use these settings.',
            'variant' => $isUpdate ? 'filled' : 'primary',
        ];

        $this->resetForm();
    }

    public function clearFlash(): void
    {
        $this->flash = null;
    }

    public function cancelDelete(): void
    {
        $this->pendingDeletionId = null;
        $this->showDeleteConfirm = false;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'matchType' => ['required', 'string', 'max:64'],
            'matchValue' => ['nullable', 'string', 'max:255'],
            'targetVaultId' => ['required', 'exists:vaults,id'],
            'targetProjectId' => ['nullable', 'exists:projects,id'],
            'scopeVaultId' => ['nullable', 'exists:vaults,id'],
            'scopeProjectId' => ['nullable', 'exists:projects,id'],
            'priority' => ['required', 'integer', 'between:1,1000'],
            'isActive' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'targetVaultId.required' => 'Select the vault fragments should be routed to.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'targetVaultId' => 'target vault',
            'targetProjectId' => 'target project',
            'scopeVaultId' => 'context vault',
            'scopeProjectId' => 'context project',
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->matchType = 'keyword';
        $this->matchValue = null;
        $this->conditionsInput = '';
        $this->targetVaultId = $this->filters['scope_vault_id'] ?? null;
        $this->targetProjectId = null;
        $this->scopeVaultId = $this->filters['scope_vault_id'] ?? null;
        $this->scopeProjectId = $this->filters['scope_project_id'] ?? null;
        $this->priority = $this->nextPriority();
        $this->isActive = true;
        $this->notes = null;
    }

    protected function nextPriority(): int
    {
        if (empty($this->rules)) {
            return 1;
        }

        $max = collect($this->rules)->max('priority');

        return (int) $max + 1;
    }
}
