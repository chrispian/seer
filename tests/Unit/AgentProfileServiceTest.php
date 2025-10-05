<?php

use App\Enums\AgentMode;
use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\AgentProfile;
use App\Services\AgentProfileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

uses(DatabaseTransactions::class);

beforeEach(function () {
    static $migrated = false;

    if (! $migrated) {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2025_10_05_180528_create_agent_profiles_table.php',
        ]);

        $migrated = true;
    }
});

it('creates an agent profile with defaults and inferred mode', function () {
    $service = new AgentProfileService();

    $agent = $service->create([
        'name' => 'Alice Backend Engineer',
        'type' => AgentType::BackendEngineer,
    ]);

    expect($agent->id)->not->toBeNull();
    expect($agent->slug)->toBe('alice-backend-engineer');
    expect($agent->type)->toBeInstanceOf(AgentType::class);
    expect($agent->type)->toBe(AgentType::BackendEngineer);
    expect($agent->mode)->toBeInstanceOf(AgentMode::class);
    expect($agent->mode)->toBe(AgentMode::Implementation);
    expect($agent->status)->toBe(AgentStatus::Active);
    expect($agent->capabilities)->toBeNull();
});

it('updates an agent profile and normalises list data', function () {
    $agent = AgentProfile::factory()->create([
        'type' => AgentType::BackendEngineer->value,
        'mode' => AgentMode::Implementation->value,
    ]);

    $service = new AgentProfileService();

    $updated = $service->update($agent, [
        'description' => 'Primary Laravel backend agent.',
        'capabilities' => ['php', 'laravel', '', null],
        'constraints' => 'no_production_access',
        'tools' => ['composer', ' artisan '],
        'status' => AgentStatus::Inactive,
        'mode' => AgentMode::Review,
    ]);

    expect($updated->description)->toBe('Primary Laravel backend agent.');
    expect($updated->capabilities)->toBe(['php', 'laravel']);
    expect($updated->constraints)->toBe(['no_production_access']);
    expect($updated->tools)->toBe(['composer', 'artisan']);
    expect($updated->status)->toBe(AgentStatus::Inactive);
    expect($updated->mode)->toBe(AgentMode::Review);
});

it('lists agent profiles with filters applied', function () {
    $backend = AgentProfile::factory()->create([
        'type' => AgentType::BackendEngineer->value,
        'mode' => AgentMode::Implementation->value,
        'status' => AgentStatus::Active->value,
    ]);

    $frontend = AgentProfile::factory()->inactive()->create([
        'type' => AgentType::FrontendEngineer->value,
        'mode' => AgentMode::Implementation->value,
    ]);

    $pm = AgentProfile::factory()->create([
        'type' => AgentType::ProjectManager->value,
        'mode' => AgentMode::Coordination->value,
    ]);

    $service = new AgentProfileService();

    $activeOnly = $service->list(['status' => AgentStatus::Active]);
    expect($activeOnly->pluck('id'))
        ->toContain($backend->id)
        ->toContain($pm->id)
        ->not->toContain($frontend->id);

    $frontendOnly = $service->list(['type' => AgentType::FrontendEngineer]);
    expect($frontendOnly)->toHaveCount(1);
    expect($frontendOnly->first()->id)->toBe($frontend->id);

    $searchByName = $service->list(['search' => $pm->name]);
    expect($searchByName)->toHaveCount(1);
    expect($searchByName->first()->id)->toBe($pm->id);
});

it('ensures slug uniqueness when creating agents with duplicate names', function () {
    $service = new AgentProfileService();

    $first = $service->create([
        'name' => 'Duplicate Name Agent',
        'type' => AgentType::BackendEngineer->value,
    ]);

    $second = $service->create([
        'name' => 'Duplicate Name Agent',
        'type' => AgentType::BackendEngineer->value,
    ]);

    expect($first->slug)->toBe('duplicate-name-agent');
    expect($second->slug)->toBe('duplicate-name-agent-2');
});

it('returns catalog definitions for types, modes, and statuses', function () {
    $service = new AgentProfileService();

    $types = $service->availableTypes();
    $modes = $service->availableModes();
    $statuses = $service->availableStatuses();

    expect($types)->not->toBeEmpty();
    expect($types[0])->toHaveKeys(['value', 'label', 'description', 'default_mode']);
    expect($modes)->not->toBeEmpty();
    expect($modes[0])->toHaveKeys(['value', 'label', 'description']);
    expect($statuses)->not->toBeEmpty();
    expect($statuses[0])->toHaveKeys(['value', 'label']);
});
