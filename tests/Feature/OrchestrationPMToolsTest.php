<?php

use App\Models\OrchestrationSprint;
use App\Models\OrchestrationTask;
use App\Services\Orchestration\OrchestrationPMToolsService;
use Illuminate\Support\Facades\File;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->pmToolsService = app(OrchestrationPMToolsService::class);
});

afterEach(function () {
    $adrDir = base_path('docs/adr');
    $bugDir = base_path('delegation/backlog');
    
    if (File::exists($adrDir)) {
        $files = File::files($adrDir);
        foreach ($files as $file) {
            if (str_starts_with($file->getFilename(), 'ADR-') && str_contains($file->getFilename(), 'test')) {
                File::delete($file->getPathname());
            }
        }
    }
    
    if (File::exists($bugDir)) {
        $files = File::files($bugDir);
        foreach ($files as $file) {
            if (str_contains(strtolower($file->getFilename()), 'test-bug')) {
                File::delete($file->getPathname());
            }
        }
    }
});

it('generates ADR from template', function () {
    $result = $this->pmToolsService->generateADR('Test ADR for PM Tools');

    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('file_path');
    expect($result)->toHaveKey('adr_number');
    expect(File::exists($result['file_path']))->toBeTrue();

    $content = File::get($result['file_path']);
    expect($content)->toContain('Test ADR for PM Tools');
    expect($content)->toContain('Development Team');
    expect($content)->toContain(now()->toDateString());
});

it('generates ADR with custom options', function () {
    $result = $this->pmToolsService->generateADR('Test Custom ADR', [
        'deciders' => 'Architecture Team',
        'context' => 'We need to decide on database strategy',
        'decision' => 'We will use PostgreSQL with pgvector',
    ]);

    expect($result['success'])->toBeTrue();
    expect(File::exists($result['file_path']))->toBeTrue();

    $content = File::get($result['file_path']);
    expect($content)->toContain('Test Custom ADR');
    expect($content)->toContain('Architecture Team');
    expect($content)->toContain('We need to decide on database strategy');
    expect($content)->toContain('We will use PostgreSQL with pgvector');
});

it('generates incrementing ADR numbers', function () {
    $result1 = $this->pmToolsService->generateADR('Test ADR One');
    $result2 = $this->pmToolsService->generateADR('Test ADR Two');

    expect($result2['adr_number'])->toBe($result1['adr_number'] + 1);
});

it('generates bug report with required fields', function () {
    $result = $this->pmToolsService->generateBugReport('Test Bug Report', 'P1');

    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('file_path');
    expect($result)->toHaveKey('priority');
    expect($result['priority'])->toBe('P1');
    expect(File::exists($result['file_path']))->toBeTrue();

    $content = File::get($result['file_path']);
    expect($content)->toContain('Test Bug Report');
    expect($content)->toContain('**Priority**: P1');
    expect($content)->toContain(now()->toDateString());
});

it('generates bug report with all options', function () {
    $result = $this->pmToolsService->generateBugReport('Test Detailed Bug', 'P0', [
        'category' => 'Security',
        'component' => 'Authentication',
        'effort' => '4-6 hours',
        'description' => 'User sessions not expiring properly',
        'reproduction_steps' => '1. Login\n2. Wait 24 hours\n3. Try to access protected route',
        'expected_behavior' => 'Session should expire after 12 hours',
        'actual_behavior' => 'Session remains active indefinitely',
    ]);

    expect($result['success'])->toBeTrue();
    expect(File::exists($result['file_path']))->toBeTrue();

    $content = File::get($result['file_path']);
    expect($content)->toContain('Test Detailed Bug');
    expect($content)->toContain('**Category**: Security');
    expect($content)->toContain('**Component**: Authentication');
    expect($content)->toContain('**Estimated Effort**: 4-6 hours');
    expect($content)->toContain('User sessions not expiring properly');
    expect($content)->toContain('1. Login');
    expect($content)->toContain('Session should expire after 12 hours');
    expect($content)->toContain('Session remains active indefinitely');
});

it('updates task status with event emission', function () {
    $task = OrchestrationTask::create([
        'task_code' => 'test-task-status',
        'title' => 'Test Task',
        'status' => 'pending',
        'priority' => 'P2',
    ]);

    $result = $this->pmToolsService->updateTaskStatus('test-task-status', 'in_progress');

    expect($result['success'])->toBeTrue();
    expect($result['task_code'])->toBe('test-task-status');
    expect($result['old_status'])->toBe('pending');
    expect($result['new_status'])->toBe('in_progress');

    $task->refresh();
    expect($task->status)->toBe('in_progress');
});

it('skips event emission when disabled', function () {
    $task = OrchestrationTask::create([
        'task_code' => 'test-task-no-event',
        'title' => 'Test Task No Event',
        'status' => 'pending',
        'priority' => 'P2',
    ]);

    $eventCountBefore = \App\Models\OrchestrationEvent::count();

    $result = $this->pmToolsService->updateTaskStatus('test-task-no-event', 'completed', [
        'emit_event' => false,
    ]);

    $eventCountAfter = \App\Models\OrchestrationEvent::count();

    expect($result['success'])->toBeTrue();
    expect($eventCountAfter)->toBe($eventCountBefore);
});

it('generates status report for sprint', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'test-sprint-report',
        'title' => 'Test Sprint for Report',
        'status' => 'active',
    ]);

    $task1 = OrchestrationTask::create([
        'task_code' => 'task-1',
        'title' => 'Task 1',
        'status' => 'completed',
        'priority' => 'P1',
        'sprint_id' => $sprint->id,
    ]);

    $task2 = OrchestrationTask::create([
        'task_code' => 'task-2',
        'title' => 'Task 2',
        'status' => 'in_progress',
        'priority' => 'P2',
        'sprint_id' => $sprint->id,
    ]);

    $task3 = OrchestrationTask::create([
        'task_code' => 'task-3',
        'title' => 'Task 3',
        'status' => 'pending',
        'priority' => 'P3',
        'sprint_id' => $sprint->id,
    ]);

    $report = $this->pmToolsService->generateStatusReport('test-sprint-report');

    expect($report)->toHaveKey('sprint_code');
    expect($report)->toHaveKey('sprint_title');
    expect($report)->toHaveKey('summary');
    expect($report)->toHaveKey('tasks');

    expect($report['summary']['total_tasks'])->toBe(3);
    expect($report['summary']['completed'])->toBe(1);
    expect($report['summary']['in_progress'])->toBe(1);
    expect($report['summary']['pending'])->toBe(1);
    expect($report['summary']['progress_percentage'])->toBe(33.33);
    expect(count($report['tasks']))->toBe(3);
});

it('sanitizes input to prevent path traversal in ADR', function () {
    expect(fn() => $this->pmToolsService->generateADR('../../../etc/passwd'))
        ->toThrow(\InvalidArgumentException::class);
});

it('sanitizes input to prevent path traversal in bug report', function () {
    expect(fn() => $this->pmToolsService->generateBugReport('../../malicious', 'P1'))
        ->toThrow(\InvalidArgumentException::class);
});

it('sanitizes input to prevent path traversal in task status', function () {
    expect(fn() => $this->pmToolsService->updateTaskStatus('../task', 'completed'))
        ->toThrow(\InvalidArgumentException::class);
});
