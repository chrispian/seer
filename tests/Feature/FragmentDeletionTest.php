<?php

use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fragment delete success', function () {
    $fragment = Fragment::factory()->create([
        'message' => 'Test fragment to delete',
        'type' => 'log',
    ]);

    $response = $this->deleteJson("/api/fragments/{$fragment->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Fragment deleted successfully',
        ]);

    // Verify fragment is actually deleted
    expect(Fragment::find($fragment->id))->toBeNull();
});

test('fragment delete handles non-existent fragment', function () {
    $nonExistentId = 99999;

    $response = $this->deleteJson("/api/fragments/{$nonExistentId}");

    $response->assertStatus(404);
});

test('fragment delete handles database errors gracefully', function () {
    // Create a fragment and then manually delete it to simulate a race condition
    $fragment = Fragment::factory()->create();
    Fragment::where('id', $fragment->id)->delete();

    $response = $this->deleteJson("/api/fragments/{$fragment->id}");

    $response->assertStatus(404);
});
