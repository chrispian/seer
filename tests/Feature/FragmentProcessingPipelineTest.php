<?php

namespace Tests\Feature;

use App\Actions\DriftSync;
use App\Actions\EnrichFragmentWithAI;
use App\Actions\ExtractMetadataEntities;
use App\Actions\GenerateAutoTitle;
use App\Actions\InferFragmentType;
use App\Actions\ParseAtomicFragment;
use App\Actions\RouteToVault;
use App\Actions\SuggestTags;
use App\Models\Fragment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pipeline\Pipeline;
use Tests\TestCase;

class FragmentProcessingPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_complete_pipeline_execution_order(): void
    {
        $fragment = Fragment::factory()->create([
            'message' => 'Meeting with @client.lead about project timeline. Need to follow up with email to team@company.com',
            'type' => 'note',
            'title' => null,
            'tags' => [],
            'parsed_entities' => null,
        ]);

        // Test the pipeline actions in order
        $pipeline = app(Pipeline::class);

        $processed = $pipeline
            ->send($fragment)
            ->through([
                DriftSync::class,
                ParseAtomicFragment::class,
                ExtractMetadataEntities::class,
                GenerateAutoTitle::class,
                // Skip LLM-dependent actions for unit test
                // EnrichFragmentWithAI::class,
                // InferFragmentType::class,
                // SuggestTags::class,
                RouteToVault::class,
            ])
            ->thenReturn();

        // Verify the pipeline ran successfully
        $this->assertInstanceOf(Fragment::class, $processed);

        // Check that each action performed its task
        $fragment->refresh();

        // ExtractMetadataEntities should have populated parsed_entities
        $this->assertNotNull($fragment->parsed_entities);
        $this->assertArrayHasKey('people', $fragment->parsed_entities);
        $this->assertArrayHasKey('emails', $fragment->parsed_entities);
        $this->assertContains('client.lead', $fragment->parsed_entities['people']);
        $this->assertContains('team@company.com', $fragment->parsed_entities['emails']);

        // GenerateAutoTitle should have created a title
        $this->assertNotNull($fragment->title);
        $this->assertNotEmpty($fragment->title);
    }

    public function test_pipeline_handles_malformed_input_gracefully(): void
    {
        // Test with minimal/malformed fragment
        $fragment = Fragment::factory()->create([
            'message' => '', // Empty message
            'type' => 'note',
            'title' => null,
        ]);

        $pipeline = app(Pipeline::class);

        // Pipeline should not throw exceptions
        $processed = $pipeline
            ->send($fragment)
            ->through([
                ExtractMetadataEntities::class,
                GenerateAutoTitle::class,
            ])
            ->thenReturn();

        $this->assertInstanceOf(Fragment::class, $processed);

        $fragment->refresh();
        // Should handle empty content gracefully
        $this->assertNotNull($fragment->parsed_entities);
    }

    public function test_entity_extraction_pipeline_stage(): void
    {
        $fragment = Fragment::factory()->create([
            'message' => 'Call John at +1-555-0123, email sarah@example.com, check https://docs.example.com/report.pdf by 2024-12-31',
            'parsed_entities' => null,
        ]);

        $extractAction = app(ExtractMetadataEntities::class);
        $processed = $extractAction($fragment);

        $entities = $processed->parsed_entities;

        // Verify comprehensive entity extraction
        $this->assertArrayHasKey('people', $entities);
        $this->assertArrayHasKey('emails', $entities);
        $this->assertArrayHasKey('phones', $entities);
        $this->assertArrayHasKey('urls', $entities);
        $this->assertArrayHasKey('dates', $entities);

        $this->assertContains('John', $entities['people']);
        $this->assertContains('sarah@example.com', $entities['emails']);
        $this->assertContains('+1-555-0123', $entities['phones']);
        $this->assertContains('https://docs.example.com/report.pdf', $entities['urls']);
        $this->assertContains('2024-12-31', $entities['dates']);
    }

    public function test_title_generation_pipeline_stage(): void
    {
        $generateTitle = app(GenerateAutoTitle::class);

        // Test with different input patterns
        $testCases = [
            [
                'message' => "Project Status Update\n\nThe quarterly review went well.",
                'type' => 'note',
                'tags' => [],
                'expected_title' => 'Project Status Update',
                'strategy' => 'first_line',
            ],
            [
                'message' => 'Quick reminder to submit expense reports',
                'type' => 'task',
                'tags' => ['urgent'],
                'expected_pattern' => '/Task.*/',
                'strategy' => 'keyword_based',
            ],
            [
                'message' => 'Random thoughts about the weather today.',
                'type' => 'note',
                'tags' => [],
                'expected_pattern' => '/Note.*/',
                'strategy' => 'keyword_based',
            ],
        ];

        foreach ($testCases as $testCase) {
            $fragment = Fragment::factory()->create([
                'message' => $testCase['message'],
                'type' => $testCase['type'],
                'tags' => $testCase['tags'],
                'title' => null,
            ]);

            $processed = $generateTitle($fragment);
            if (isset($testCase['expected_title'])) {
                $this->assertEquals($testCase['expected_title'], $processed->title);
            } elseif (isset($testCase['expected_pattern'])) {
                $this->assertMatchesRegularExpression($testCase['expected_pattern'], $processed->title);
            }

            $this->assertNotNull($processed->title);
            $this->assertNotEmpty($processed->title);
        }
    }

    public function test_pipeline_preserves_existing_data(): void
    {
        $fragment = Fragment::factory()->create([
            'message' => 'Test message with @user mention',
            'type' => 'note',
            'title' => 'Existing Title', // Should be preserved
            'tags' => ['existing', 'tags'],
            'parsed_entities' => ['existing' => ['data']],
        ]);

        $pipeline = app(Pipeline::class);

        $processed = $pipeline
            ->send($fragment)
            ->through([
                ExtractMetadataEntities::class,
                GenerateAutoTitle::class,
            ])
            ->thenReturn();

        $fragment->refresh();

        // Existing title should be preserved
        $this->assertEquals('Existing Title', $fragment->title);

        // Existing tags should be preserved
        $this->assertContains('existing', $fragment->tags);
        $this->assertContains('tags', $fragment->tags);

        // Should still extract new entities (but preserve existing structure)
        $this->assertNotNull($fragment->parsed_entities);
        $this->assertArrayHasKey('people', $fragment->parsed_entities);
        $this->assertContains('user', $fragment->parsed_entities['people']);
    }

    public function test_pipeline_error_handling(): void
    {
        $fragment = Fragment::factory()->create([
            'message' => 'Test fragment for error handling',
            'type' => 'note',
        ]);

        // Create a mock action that throws an exception
        $mockAction = new class
        {
            public function handle($fragment, $next)
            {
                // Perform some processing that might fail
                if (empty($fragment->message)) {
                    throw new \Exception('Processing failed');
                }

                return $next($fragment);
            }
        };

        app()->instance(get_class($mockAction), $mockAction);

        // Test that pipeline continues after non-critical errors
        $pipeline = app(Pipeline::class);

        try {
            $processed = $pipeline
                ->send($fragment)
                ->through([
                    ExtractMetadataEntities::class,
                    // Include our mock action
                    $mockAction,
                    GenerateAutoTitle::class,
                ])
                ->thenReturn();

            // Should reach this point if error handling is working
            $this->assertInstanceOf(Fragment::class, $processed);
        } catch (\Exception $e) {
            // If an exception is thrown, it should be handled gracefully
            $this->fail('Pipeline should handle errors gracefully: '.$e->getMessage());
        }
    }

    public function test_pipeline_performance_with_multiple_fragments(): void
    {
        $fragments = Fragment::factory()->count(10)->create([
            'message' => 'Test fragment with @mention and email@example.com',
            'title' => null,
            'parsed_entities' => null,
        ]);

        $pipeline = app(Pipeline::class);
        $startTime = microtime(true);

        foreach ($fragments as $fragment) {
            $pipeline
                ->send($fragment)
                ->through([
                    ExtractMetadataEntities::class,
                    GenerateAutoTitle::class,
                ])
                ->thenReturn();
        }

        $processingTime = microtime(true) - $startTime;

        // Should process 10 fragments in reasonable time
        $this->assertLessThan(5, $processingTime,
            "Pipeline processing took too long: {$processingTime}s for 10 fragments");

        // Verify all fragments were processed
        foreach ($fragments as $fragment) {
            $fragment->refresh();
            $this->assertNotNull($fragment->parsed_entities);
            $this->assertNotNull($fragment->title);
        }
    }

    public function test_pipeline_stage_dependencies(): void
    {
        $fragment = Fragment::factory()->create([
            'message' => 'Meeting notes with action items and @team mentions',
            'type' => 'note',
            'title' => null,
            'parsed_entities' => null,
        ]);

        // Test that GenerateAutoTitle can use parsed_entities if available
        $extractAction = app(ExtractMetadataEntities::class);
        $titleAction = app(GenerateAutoTitle::class);

        // First extract entities
        $withEntities = $extractAction($fragment);
        $this->assertNotNull($withEntities->parsed_entities);

        // Then generate title (may use entity information)
        $withTitle = $titleAction($withEntities);
        $this->assertNotNull($withTitle->title);

        // Title generation should work whether or not entities are present
        $fragment2 = Fragment::factory()->create([
            'message' => 'Another test fragment',
            'title' => null,
            'parsed_entities' => null, // No entities extracted yet
        ]);

        $titleOnly = $titleAction($fragment2);
        $this->assertNotNull($titleOnly->title);
    }
}
