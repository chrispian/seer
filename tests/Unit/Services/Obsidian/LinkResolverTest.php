<?php

use App\Models\Fragment;
use App\Services\Obsidian\LinkResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resolver = new LinkResolver;
});

it('resolves links to fragment IDs', function () {
    $fragment1 = Fragment::factory()->create([
        'source_key' => 'obsidian',
        'metadata' => ['obsidian_path' => 'Notes/Target.md'],
    ]);

    $fragment2 = Fragment::factory()->create([
        'source_key' => 'obsidian',
        'metadata' => ['obsidian_path' => 'Projects/Another.md'],
    ]);

    $links = [
        [
            'raw' => '[[Target]]',
            'target' => 'Target',
            'heading' => null,
            'alias' => null,
            'position' => 0,
        ],
        [
            'raw' => '[[Another]]',
            'target' => 'Another',
            'heading' => null,
            'alias' => null,
            'position' => 50,
        ],
    ];

    $result = $this->resolver->resolve($links, 999);

    expect($result['resolved'])->toHaveCount(2);
    expect($result['orphans'])->toHaveCount(0);
    expect($result['stats'])->toBe([
        'total' => 2,
        'resolved' => 2,
        'orphaned' => 0,
    ]);

    expect($result['resolved'][0]['target_fragment_id'])->toBe($fragment1->id);
    expect($result['resolved'][1]['target_fragment_id'])->toBe($fragment2->id);
});

it('identifies orphan links when target not found', function () {
    Fragment::factory()->create([
        'source_key' => 'obsidian',
        'metadata' => ['obsidian_path' => 'Notes/Existing.md'],
    ]);

    $links = [
        [
            'raw' => '[[Existing]]',
            'target' => 'Existing',
            'heading' => null,
            'alias' => null,
            'position' => 0,
        ],
        [
            'raw' => '[[NonExistent]]',
            'target' => 'NonExistent',
            'heading' => null,
            'alias' => null,
            'position' => 50,
        ],
    ];

    $result = $this->resolver->resolve($links, 999);

    expect($result['resolved'])->toHaveCount(1);
    expect($result['orphans'])->toHaveCount(1);
    expect($result['stats'])->toBe([
        'total' => 2,
        'resolved' => 1,
        'orphaned' => 1,
    ]);

    expect($result['orphans'][0]['target'])->toBe('NonExistent');
});

it('is case-insensitive when matching filenames', function () {
    $fragment = Fragment::factory()->create([
        'source_key' => 'obsidian',
        'metadata' => ['obsidian_path' => 'Notes/MyNote.md'],
    ]);

    $links = [
        ['raw' => '[[mynote]]', 'target' => 'mynote', 'heading' => null, 'alias' => null, 'position' => 0],
        ['raw' => '[[MYNOTE]]', 'target' => 'MYNOTE', 'heading' => null, 'alias' => null, 'position' => 50],
        ['raw' => '[[MyNote]]', 'target' => 'MyNote', 'heading' => null, 'alias' => null, 'position' => 100],
    ];

    $result = $this->resolver->resolve($links, 999);

    expect($result['resolved'])->toHaveCount(3);
    expect($result['orphans'])->toHaveCount(0);

    foreach ($result['resolved'] as $link) {
        expect($link['target_fragment_id'])->toBe($fragment->id);
    }
});

it('excludes self-references', function () {
    $fragment = Fragment::factory()->create([
        'source_key' => 'obsidian',
        'metadata' => ['obsidian_path' => 'Notes/Self.md'],
    ]);

    $links = [
        ['raw' => '[[Self]]', 'target' => 'Self', 'heading' => null, 'alias' => null, 'position' => 0],
    ];

    $result = $this->resolver->resolve($links, $fragment->id);

    expect($result['resolved'])->toHaveCount(0);
    expect($result['orphans'])->toHaveCount(0);
});

it('handles empty links array', function () {
    $result = $this->resolver->resolve([], 999);

    expect($result['resolved'])->toHaveCount(0);
    expect($result['orphans'])->toHaveCount(0);
    expect($result['stats'])->toBe([
        'total' => 0,
        'resolved' => 0,
        'orphaned' => 0,
    ]);
});

it('preserves link metadata in resolved links', function () {
    $fragment = Fragment::factory()->create([
        'source_key' => 'obsidian',
        'metadata' => ['obsidian_path' => 'Notes/Target.md'],
    ]);

    $links = [
        [
            'raw' => '[[Target#Section|Custom Alias]]',
            'target' => 'Target',
            'heading' => 'Section',
            'alias' => 'Custom Alias',
            'position' => 42,
        ],
    ];

    $result = $this->resolver->resolve($links, 999);

    expect($result['resolved'])->toHaveCount(1);

    $resolved = $result['resolved'][0];
    expect($resolved['target_fragment_id'])->toBe($fragment->id);
    expect($resolved['raw'])->toBe('[[Target#Section|Custom Alias]]');
    expect($resolved['target'])->toBe('Target');
    expect($resolved['heading'])->toBe('Section');
    expect($resolved['alias'])->toBe('Custom Alias');
    expect($resolved['position'])->toBe(42);
});

it('only matches obsidian fragments', function () {
    Fragment::factory()->create([
        'source_key' => 'other',
        'metadata' => ['obsidian_path' => 'Notes/Target.md'],
    ]);

    $fragment = Fragment::factory()->create([
        'source_key' => 'obsidian',
        'metadata' => ['obsidian_path' => 'Notes/Valid.md'],
    ]);

    $links = [
        ['raw' => '[[Target]]', 'target' => 'Target', 'heading' => null, 'alias' => null, 'position' => 0],
        ['raw' => '[[Valid]]', 'target' => 'Valid', 'heading' => null, 'alias' => null, 'position' => 50],
    ];

    $result = $this->resolver->resolve($links, 999);

    expect($result['resolved'])->toHaveCount(1);
    expect($result['orphans'])->toHaveCount(1);
    expect($result['resolved'][0]['target_fragment_id'])->toBe($fragment->id);
});
