<?php

use App\Models\AgentProfile;
use App\Models\Sprint;
use App\Models\WorkItem;
use App\Services\DelegationMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('it parses sprint status content', function () {
    $service = app(DelegationMigrationService::class);
    $content = File::get(base_path('delegation/SPRINT_STATUS.md'));
    $parsed = $service->parseSprintStatusContent($content);

    expect($parsed)->toHaveKey('SPRINT-62');
    expect($parsed['SPRINT-62']['tasks'])->toHaveKey('ORCH-01-01');
    expect($parsed['SPRINT-57']['tasks'])->toHaveKey('VECTOR-001');
    expect($parsed['SPRINT-57']['tasks']['VECTOR-001']['status'])->toBe('todo');
});

test('dry-run import provides preview without persisting', function () {
    $service = app(DelegationMigrationService::class);

    $summary = $service->import([
        'dry_run' => true,
        'sprint' => ['SPRINT-62'],
    ]);

    expect($summary['dry_run'])->toBeTrue();
    expect($summary['sprints']['processed'])->toBeGreaterThan(0);
    expect($summary['work_items']['processed'])->toBeGreaterThan(0);
    expect(Sprint::count())->toBe(0);
    expect(WorkItem::count())->toBe(0);
});

test('import persists sprint and work item data', function () {
    $service = app(DelegationMigrationService::class);

    $summary = $service->import([
        'sprint' => ['SPRINT-62'],
    ]);

    expect($summary['dry_run'])->toBeFalse();
    expect(Sprint::where('code', 'SPRINT-62')->exists())->toBeTrue();
    expect(WorkItem::where('metadata->task_code', 'ORCH-01-01')->exists())->toBeTrue();
    expect(AgentProfile::where('slug', 'backend-engineer-template')->exists())->toBeTrue();
});
