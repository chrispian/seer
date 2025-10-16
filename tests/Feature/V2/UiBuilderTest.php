<?php

namespace Tests\Feature\V2;

use App\Models\Agent;
use App\Models\AgentProfile;
use App\Models\FeUiDatasource;
use App\Models\FeUiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $pageConfig = [
            'id' => 'page.agent.table.modal',
            'overlay' => 'modal',
            'title' => 'Agents',
            'components' => [
                [
                    'id' => 'component.table.agent',
                    'type' => 'table',
                    'dataSource' => 'Agent',
                ],
            ],
        ];

        FeUiPage::create([
            'key' => 'page.agent.table.modal',
            'config' => $pageConfig,
        ]);

        FeUiDatasource::create([
            'alias' => 'Agent',
            'model_class' => Agent::class,
            'resolver_class' => \App\Services\V2\AgentDataSourceResolver::class,
            'capabilities' => [
                'searchable' => ['name', 'designation'],
                'filterable' => ['status', 'agent_profile_id'],
                'sortable' => ['name', 'updated_at'],
            ],
        ]);
    }

    public function test_get_page_config_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/v2/ui/pages/page.agent.table.modal');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'key',
                'config',
                'hash',
                'version',
                'timestamp',
            ])
            ->assertJson([
                'key' => 'page.agent.table.modal',
            ]);

        $this->assertEquals(1, $response->json('version'));
        $this->assertNotEmpty($response->json('hash'));
    }

    public function test_get_page_config_returns_404_for_non_existent_page(): void
    {
        $response = $this->getJson('/api/v2/ui/pages/non.existent.page');

        $response->assertStatus(404)
            ->assertJsonStructure(['error', 'message']);
    }

    public function test_datasource_query_returns_paginated_agents(): void
    {
        $profile = AgentProfile::factory()->create();
        Agent::factory()->count(5)->create([
            'agent_profile_id' => $profile->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v2/ui/datasources/Agent', [
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'role',
                        'status',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'total',
                    'page',
                    'per_page',
                    'last_page',
                ],
                'hash',
            ]);

        $this->assertEquals(5, $response->json('meta.total'));
    }

    public function test_datasource_query_supports_search(): void
    {
        $profile = AgentProfile::factory()->create();
        Agent::factory()->create([
            'name' => 'John Doe',
            'agent_profile_id' => $profile->id,
        ]);
        Agent::factory()->create([
            'name' => 'Jane Smith',
            'agent_profile_id' => $profile->id,
        ]);

        $response = $this->postJson('/api/v2/ui/datasources/Agent', [
            'search' => 'John',
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('John Doe', $response->json('data.0.name'));
    }

    public function test_datasource_query_supports_filters(): void
    {
        $profile = AgentProfile::factory()->create();
        Agent::factory()->create([
            'status' => 'active',
            'agent_profile_id' => $profile->id,
        ]);
        Agent::factory()->create([
            'status' => 'inactive',
            'agent_profile_id' => $profile->id,
        ]);

        $response = $this->postJson('/api/v2/ui/datasources/Agent', [
            'filters' => [
                'status' => 'active',
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('active', $response->json('data.0.status'));
    }

    public function test_datasource_query_supports_sorting(): void
    {
        $profile = AgentProfile::factory()->create();
        Agent::factory()->create([
            'name' => 'Zebra Agent',
            'agent_profile_id' => $profile->id,
        ]);
        Agent::factory()->create([
            'name' => 'Alpha Agent',
            'agent_profile_id' => $profile->id,
        ]);

        $response = $this->postJson('/api/v2/ui/datasources/Agent', [
            'sort' => [
                'field' => 'name',
                'direction' => 'asc',
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Alpha Agent', $response->json('data.0.name'));
        $this->assertEquals('Zebra Agent', $response->json('data.1.name'));
    }

    public function test_action_execute_command_type_returns_success(): void
    {
        $response = $this->postJson('/api/v2/ui/action', [
            'type' => 'command',
            'command' => '/help',
            'params' => [],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'result',
                'hash',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_action_execute_navigate_type_returns_success(): void
    {
        $response = $this->postJson('/api/v2/ui/action', [
            'type' => 'navigate',
            'url' => '/agents',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'result' => [
                    'type' => 'navigate',
                    'url' => '/agents',
                ],
            ]);
    }

    public function test_action_execute_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v2/ui/action', [
            'type' => 'command',
        ]);

        $response->assertStatus(422);
    }

    public function test_page_config_version_increments_on_update(): void
    {
        $page = FeUiPage::where('key', 'page.agent.table.modal')->first();
        $originalVersion = $page->version;

        $page->config = array_merge($page->config, ['updated' => true]);
        $page->save();

        $this->assertEquals($originalVersion + 1, $page->version);
    }

    public function test_page_config_hash_changes_on_update(): void
    {
        $page = FeUiPage::where('key', 'page.agent.table.modal')->first();
        $originalHash = $page->hash;

        $page->config = array_merge($page->config, ['updated' => true]);
        $page->save();

        $this->assertNotEquals($originalHash, $page->hash);
    }
}
