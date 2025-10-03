<?php

namespace App\Console\Commands\Commands;

use App\Services\Commands\DSL\CommandRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestCommandPackCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'frag:command:test {slug : Command pack slug} {sample? : Path to sample JSON file} {--dry : Run in dry mode}';

    /**
     * The console command description.
     */
    protected $description = 'Test command pack execution with sample data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');
        $samplePath = $this->argument('sample');
        $dryRun = $this->option('dry');

        $runner = app(CommandRunner::class);

        // Load sample context
        $context = $this->loadSampleContext($slug, $samplePath);
        if ($context === null) {
            return self::FAILURE;
        }

        $this->info("Testing command pack '{$slug}'" . ($dryRun ? ' (dry run)' : ''));
        $this->line("Context: " . json_encode($context, JSON_PRETTY_PRINT));
        $this->newLine();

        try {
            $execution = $runner->execute($slug, $context, $dryRun);
            $this->displayExecutionResult($execution);

            return $execution['success'] ? self::SUCCESS : self::FAILURE;

        } catch (\Exception $e) {
            $this->error("Execution failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Load sample context from file or use default
     */
    protected function loadSampleContext(string $slug, ?string $samplePath): ?array
    {
        if ($samplePath) {
            // Load from specified file
            if (!File::exists($samplePath)) {
                $this->error("Sample file not found: {$samplePath}");
                return null;
            }

            try {
                $content = File::get($samplePath);
                return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->error("Invalid JSON in sample file: {$e->getMessage()}");
                return null;
            }
        }

        // Look for built-in samples
        $builtInSamplePath = base_path("fragments/commands/{$slug}/samples/basic.json");
        if (File::exists($builtInSamplePath)) {
            try {
                $content = File::get($builtInSamplePath);
                return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->warn("Invalid JSON in built-in sample, using default context");
            }
        }

        // Use default context
        return [
            'ctx' => [
                'body' => 'Test input for ' . $slug,
                'selection' => '',
                'user' => ['id' => 1, 'name' => 'Test User'],
                'workspace' => ['id' => 1],
                'session' => ['id' => 'test-session'],
            ]
        ];
    }

    /**
     * Display execution result
     */
    protected function displayExecutionResult(array $execution): void
    {
        $this->line("<info>🎯 Execution Result:</info>");
        $this->line("  Command: {$execution['command']}");
        $this->line("  Success: " . ($execution['success'] ? '✅ Yes' : '❌ No'));
        
        if ($execution['error']) {
            $this->line("  Error: {$execution['error']}");
        }

        $this->newLine();
        $this->line("<info>📝 Steps:</info>");

        foreach ($execution['steps'] as $step) {
            $status = $step['success'] ? '✅' : '❌';
            $this->line("  {$status} {$step['id']} ({$step['type']}) - {$step['duration_ms']}ms");
            
            if ($step['error']) {
                $this->line("    Error: {$step['error']}");
            }
            
            if ($step['output']) {
                $output = is_array($step['output']) 
                    ? json_encode($step['output'], JSON_PRETTY_PRINT)
                    : $step['output'];
                $this->line("    Output: " . substr($output, 0, 200) . (strlen($output) > 200 ? '...' : ''));
            }
        }

        if ($execution['dry_run']) {
            $this->newLine();
            $this->warn('🔍 This was a dry run - no actual changes were made.');
        }
    }
}
