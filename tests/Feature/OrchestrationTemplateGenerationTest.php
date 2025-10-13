<?php

use App\Models\OrchestrationSprint;
use App\Services\Orchestration\OrchestrationFileSyncService;
use App\Services\Orchestration\OrchestrationTemplateService;
use Illuminate\Support\Facades\File;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->templateService = app(OrchestrationTemplateService::class);
    $this->fileSyncService = app(OrchestrationFileSyncService::class);
});

it('can list available templates', function () {
    $templates = $this->templateService->getAvailableTemplates();

    expect($templates)->toHaveKey('sprints');
    expect($templates)->toHaveKey('tasks');
    expect($templates)->toHaveKey('agents');
});

it('can load sprint template', function () {
    $content = $this->templateService->loadTemplate('sprint', 'SPRINT_TEMPLATE.md');

    expect($content)->toBeString();
    expect($content)->toContain('Sprint:');
});

it('can parse template with variables', function () {
    $template = "# Sprint: {{title}}\n\n**Sprint ID**: `{{sprint_code}}`";
    
    $parsed = $this->templateService->parseTemplate($template, [
        'title' => 'Test Sprint',
        'sprint_code' => 'test-sprint-001',
    ]);

    expect($parsed)->toContain('# Sprint: Test Sprint');
    expect($parsed)->toContain('**Sprint ID**: `test-sprint-001`');
});

it('can format array values in templates', function () {
    $template = "## Goals\n{{goals}}";
    
    $parsed = $this->templateService->parseTemplate($template, [
        'goals' => ['Goal 1', 'Goal 2', 'Goal 3'],
    ]);

    expect($parsed)->toContain('- Goal 1');
    expect($parsed)->toContain('- Goal 2');
    expect($parsed)->toContain('- Goal 3');
});

it('can sync sprint to file system', function () {
    $sprint = OrchestrationSprint::create([
        'sprint_code' => 'test-sprint-sync',
        'title' => 'Test Sprint Sync',
        'status' => 'planning',
        'metadata' => [
            'goal' => 'Test goal',
            'start_date' => '2025-10-13',
        ],
    ]);

    $result = $this->fileSyncService->syncSprintToFile($sprint);

    expect($result)->toBeTrue();
    
    $sprintPath = base_path("delegation/sprints/{$sprint->sprint_code}");
    expect(File::isDirectory($sprintPath))->toBeTrue();
    expect(File::exists("{$sprintPath}/SPRINT.md"))->toBeTrue();
    expect(File::exists("{$sprintPath}/README.md"))->toBeTrue();

    File::deleteDirectory($sprintPath);
});

it('can create sprint from template via API', function () {
    $response = $this->postJson('/api/orchestration/sprints/from-template', [
        'template_name' => 'default',
        'sprint_code' => 'api-test-sprint',
        'title' => 'API Test Sprint',
        'owner' => 'test-user',
        'variables' => [
            'goal' => 'Test sprint creation via API',
            'duration' => '2 weeks',
        ],
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => 'Sprint created from template',
    ]);

    expect(OrchestrationSprint::where('sprint_code', 'api-test-sprint')->exists())->toBeTrue();

    File::deleteDirectory(base_path('delegation/sprints/api-test-sprint'));
});
