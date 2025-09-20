<?php

use App\Actions\Commands\RoutingCommand;
use App\DTOs\CommandRequest;
use App\Livewire\RoutingRulesManager;
use App\Models\Project;
use App\Models\Vault;
use App\Models\VaultRoutingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('opens routing panel with rule data', function () {
    $vault = Vault::factory()->create(['name' => 'Archive']);
    $project = Project::factory()->create(['vault_id' => $vault->id, 'name' => 'Client Onboarding']);

    $rule = VaultRoutingRule::factory()->create([
        'name' => 'Client keyword routing',
        'match_type' => 'keyword',
        'match_value' => 'client',
        'target_vault_id' => $vault->id,
        'target_project_id' => $project->id,
        'priority' => 5,
    ]);

    $command = app(RoutingCommand::class);
    $request = new CommandRequest('routing', ['vault_id' => $vault->id], '/routing');

    $response = $command->handle($request);

    expect($response->shouldOpenPanel)->toBeTrue()
        ->and($response->panelData)->toHaveKey('rules')
        ->and($response->panelData['rules'])->toHaveCount(1)
        ->and($response->panelData['rules'][0]['id'])->toEqual($rule->id)
        ->and($response->panelData['filters']['scope_vault_id'])->toEqual($vault->id)
        ->and($response->panelData['vaults'][0]['name'])->toEqual('Archive');
});

it('creates routing rules via flux panel component', function () {
    $vault = Vault::factory()->create(['name' => 'Focus']);
    $project = Project::factory()->create(['vault_id' => $vault->id, 'name' => 'Deep Work']);

    Livewire::test(RoutingRulesManager::class, [
        'rules' => [],
        'vaults' => [[
            'id' => $vault->id,
            'name' => $vault->name,
        ]],
        'projects' => Project::all()->map(fn ($proj) => [
            'id' => $proj->id,
            'name' => $proj->name,
            'vault_id' => $proj->vault_id,
        ])->values()->all(),
        'filters' => ['scope_vault_id' => $vault->id],
    ])
        ->call('startCreate')
        ->set('name', 'Deep work fragments')
        ->set('matchType', 'keyword')
        ->set('matchValue', 'deep-work')
        ->set('targetVaultId', $vault->id)
        ->set('targetProjectId', $project->id)
        ->set('priority', 10)
        ->set('isActive', true)
        ->call('saveRule')
        ->assertSet('showEditor', false);

    expect(VaultRoutingRule::count())->toBe(1)
        ->and(VaultRoutingRule::first()->match_value)->toBe('deep-work');
});
