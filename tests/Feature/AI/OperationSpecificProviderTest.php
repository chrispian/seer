<?php

namespace Tests\Feature\AI;

use App\Services\AI\ModelSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OperationSpecificProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrichment_uses_operation_specific_provider()
    {
        // Set operation-specific provider for enrichment
        Config::set('fragments.models.operations.enrichment.provider', 'anthropic');
        Config::set('fragments.models.operations.enrichment.model', 'claude-3-5-sonnet-latest');
        Config::set('fragments.models.operations.enrichment.enabled', true);

        $modelSelection = app(ModelSelectionService::class);
        $context = [
            'operation_type' => 'text',
            'command' => 'enrich_fragment',
            'vault' => 'default',
            'project_id' => 1,
        ];

        $result = $modelSelection->selectTextModel($context);

        $this->assertEquals('anthropic', $result['provider']);
        $this->assertEquals('claude-3-5-sonnet-latest', $result['model']);
        $this->assertEquals('operation_specific', $result['source']);
    }

    public function test_classification_uses_operation_specific_provider()
    {
        // Set operation-specific provider for classification
        Config::set('fragments.models.operations.classification.provider', 'openai');
        Config::set('fragments.models.operations.classification.model', 'gpt-4o');
        Config::set('fragments.models.operations.classification.enabled', true);

        $modelSelection = app(ModelSelectionService::class);
        $context = [
            'operation_type' => 'text',
            'command' => 'type_inference',
            'vault' => 'default',
            'project_id' => 1,
        ];

        $result = $modelSelection->selectTextModel($context);

        $this->assertEquals('openai', $result['provider']);
        $this->assertEquals('gpt-4o', $result['model']);
        $this->assertEquals('operation_specific', $result['source']);
    }

    public function test_embedding_uses_operation_specific_provider()
    {
        // Set operation-specific provider for embeddings
        Config::set('fragments.models.operations.embedding.provider', 'ollama');
        Config::set('fragments.models.operations.embedding.model', 'nomic-embed-text');
        Config::set('fragments.models.operations.embedding.enabled', true);

        $modelSelection = app(ModelSelectionService::class);
        $context = [
            'operation_type' => 'embedding',
            'command' => 'embed_text',
            'vault' => 'default',
            'project_id' => 1,
        ];

        $result = $modelSelection->selectEmbeddingModel($context);

        $this->assertEquals('ollama', $result['provider']);
        $this->assertEquals('nomic-embed-text', $result['model']);
        $this->assertEquals('operation_specific', $result['source']);
    }

    public function test_disabled_operation_throws_exception()
    {
        // Disable enrichment operation
        Config::set('fragments.models.operations.enrichment.enabled', false);

        $modelSelection = app(ModelSelectionService::class);
        $context = [
            'operation_type' => 'text',
            'command' => 'enrich_fragment',
            'vault' => 'default',
            'project_id' => 1,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("AI operation 'enrichment' is disabled");

        $modelSelection->selectTextModel($context);
    }

    public function test_falls_back_to_default_when_no_operation_specific_config()
    {
        // Clear operation-specific config
        Config::set('fragments.models.operations.enrichment.provider', null);
        Config::set('fragments.models.operations.enrichment.model', null);
        Config::set('fragments.models.default_provider', 'openai');
        Config::set('fragments.models.default_text_model', 'gpt-4o-mini');

        $modelSelection = app(ModelSelectionService::class);
        $context = [
            'operation_type' => 'text',
            'command' => 'enrich_fragment',
            'vault' => 'default',
            'project_id' => 1,
        ];

        $result = $modelSelection->selectTextModel($context);

        // Should use default selection logic, not operation-specific
        $this->assertNotEquals('operation_specific', $result['source'] ?? '');
    }

    public function test_operation_specific_provider_only_without_model()
    {
        // Set only provider, not model for classification
        Config::set('fragments.models.operations.classification.provider', 'anthropic');
        Config::set('fragments.models.operations.classification.model', null);
        Config::set('fragments.models.operations.classification.enabled', true);

        $modelSelection = app(ModelSelectionService::class);
        $context = [
            'operation_type' => 'text',
            'command' => 'type_inference',
            'vault' => 'default',
            'project_id' => 1,
        ];

        $result = $modelSelection->selectTextModel($context);

        $this->assertEquals('anthropic', $result['provider']);
        // Should use default model for anthropic provider
        $this->assertNotEmpty($result['model']);
        $this->assertEquals('operation_specific', $result['source']);
    }

    public function test_operation_parameters_mapping()
    {
        $modelSelection = app(ModelSelectionService::class);

        // Test enrichment parameters
        $enrichmentContext = ['command' => 'enrich_fragment'];
        $enrichmentParams = $modelSelection->getAIParameters($enrichmentContext);
        $this->assertEquals(0.3, $enrichmentParams['temperature']);

        // Test classification parameters
        $classificationContext = ['command' => 'type_inference'];
        $classificationParams = $modelSelection->getAIParameters($classificationContext);
        $this->assertEquals(0.1, $classificationParams['temperature']);

        // Test tagging parameters
        $taggingContext = ['command' => 'suggest_tags'];
        $taggingParams = $modelSelection->getAIParameters($taggingContext);
        $this->assertEquals(0.2, $taggingParams['temperature']);

        // Test title generation parameters
        $titleContext = ['command' => 'generate_title'];
        $titleParams = $modelSelection->getAIParameters($titleContext);
        $this->assertEquals(0.1, $titleParams['temperature']);
    }
}
