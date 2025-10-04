<?php

namespace App\Console\Commands\Tools;

use App\Services\Tools\ToolRegistry;
use Illuminate\Console\Command;

class TestToolCommand extends Command
{
    protected $signature = 'frag:tools:test {tool : Tool slug to test} {--args= : JSON arguments for the tool}';

    protected $description = 'Test a tool with sample arguments';

    public function __construct(protected ToolRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $toolSlug = $this->argument('tool');
        $argsJson = $this->option('args') ?? '{}';

        try {
            $args = json_decode($argsJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->error("Invalid JSON arguments: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (! $this->registry->exists($toolSlug)) {
            $this->error("Tool not found: {$toolSlug}");
            $this->info('Available tools: '.implode(', ', array_keys($this->registry->all())));

            return self::FAILURE;
        }

        if (! $this->registry->allowed($toolSlug)) {
            $this->error("Tool not allowed: {$toolSlug}");
            $this->info('Check fragments.tools.allowed configuration');

            return self::FAILURE;
        }

        $tool = $this->registry->get($toolSlug);

        if (! $tool->isEnabled()) {
            $this->error("Tool is disabled: {$toolSlug}");
            $this->info("Check tool-specific configuration in fragments.tools.{$toolSlug}");

            return self::FAILURE;
        }

        $this->info("Testing tool: {$toolSlug}");
        $this->info('Arguments: '.json_encode($args, JSON_PRETTY_PRINT));
        $this->newLine();

        try {
            $start = microtime(true);
            $result = $tool->call($args, ['user' => ['id' => 1]]);
            $duration = round((microtime(true) - $start) * 1000, 2);

            $this->info('✅ Tool executed successfully');
            $this->info("Duration: {$duration}ms");
            $this->newLine();

            $this->info('Result:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Tool execution failed');
            $this->error("Error: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
