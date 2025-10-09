<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('tool-crate:mcp', 'Laravel Tool Crate MCP Server')]
class ToolCrateMcp extends Command
{
    protected $signature = 'tool-crate:mcp';

    protected $description = 'Laravel Tool Crate MCP Server';

    public function handle(): int
    {
        return Artisan::call('mcp:start', ['handle' => 'laravel-tool-crate']);
    }
}
