<?php

    use App\Models\SeerLog;
    use Illuminate\Foundation\Testing\RefreshDatabase;

    test('can create a log', function () {
        $response = $this->postJson('/api/log', [
            'type' => 'obs',
            'message' => 'Test log entry',
            'tags' => ['test'],
            'relationships' => ['related_to' => 1],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['type' => 'obs']);
    });

    test('can search logs', function () {
        SeerLog::factory()->create(['message' => 'find me']);

        $response = $this->getJson('/api/search?q=find');
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'find me']);
    });