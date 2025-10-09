<?php

use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\Project;
use App\Models\Vault;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $vault = Vault::factory()->create([
        'name' => 'Default',
        'is_default' => true,
        'sort_order' => 1,
    ]);

    Project::factory()->create([
        'name' => 'General',
        'vault_id' => $vault->id,
        'is_default' => true,
        'sort_order' => 1,
    ]);
});

test('imports conversations into chat sessions and fragments', function () {
    $fixturePath = base_path('tests/Fixtures/chatgpt');

    $this->artisan('chatgpt:import', [
        '--path' => $fixturePath,
    ])->assertExitCode(0);

    expect(ChatSession::count())->toBe(2);

    $session = ChatSession::where('metadata->chatgpt_conversation_id', 'conv-1')->first();
    expect($session)->not()->toBeNull();
    expect($session->messages)->toHaveCount(2);
    expect($session->messages[0]['message'])->toBe('Hello ChatGPT');

    $fragments = Fragment::where('metadata->chatgpt_conversation_id', 'conv-2')->get();
    expect($fragments)->toHaveCount(2);
    expect($fragments->first()->source_key)->toBe('chatgpt-user');
    expect($fragments->last()->message)->toContain('```php');
});

test('dry-run skips database writes', function () {
    $fixturePath = base_path('tests/Fixtures/chatgpt');

    $this->artisan('chatgpt:import', [
        '--path' => $fixturePath,
        '--dry-run' => true,
    ])->assertExitCode(0);

    expect(ChatSession::count())->toBe(0);
    expect(Fragment::count())->toBe(0);
});
