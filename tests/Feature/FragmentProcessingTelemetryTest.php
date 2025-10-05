<?php

namespace Tests\Feature;

use App\Decorators\TelemetryPipelineDecorator;
use App\Models\Fragment;
use App\Services\Telemetry\TelemetryPipelineBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FragmentProcessingTelemetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable telemetry for tests
        config(['fragment-telemetry.enabled' => true]);

        // Mock the log channel to capture telemetry
        Log::spy();
    }

    /** @test */
    public function it_can_wrap_single_action_with_telemetry()
    {
        $fragment = Fragment::factory()->create(['message' => 'test message']);

        $parseAction = app(\App\Actions\ParseAtomicFragment::class);
        $decoratedAction = TelemetryPipelineDecorator::wrap($parseAction, 'test_parse');

        $result = $decoratedAction($fragment);

        $this->assertInstanceOf(Fragment::class, $result);

        // Verify telemetry logs were generated
        Log::shouldHaveReceived('info')
            ->with('🔄 Fragment processing step started', \Mockery::type('array'))
            ->once();

        Log::shouldHaveReceived('info')
            ->with('✅ Fragment processing step completed', \Mockery::type('array'))
            ->once();
    }

    /** @test */
    public function it_logs_step_failures_with_proper_context()
    {
        $fragment = Fragment::factory()->create(['message' => 'test message']);

        // Create a mock action that will throw an exception
        $mockAction = new class
        {
            public function handle($fragment, $next)
            {
                throw new \Exception('Test failure');
            }

            public function __invoke($fragment)
            {
                throw new \Exception('Test failure');
            }
        };

        $decoratedAction = TelemetryPipelineDecorator::wrap($mockAction, 'failing_step');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test failure');

        try {
            $decoratedAction($fragment);
        } finally {
            // Verify error telemetry was logged
            Log::shouldHaveReceived('error')
                ->with('❌ Fragment processing step failed', \Mockery::type('array'))
                ->once();
        }
    }

    /** @test */
    public function it_can_build_and_execute_complete_pipeline_with_telemetry()
    {
        $fragment = Fragment::factory()->create(['message' => 'todo: finish the report #urgent']);

        $result = TelemetryPipelineBuilder::create()
            ->addStep(\App\Actions\ParseAtomicFragment::class)
            ->addStep(\App\Actions\ExtractMetadataEntities::class)
            ->addStep(\App\Actions\GenerateAutoTitle::class)
            ->withContext(['test_pipeline' => true])
            ->process($fragment);

        $this->assertInstanceOf(Fragment::class, $result);
        $this->assertEquals('todo', $result->type);
        $this->assertContains('urgent', $result->tags);

        // Verify pipeline telemetry logs
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Fragment processing step started') ||
                       str_contains($message, 'Fragment processing step completed');
            })
            ->atLeast(6); // 3 steps × 2 logs each (start + complete)
    }

    /** @test */
    public function it_uses_standard_pipeline_preset()
    {
        $fragment = Fragment::factory()->create(['message' => 'test note about project']);

        $result = TelemetryPipelineBuilder::standard()
            ->withContext(['preset' => 'standard'])
            ->process($fragment);

        $this->assertInstanceOf(Fragment::class, $result);

        // Verify all standard pipeline steps were logged
        $expectedSteps = ['DriftSync', 'ParseAtomicFragment', 'ExtractMetadataEntities',
            'GenerateAutoTitle', 'EnrichFragmentWithAI', 'InferFragmentType',
            'SuggestTags', 'RouteToVault', 'EmbedFragmentAction'];

        foreach ($expectedSteps as $step) {
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message, $context) use ($step) {
                    return str_contains($message, 'Fragment processing step started') &&
                           isset($context['data']['step_name']) &&
                           $context['data']['step_name'] === $step;
                })
                ->atLeast(1);
        }
    }

    /** @test */
    public function it_uses_lightweight_pipeline_preset()
    {
        $fragment = Fragment::factory()->create(['message' => 'simple note']);

        $result = TelemetryPipelineBuilder::lightweight()
            ->process($fragment);

        $this->assertInstanceOf(Fragment::class, $result);

        // Verify only lightweight steps were executed
        $lightweightSteps = ['ParseAtomicFragment', 'ExtractMetadataEntities', 'GenerateAutoTitle', 'RouteToVault'];

        foreach ($lightweightSteps as $step) {
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message, $context) use ($step) {
                    return str_contains($message, 'Fragment processing step started') &&
                           isset($context['data']['step_name']) &&
                           $context['data']['step_name'] === $step;
                })
                ->atLeast(1);
        }

        // Verify AI steps were NOT executed
        $aiSteps = ['EnrichFragmentWithAI', 'InferFragmentType', 'SuggestTags'];

        foreach ($aiSteps as $step) {
            Log::shouldNotHaveReceived('info')
                ->withArgs(function ($message, $context) use ($step) {
                    return str_contains($message, 'Fragment processing step started') &&
                           isset($context['data']['step_name']) &&
                           $context['data']['step_name'] === $step;
                });
        }
    }

    /** @test */
    public function it_executes_single_action_with_telemetry()
    {
        $fragment = Fragment::factory()->create(['message' => 'parse this message']);

        $result = TelemetryPipelineBuilder::executeAction(
            \App\Actions\ParseAtomicFragment::class,
            $fragment,
            ['single_action' => true]
        );

        $this->assertInstanceOf(Fragment::class, $result);

        // Verify single action telemetry
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Fragment processing step started') &&
                       isset($context['data']['step_name']) &&
                       $context['data']['step_name'] === 'ParseAtomicFragment';
            })
            ->once();
    }

    /** @test */
    public function it_captures_performance_metrics()
    {
        $fragment = Fragment::factory()->create(['message' => 'performance test']);

        TelemetryPipelineBuilder::lightweight()
            ->withContext(['performance_test' => true])
            ->process($fragment);

        // Verify performance data is captured
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Fragment processing step completed') &&
                       isset($context['data']['duration_ms']) &&
                       isset($context['data']['performance_tier']) &&
                       in_array($context['data']['performance_tier'], ['fast', 'normal', 'slow', 'very_slow']);
            })
            ->atLeast(1);
    }

    /** @test */
    public function it_can_disable_telemetry()
    {
        config(['fragment-telemetry.enabled' => false]);

        $fragment = Fragment::factory()->create(['message' => 'no telemetry test']);

        $result = TelemetryPipelineBuilder::lightweight()
            ->withTelemetry(false)
            ->process($fragment);

        $this->assertInstanceOf(Fragment::class, $result);

        // Should not have received any telemetry logs when disabled
        Log::shouldNotHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Fragment processing step');
            });
    }

    /** @test */
    public function it_chains_multiple_decorated_actions()
    {
        $fragment = Fragment::factory()->create(['message' => 'chain test message']);

        $actions = [
            \App\Actions\ParseAtomicFragment::class,
            [\App\Actions\ExtractMetadataEntities::class, 'custom_extract', ['custom' => true]],
            \App\Actions\GenerateAutoTitle::class,
        ];

        $result = TelemetryPipelineBuilder::executeActions(
            $actions,
            $fragment,
            ['chain_test' => true]
        );

        $this->assertInstanceOf(Fragment::class, $result);

        // Verify all actions in chain were executed with telemetry
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Fragment processing step completed');
            })
            ->times(3); // One for each action
    }

    /** @test */
    public function it_includes_correlation_context_when_available()
    {
        $fragment = Fragment::factory()->create(['message' => 'correlation test']);

        // Set correlation context
        \App\Services\Telemetry\CorrelationContext::set('test-correlation-id');
        \App\Services\Telemetry\CorrelationContext::addContext('test_context', 'test_value');

        TelemetryPipelineBuilder::executeAction(
            \App\Actions\ParseAtomicFragment::class,
            $fragment
        );

        // Verify correlation context is included
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Fragment processing step started') &&
                       isset($context['correlation']) &&
                       $context['correlation']['correlation_id'] === 'test-correlation-id';
            })
            ->once();

        // Clean up
        \App\Services\Telemetry\CorrelationContext::clear();
    }

    /** @test */
    public function it_logs_fragment_state_changes()
    {
        $fragment = Fragment::factory()->create([
            'message' => 'todo: test state changes #priority',
            'type' => null,
            'tags' => [],
            'title' => null,
        ]);

        TelemetryPipelineBuilder::create()
            ->addStep(\App\Actions\ParseAtomicFragment::class)
            ->addStep(\App\Actions\GenerateAutoTitle::class)
            ->process($fragment);

        // The fragment should have changed (type, tags, title should now be set)
        $this->assertEquals('todo', $fragment->type);
        $this->assertContains('priority', $fragment->tags);
        $this->assertNotNull($fragment->title);
    }

    /** @test */
    public function it_handles_memory_usage_tracking()
    {
        $fragment = Fragment::factory()->create(['message' => 'memory tracking test']);

        TelemetryPipelineBuilder::executeAction(
            \App\Actions\ParseAtomicFragment::class,
            $fragment,
            ['memory_test' => true]
        );

        // Verify memory usage is tracked
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Fragment processing step completed') &&
                       isset($context['data']['memory_usage_mb']) &&
                       isset($context['data']['peak_memory_mb']) &&
                       is_numeric($context['data']['memory_usage_mb']);
            })
            ->once();
    }
}
