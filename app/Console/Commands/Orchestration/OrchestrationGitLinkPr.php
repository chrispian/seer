<?php

namespace App\Console\Commands\Orchestration;

use App\Services\Orchestration\OrchestrationGitService;
use Illuminate\Console\Command;

class OrchestrationGitLinkPr extends Command
{
    protected $signature = 'orchestration:git-link-pr
                            {task_code : The task code to link PR to}
                            {pr_url : The pull request URL}';

    protected $description = 'Link a pull request to an orchestration task';

    public function handle(OrchestrationGitService $gitService): int
    {
        $taskCode = $this->argument('task_code');
        $prUrl = $this->argument('pr_url');

        try {
            $gitService->linkPullRequest($taskCode, $prUrl);

            $this->info("✓ Pull request linked to task: {$taskCode}");
            $this->line("  URL: {$prUrl}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to link PR: {$e->getMessage()}");
            return 1;
        }
    }
}
