<?php

namespace App\Console\Commands\Orchestration;

use App\Services\Orchestration\OrchestrationPMToolsService;
use Illuminate\Console\Command;

class OrchestrationADRGenerate extends Command
{
    protected $signature = 'orchestration:adr-generate
                            {title : The title of the ADR}
                            {--deciders= : The decision makers (default: Development Team)}
                            {--context= : The context or problem statement}
                            {--decision= : The decision that was made}';

    protected $description = 'Generate an Architecture Decision Record (ADR) from template';

    public function handle(OrchestrationPMToolsService $pmToolsService): int
    {
        $title = $this->argument('title');
        $options = [
            'deciders' => $this->option('deciders'),
            'context' => $this->option('context'),
            'decision' => $this->option('decision'),
        ];

        $this->info("Generating ADR: {$title}");

        try {
            $result = $pmToolsService->generateADR($title, $options);

            $this->info("✓ ADR generated successfully");
            $this->line("  File: {$result['file_name']}");
            $this->line("  Path: {$result['file_path']}");
            $this->line("  Number: ADR-{$result['adr_number']}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate ADR: {$e->getMessage()}");
            return 1;
        }
    }
}
