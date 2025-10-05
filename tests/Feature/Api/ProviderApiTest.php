<?php

namespace Tests\Feature\Api;

use App\Models\AICredential;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_providers()
    {
        // Use getOrCreateForProvider to avoid unique constraint issues
        $providerConfig = Provider::getOrCreateForProvider('openai');

        $response = $this->getJson('/api/providers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'display_name',
                        'enabled',
                        'health_status',
                        'is_available',
                        'stats',
                        'capabilities',
                    ],
                ],
                'meta',
                'status',
            ])
            ->assertJson([
                'status' => 'success',
            ]);
    }

    public function test_can_get_specific_provider()
    {
        $response = $this->getJson('/api/providers/openai');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'display_name',
                    'enabled',
                    'capabilities',
                    'ui_preferences',
                ],
            ]);
    }

    public function test_can_toggle_provider()
    {
        $providerConfig = Provider::getOrCreateForProvider('openai');

        $response = $this->postJson('/api/providers/openai/toggle');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'enabled' => false,
                ],
            ]);
    }

    public function test_can_update_provider_config()
    {
        $providerConfig = Provider::getOrCreateForProvider('openai');

        $updateData = [
            'enabled' => false,
            'priority' => 75,
            'ui_preferences' => [
                'display_name' => 'Custom OpenAI',
                'featured' => true,
            ],
        ];

        $response = $this->putJson('/api/providers/openai', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'enabled' => false,
                    'priority' => 75,
                ],
            ]);
    }

    public function test_can_store_credentials()
    {
        $credentialData = [
            'credentials' => [
                'api_key' => 'sk-test1234567890abcdef',
            ],
            'credential_type' => 'api_key',
            'test_on_create' => false, // Skip testing for unit test
        ];

        $response = $this->postJson('/api/providers/openai/credentials', $credentialData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('a_i_credentials', [
            'provider' => 'openai',
            'credential_type' => 'api_key',
            'is_active' => true,
        ]);
    }

    public function test_validates_credential_format()
    {
        $credentialData = [
            'credentials' => [
                'api_key' => 'invalid-key', // Should fail OpenAI validation
            ],
        ];

        $response = $this->postJson('/api/providers/openai/credentials', $credentialData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['credentials']);
    }

    public function test_can_list_credentials_for_provider()
    {
        $providerConfig = Provider::getOrCreateForProvider('openai');

        AICredential::storeCredentialsEnhanced(
            'openai',
            ['api_key' => 'sk-test1234567890abcdef'],
            'api_key'
        );

        $response = $this->getJson('/api/providers/openai/credentials');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'credential_type',
                        'is_active',
                        'credential_info',
                        'status',
                    ],
                ],
                'meta',
            ]);
    }

    public function test_credentials_are_masked_in_response()
    {
        $providerConfig = Provider::getOrCreateForProvider('openai');

        $credential = AICredential::storeCredentialsEnhanced(
            'openai',
            ['api_key' => 'sk-test1234567890abcdef'],
            'api_key'
        );

        $response = $this->getJson('/api/providers/openai/credentials');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);

        // Check that API key is masked
        $credentialInfo = $responseData[0]['credential_info'];
        $this->assertArrayHasKey('api_key', $credentialInfo);
        $this->assertStringContainsString('*', $credentialInfo['api_key']);
        $this->assertStringNotContainsString('sk-test1234567890abcdef', $credentialInfo['api_key']);
    }

    public function test_can_get_models_for_provider()
    {
        $response = $this->getJson('/api/providers/openai/models');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'model',
                        'name',
                        'type',
                        'capabilities',
                    ],
                ],
                'meta' => [
                    'provider',
                    'total_models',
                ],
            ]);
    }

    public function test_can_get_all_models()
    {
        $response = $this->getJson('/api/models');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'model',
                        'name',
                        'type',
                        'capabilities',
                    ],
                ],
                'meta',
            ]);
    }

    public function test_can_filter_models_by_type()
    {
        $response = $this->getJson('/api/models?type=text');

        $response->assertStatus(200);

        $models = $response->json('data');
        foreach ($models as $model) {
            $this->assertEquals('text', $model['type']);
        }
    }

    public function test_can_get_provider_statistics()
    {
        $response = $this->getJson('/api/providers/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_providers',
                    'enabled_providers',
                    'healthy_providers',
                    'total_usage',
                ],
            ]);
    }

    public function test_returns_404_for_unknown_provider()
    {
        $response = $this->getJson('/api/providers/unknown-provider');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
            ]);
    }
}
