<?php

namespace App\Console\Commands\Telemetry;

use App\Services\Telemetry\TelemetryAdapter;
use App\Services\Telemetry\TelemetrySink;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TelemetryTestCommand extends Command
{
    protected $signature = 'telemetry:test
                           {--count=10 : Number of test events to generate}
                           {--components=tool,command,fragment,chat : Comma-separated list of components to test}';

    protected $description = 'Generate sample telemetry data for testing';

    protected TelemetrySink $sink;

    protected TelemetryAdapter $adapter;

    public function __construct(TelemetrySink $sink, TelemetryAdapter $adapter)
    {
        parent::__construct();
        $this->sink = $sink;
        $this->adapter = $adapter;
    }

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $components = explode(',', $this->option('components'));

        $this->info("🧪 Generating {$count} test telemetry events for components: ".implode(', ', $components));
        $this->line('');

        // Temporarily disable async processing for test data
        config(['telemetry.performance.async_processing' => false]);

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $component = $components[array_rand($components)];
            $this->generateTestEvent($component, $i);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');

        // Flush any buffered data
        $this->sink->flush();

        $this->info('✅ Test telemetry data generated successfully!');
        $this->line('');
        $this->info('You can now test the query system with:');
        $this->line('  php artisan telemetry:query');
        $this->line('  php artisan telemetry:health');

        return 0;
    }

    private function generateTestEvent(string $component, int $index): void
    {
        $correlationId = 'test_'.uniqid();
        $timestamp = now()->subMinutes(rand(0, 60));

        match ($component) {
            'tool' => $this->generateToolEvent($correlationId, $timestamp, $index),
            'command' => $this->generateCommandEvent($correlationId, $timestamp, $index),
            'fragment' => $this->generateFragmentEvent($correlationId, $timestamp, $index),
            'chat' => $this->generateChatEvent($correlationId, $timestamp, $index),
            default => null
        };
    }

    private function generateToolEvent(string $correlationId, Carbon $timestamp, int $index): void
    {
        $toolNames = ['memory.search', 'db.query', 'export.generate', 'file.read', 'ai.generate'];
        $toolName = $toolNames[array_rand($toolNames)];
        $isError = rand(1, 100) <= 5; // 5% error rate
        $duration = $isError ? rand(5000, 15000) : rand(50, 2000);

        $data = [
            'correlation_id' => $correlationId,
            'tool_name' => $toolName,
            'tool_type' => explode('.', $toolName)[0],
            'operation' => $isError ? 'execute_with_error' : 'execute',
            'parameters' => [
                'query' => 'test query '.$index,
                'limit' => rand(10, 100),
            ],
            'result' => $isError ? null : ['status' => 'success', 'count' => rand(1, 50)],
            'performance' => [
                'duration_ms' => $duration,
                'memory_usage' => rand(10, 200) * 1024 * 1024, // 10-200MB
                'cpu_usage' => rand(5, 95),
            ],
            'context' => [
                'user_id' => 'test_user_'.rand(1, 5),
                'request_id' => 'req_'.uniqid(),
                'environment' => 'test',
            ],
            'level' => $isError ? 'error' : 'info',
            'error_message' => $isError ? 'Simulated tool execution error' : null,
        ];

        $eventName = $isError ? 'invocation_error' : 'invocation_complete';
        $this->adapter->adaptToolEvent($eventName, $data);

        // Generate some health checks
        if (rand(1, 10) === 1) {
            $this->adapter->recordHealthCheck(
                $toolName,
                'availability_check',
                ! $isError,
                $isError ? 'Tool is experiencing issues' : null,
                rand(50, 500),
                ['check_timestamp' => $timestamp->toISOString()]
            );
        }

        // Generate performance snapshots
        if (rand(1, 5) === 1) {
            $this->adapter->recordPerformanceSnapshot(
                $toolName,
                'execute',
                $duration,
                [
                    'memory_usage' => rand(10, 200) * 1024 * 1024,
                    'cpu_usage' => rand(5, 95),
                    'disk_io' => rand(100, 1000) * 1024,
                    'network_io' => rand(50, 500) * 1024,
                ]
            );
        }
    }

    private function generateCommandEvent(string $correlationId, Carbon $timestamp, int $index): void
    {
        $commands = ['process.fragment', 'sync.data', 'generate.report', 'analyze.metrics'];
        $commandName = $commands[array_rand($commands)];
        $steps = ['validate', 'process', 'transform', 'save'];
        $stepName = $steps[array_rand($steps)];
        $isError = rand(1, 100) <= 3; // 3% error rate
        $duration = $isError ? rand(3000, 10000) : rand(100, 2000);

        $data = [
            'correlation_id' => $correlationId,
            'command_name' => $commandName,
            'command_type' => 'dsl',
            'step_name' => $stepName,
            'step_type' => explode('.', $stepName)[0],
            'operation' => $isError ? 'step_error' : 'step_complete',
            'arguments' => [
                'input_file' => 'test_data_'.$index.'.json',
                'options' => ['verbose' => true],
            ],
            'template_data' => [
                'variables' => ['count' => $index, 'timestamp' => $timestamp->toISOString()],
            ],
            'result' => $isError ? null : ['processed' => rand(10, 100), 'skipped' => rand(0, 5)],
            'performance' => [
                'duration_ms' => $duration,
                'memory_usage' => rand(20, 300) * 1024 * 1024,
                'cpu_usage' => rand(10, 80),
            ],
            'context' => [
                'command_chain_id' => 'chain_'.substr($correlationId, -8),
                'user_id' => 'test_user_'.rand(1, 3),
            ],
            'level' => $isError ? 'error' : 'info',
            'error_message' => $isError ? 'Command step failed during processing' : null,
        ];

        $eventName = $isError ? 'execution_error' : 'execution_complete';
        $this->adapter->adaptCommandEvent($eventName, $data);
    }

    private function generateFragmentEvent(string $correlationId, Carbon $timestamp, int $index): void
    {
        $pipelines = ['full_processing', 'basic_processing', 'ai_only'];
        $pipelineName = $pipelines[array_rand($pipelines)];
        $steps = ['parse', 'extract_metadata', 'enrich_ai', 'embed'];
        $stepName = $steps[array_rand($steps)];
        $isError = rand(1, 100) <= 2; // 2% error rate
        $duration = $isError ? rand(2000, 8000) : rand(200, 3000);

        $data = [
            'correlation_id' => $correlationId,
            'fragment_id' => 'frag_'.$index,
            'pipeline_name' => $pipelineName,
            'step_name' => $stepName,
            'step_type' => $stepName,
            'fragment_type' => ['note', 'document', 'code', 'data'][array_rand(['note', 'document', 'code', 'data'])],
            'pipeline_stage' => rand(1, 5),
            'operation' => $isError ? 'processing_error' : 'processing_complete',
            'result' => $isError ? null : [
                'status' => 'processed',
                'entities_extracted' => rand(0, 10),
                'ai_score' => rand(70, 98) / 100,
            ],
            'performance' => [
                'duration_ms' => $duration,
                'memory_usage' => rand(15, 250) * 1024 * 1024,
                'cpu_usage' => rand(15, 85),
            ],
            'context' => [
                'pipeline_run_id' => 'run_'.substr($correlationId, -8),
                'batch_id' => 'batch_'.rand(1, 10),
            ],
            'level' => $isError ? 'error' : 'info',
            'error_message' => $isError ? 'Fragment processing pipeline error' : null,
        ];

        $eventName = $isError ? 'processing_error' : 'processing_complete';
        $this->adapter->adaptFragmentEvent($eventName, $data);
    }

    private function generateChatEvent(string $correlationId, Carbon $timestamp, int $index): void
    {
        $providers = ['openai', 'anthropic', 'azure'];
        $models = ['gpt-4', 'claude-3', 'gpt-3.5-turbo'];
        $provider = $providers[array_rand($providers)];
        $model = $models[array_rand($models)];
        $isError = rand(1, 100) <= 1; // 1% error rate
        $responseTime = $isError ? rand(10000, 30000) : rand(500, 5000);
        $tokensUsed = $isError ? 0 : rand(100, 2000);

        $data = [
            'correlation_id' => $correlationId,
            'session_id' => 'session_'.rand(1, 20),
            'message_id' => 'msg_'.$index,
            'user_id' => 'user_'.rand(1, 5),
            'ai_provider' => $provider,
            'model_name' => $model,
            'tokens_used' => $tokensUsed,
            'operation' => $isError ? 'response_error' : 'response_complete',
            'response_quality' => $isError ? null : rand(70, 95) / 100,
            'performance' => [
                'response_time_ms' => $responseTime,
                'memory_usage' => rand(50, 150) * 1024 * 1024,
                'api_latency_ms' => rand(200, 2000),
            ],
            'context' => [
                'conversation_length' => rand(1, 20),
                'message_type' => ['question', 'command', 'clarification'][array_rand(['question', 'command', 'clarification'])],
            ],
            'level' => $isError ? 'error' : 'info',
            'error_message' => $isError ? 'AI provider API error' : null,
        ];

        $eventName = $isError ? 'response_error' : 'response_complete';
        $this->adapter->adaptChatEvent($eventName, $data);
    }
}
