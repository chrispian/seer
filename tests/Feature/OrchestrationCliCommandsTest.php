<?php

use App\Services\DelegationMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var DelegationMigrationService $migration */
    $migration = app(DelegationMigrationService::class);
    $migration->import();
});

test('orchestration agents command returns json summary', function () {
    Artisan::call('orchestration:agents', ['--json' => true, '--limit' => 5]);

    $output = Artisan::output();
    $payload = json_decode($output, true);

    expect($payload)->toHaveKey('data');
    expect($payload['data'])->not->toBeEmpty();
    expect($payload['data'][0])->toHaveKeys(['id', 'name', 'slug', 'type', 'status']);
});

test('orchestration sprints command summarises sprint progress', function () {
    Artisan::call('orchestration:sprints', ['--json' => true, '--limit' => 2]);

    $payload = json_decode(Artisan::output(), true);

    expect($payload['data'])->not->toBeEmpty();
    expect($payload['data'][0])->toHaveKeys(['code', 'title', 'stats']);
    expect($payload['data'][0]['stats'])->toHaveKeys(['total', 'completed', 'in_progress']);
});

test('orchestration tasks command lists work items with filters', function () {
    Artisan::call('orchestration:tasks', ['--json' => true, '--limit' => 5, '--delegation-status' => ['completed', 'assigned']]);

    $payload = json_decode(Artisan::output(), true);

    expect($payload['data'])->not->toBeEmpty();
    expect($payload['data'][0])->toHaveKeys(['task_code', 'sprint_code', 'delegation_status']);
});
