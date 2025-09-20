<?php

use App\Models\Fragment;
use App\Models\Project;
use App\Models\Type;
use App\Models\Vault;
use App\Models\VaultRoutingRule;
use App\Services\VaultRoutingRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(VaultRoutingRuleService::class);
    $this->vault = Vault::factory()->create(['name' => 'work']);
    $this->project = Project::factory()->create(['vault_id' => $this->vault->id]);
});

it('resolves fragment to rule target when keyword matches', function () {
    $rule = VaultRoutingRule::factory()->create([
        'match_type' => 'keyword',
        'match_value' => 'meeting',
        'target_vault_id' => $this->vault->id,
        'target_project_id' => $this->project->id,
        'is_active' => true,
        'priority' => 1,
    ]);

    $fragment = Fragment::factory()->make([
        'message' => 'Schedule a meeting with the client tomorrow',
    ]);

    $result = $this->service->resolveForFragment($fragment);

    expect($result)->toEqual([
        'vault' => 'work',
        'vault_id' => $this->vault->id,
        'project_id' => $this->project->id,
    ]);
});

it('resolves fragment to rule target when tag matches', function () {
    $rule = VaultRoutingRule::factory()->create([
        'match_type' => 'tag',
        'match_value' => 'urgent',
        'target_vault_id' => $this->vault->id,
        'target_project_id' => $this->project->id,
        'is_active' => true,
        'priority' => 1,
    ]);

    $fragment = Fragment::factory()->make([
        'message' => 'Some important task',
        'tags' => ['urgent', 'work'],
    ]);

    $result = $this->service->resolveForFragment($fragment);

    expect($result)->toEqual([
        'vault' => 'work',
        'vault_id' => $this->vault->id,
        'project_id' => $this->project->id,
    ]);
});

it('resolves fragment to rule target when type matches', function () {
    $type = Type::create(['value' => 'todo', 'label' => 'Todo']);

    $rule = VaultRoutingRule::factory()->create([
        'match_type' => 'type',
        'match_value' => 'todo',
        'target_vault_id' => $this->vault->id,
        'target_project_id' => $this->project->id,
        'is_active' => true,
        'priority' => 1,
    ]);

    $fragment = Fragment::factory()->make([
        'message' => 'Complete the report',
        'type_id' => $type->id,
    ]);
    $fragment->setRelation('type', $type);

    $result = $this->service->resolveForFragment($fragment);

    expect($result)->toEqual([
        'vault' => 'work',
        'vault_id' => $this->vault->id,
        'project_id' => $this->project->id,
    ]);
});

it('returns null when no rules match', function () {
    VaultRoutingRule::factory()->create([
        'match_type' => 'keyword',
        'match_value' => 'nomatch',
        'target_vault_id' => $this->vault->id,
        'is_active' => true,
        'priority' => 1,
    ]);

    $fragment = Fragment::factory()->make([
        'message' => 'This is a regular message',
    ]);

    $result = $this->service->resolveForFragment($fragment);

    expect($result)->toBeNull();
});

it('ignores inactive rules', function () {
    VaultRoutingRule::factory()->create([
        'match_type' => 'keyword',
        'match_value' => 'meeting',
        'target_vault_id' => $this->vault->id,
        'is_active' => false,
        'priority' => 1,
    ]);

    $fragment = Fragment::factory()->make([
        'message' => 'Schedule a meeting tomorrow',
    ]);

    $result = $this->service->resolveForFragment($fragment);

    expect($result)->toBeNull();
});

it('respects rule priority order', function () {
    $vault2 = Vault::factory()->create(['name' => 'personal']);
    $project2 = Project::factory()->create(['vault_id' => $vault2->id]);

    // Higher priority rule (lower number = higher priority)
    VaultRoutingRule::factory()->create([
        'match_type' => 'keyword',
        'match_value' => 'meeting',
        'target_vault_id' => $vault2->id,
        'target_project_id' => $project2->id,
        'is_active' => true,
        'priority' => 1,
    ]);

    // Lower priority rule
    VaultRoutingRule::factory()->create([
        'match_type' => 'keyword',
        'match_value' => 'meeting',
        'target_vault_id' => $this->vault->id,
        'target_project_id' => $this->project->id,
        'is_active' => true,
        'priority' => 2,
    ]);

    $fragment = Fragment::factory()->make([
        'message' => 'Schedule a meeting tomorrow',
    ]);

    $result = $this->service->resolveForFragment($fragment);

    expect($result['vault'])->toBe('personal');
    expect($result['vault_id'])->toBe($vault2->id);
    expect($result['project_id'])->toBe($project2->id);
});

it('adds default project when rule only specifies vault', function () {
    $defaultProject = Project::factory()->create([
        'vault_id' => $this->vault->id,
        'is_default' => true,
    ]);

    $rule = VaultRoutingRule::factory()->create([
        'match_type' => 'keyword',
        'match_value' => 'meeting',
        'target_vault_id' => $this->vault->id,
        'target_project_id' => null,
        'is_active' => true,
        'priority' => 1,
    ]);

    $fragment = Fragment::factory()->make([
        'message' => 'Schedule a meeting tomorrow',
    ]);

    $result = $this->service->resolveForFragment($fragment);

    expect($result)->toEqual([
        'vault' => 'work',
        'vault_id' => $this->vault->id,
        'project_id' => $defaultProject->id,
    ]);
});
