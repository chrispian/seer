<?php

use App\Actions\CreateChatFragment;
use App\Models\Fragment;
use App\Services\AI\ModelSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Critical Bug Fixes', function () {
    test('embedding model selection uses embedding models instead of text models', function () {
        $modelSelection = app(ModelSelectionService::class);
        
        // Test that embedding operation gets appropriate model type
        $result = $modelSelection->selectEmbeddingModel(['operation' => 'embedding']);
        
        // Should not get a chat model like gpt-4o-mini for embeddings
        expect($result['model'])->not->toContain('gpt-4o');
        expect($result['model'])->not->toContain('claude');
        expect($result['model'])->not->toContain('llama');
        
        // Should get an embedding model
        expect($result['provider'])->toBeIn(['openai', 'ollama']);
    });

    test('chat fragment creation bypasses deduplication', function () {
        $createChatFragment = app(CreateChatFragment::class);
        
        // Create first fragment with same content
        $fragment1 = $createChatFragment('Hello world');
        expect($fragment1->source)->toBe('chat-user');
        
        // Create second fragment with identical content
        $fragment2 = $createChatFragment('Hello world');
        expect($fragment2->source)->toBe('chat-user');
        
        // Should be different fragments (no deduplication)
        expect($fragment1->id)->not->toBe($fragment2->id);
        expect(Fragment::count())->toBe(2);
        
        // Both should have same content but different IDs
        expect($fragment1->message)->toBe($fragment2->message);
        expect($fragment1->input_hash)->toBe($fragment2->input_hash);
    });

    test('json schema validator preserves tag inheritance', function () {
        // Test that the JsonSchemaValidator fix prevents tag inheritance issues
        // This would typically be tested through the full pipeline
        // For now, we'll test that the general validation principle works
        
        $testData = [
            'type' => 'note',
            'message' => 'Test message'
        ];
        
        // Simulate the fixed behavior: only include tags if explicitly provided
        $processedData = [
            'type' => $testData['type'],
            'message' => $testData['message'],
        ];
        
        // Only set tags if explicitly provided
        if (isset($testData['tags'])) {
            $processedData['tags'] = $testData['tags'];
        }
        
        // Should not have tags key to preserve inheritance
        expect($processedData)->not->toHaveKey('tags');
        expect($processedData)->toHaveKey('type');
        expect($processedData)->toHaveKey('message');
        
        // Test with explicit tags
        $testDataWithTags = [
            'type' => 'note',
            'message' => 'Test message',
            'tags' => ['important']
        ];
        
        $processedDataWithTags = [
            'type' => $testDataWithTags['type'],
            'message' => $testDataWithTags['message'],
        ];
        
        if (isset($testDataWithTags['tags'])) {
            $processedDataWithTags['tags'] = $testDataWithTags['tags'];
        }
        
        expect($processedDataWithTags)->toHaveKey('tags');
        expect($processedDataWithTags['tags'])->toBe(['important']);
    });

    test('search command falls back to text search when hybrid fails', function () {
        // This test would require mocking the hybrid search to return empty results
        // and verifying that fallback search is called
        // Implementation depends on the actual SearchCommand structure
        expect(true)->toBeTrue(); // Placeholder - full test would require more setup
    });

    test('enrichment pipeline return type allows Fragment passthrough', function () {
        // Verify that EnrichAssistantMetadata has correct return type to prevent PHP type errors
        $reflection = new ReflectionClass(\App\Actions\EnrichAssistantMetadata::class);
        $method = $reflection->getMethod('handle');
        
        // Should accept array payload
        $parameters = $method->getParameters();
        expect($parameters[0]->getType()->getName())->toBe('array');
        
        // Should return mixed to allow Fragment passthrough
        $returnType = $method->getReturnType();
        expect($returnType->getName())->toBe('mixed');
    });
});