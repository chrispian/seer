<?php

namespace App\Console\Commands\Orchestration;

use App\Services\Orchestration\OrchestrationPMToolsService;
use Illuminate\Console\Command;

class OrchestrationBugReport extends Command
{
    protected $signature = 'orchestration:bug-report
                            {title : The title of the bug report}
                            {--priority=P2 : Priority level (P0, P1, P2, P3)}
                            {--category= : Bug category (e.g., UI/UX, Backend, API)}
                            {--component= : Component name}
                            {--effort= : Estimated effort}
                            {--description= : Problem description}';

    protected $description = 'Create a bug report in delegation/backlog/';

    public function handle(OrchestrationPMToolsService $pmToolsService): int
    {
        $title = $this->argument('title');
        $priority = $this->option('priority');
        $options = [
            'category' => $this->option('category'),
            'component' => $this->option('component'),
            'effort' => $this->option('effort'),
            'description' => $this->option('description'),
        ];

        $this->info("Creating bug report: {$title}");

        try {
            $result = $pmToolsService->generateBugReport($title, $priority, $options);

            $this->info("✓ Bug report created successfully");
            $this->line("  File: {$result['file_name']}");
            $this->line("  Path: {$result['file_path']}");
            $this->line("  Priority: {$result['priority']}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create bug report: {$e->getMessage()}");
            return 1;
        }
    }
}
