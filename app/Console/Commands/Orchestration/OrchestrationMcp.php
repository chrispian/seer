<?php

namespace App\Console\Commands\Orchestration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('orchestration:mcp', 'Fragments Engine Orchestration MCP Server')]
class OrchestrationMcp extends Command
{
    protected $signature = 'orchestration:mcp';

    protected $description = 'Fragments Engine Orchestration MCP Server';

    public function handle(): int
    {
        return Artisan::call('mcp:start', ['handle' => 'orchestration']);
    }
}
