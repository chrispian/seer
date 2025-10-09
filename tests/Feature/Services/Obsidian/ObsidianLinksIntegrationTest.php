<?php

use App\Models\Fragment;
use App\Models\FragmentLink;
use App\Services\Obsidian\ObsidianImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tempVaultPath = sys_get_temp_dir().'/obsidian_test_'.uniqid();
    File::makeDirectory($this->tempVaultPath, 0755, true);

    $user = \App\Models\User::factory()->create([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->tempVaultPath,
                ],
            ],
        ],
    ]);
});

afterEach(function () {
    if (File::exists($this->tempVaultPath)) {
        File::deleteDirectory($this->tempVaultPath);
    }
});

it('imports notes and resolves internal links', function () {
    File::put($this->tempVaultPath.'/Note1.md', <<<'MD'
# Note One

This note links to [[Note2]] and [[Note3#Section]].
MD
    );

    File::put($this->tempVaultPath.'/Note2.md', <<<'MD'
# Note Two

This is the target note.
MD
    );

    File::put($this->tempVaultPath.'/Note3.md', <<<'MD'
# Note Three

## Section

This is a section.
MD
    );

    $service = app(ObsidianImportService::class);
    $stats = $service->import(dryRun: false);

    expect($stats['files_imported'])->toBe(3);
    expect($stats['links_resolved'])->toBe(2);
    expect($stats['links_orphaned'])->toBe(0);

    $note1 = Fragment::where('title', 'Note1')->first();
    $note2 = Fragment::where('title', 'Note2')->first();
    $note3 = Fragment::where('title', 'Note3')->first();

    expect($note1)->not->toBeNull();
    expect($note2)->not->toBeNull();
    expect($note3)->not->toBeNull();

    expect($note1->metadata['obsidian_links'])->toHaveCount(2);

    $links = FragmentLink::where('from_id', $note1->id)->get();
    expect($links)->toHaveCount(2);

    $targetIds = $links->pluck('to_id')->toArray();
    expect($targetIds)->toContain($note2->id);
    expect($targetIds)->toContain($note3->id);

    expect($links->first()->relation->value)->toBe('references');
});

it('handles orphan links', function () {
    File::put($this->tempVaultPath.'/Note1.md', <<<'MD'
# Note One

This links to [[Existing]] and [[NonExistent]].
MD
    );

    File::put($this->tempVaultPath.'/Existing.md', <<<'MD'
# Existing Note
MD
    );

    $service = app(ObsidianImportService::class);
    $stats = $service->import(dryRun: false);

    expect($stats['files_imported'])->toBe(2);
    expect($stats['links_resolved'])->toBe(1);
    expect($stats['links_orphaned'])->toBe(1);

    $note1 = Fragment::where('title', 'Note1')->first();
    $links = FragmentLink::where('from_id', $note1->id)->get();

    expect($links)->toHaveCount(1);
});

it('updates links on subsequent imports', function () {
    File::put($this->tempVaultPath.'/Source.md', <<<'MD'
# Source

Links to [[Target]].
MD
    );

    File::put($this->tempVaultPath.'/Target.md', <<<'MD'
# Target
MD
    );

    $service = app(ObsidianImportService::class);
    $service->import(dryRun: false);

    $source = Fragment::where('title', 'Source')->first();
    $initialLinks = FragmentLink::where('from_id', $source->id)->count();
    expect($initialLinks)->toBe(1);

    File::put($this->tempVaultPath.'/Source.md', <<<'MD'
# Source

Links to [[Target]] and [[Target]] again.
MD
    );

    touch($this->tempVaultPath.'/Source.md', time() + 10);

    $service->import(dryRun: false, force: true);

    $updatedLinks = FragmentLink::where('from_id', $source->id)->count();
    expect($updatedLinks)->toBe(1);
});

it('preserves link metadata during resolution', function () {
    File::put($this->tempVaultPath.'/Source.md', <<<'MD'
# Source

Check [[Target#Heading|Custom Alias]].
MD
    );

    File::put($this->tempVaultPath.'/Target.md', <<<'MD'
# Target

## Heading

Content here.
MD
    );

    $service = app(ObsidianImportService::class);
    $service->import(dryRun: false);

    $source = Fragment::where('title', 'Source')->first();

    expect($source->metadata['obsidian_links'])->toHaveCount(1);
    expect($source->metadata['obsidian_links'][0]['target'])->toBe('Target');
    expect($source->metadata['obsidian_links'][0]['heading'])->toBe('Heading');
    expect($source->metadata['obsidian_links'][0]['alias'])->toBe('Custom Alias');
});

it('handles case-insensitive link matching', function () {
    File::put($this->tempVaultPath.'/Source.md', <<<'MD'
# Source

Links to [[mynote]], [[MYNOTE]], and [[MyNote]].
MD
    );

    File::put($this->tempVaultPath.'/MyNote.md', <<<'MD'
# My Note
MD
    );

    $service = app(ObsidianImportService::class);
    $stats = $service->import(dryRun: false);

    expect($stats['links_resolved'])->toBe(3);
    expect($stats['links_orphaned'])->toBe(0);

    $source = Fragment::where('title', 'Source')->first();
    $links = FragmentLink::where('from_id', $source->id)->get();

    expect($links)->toHaveCount(1);
});
