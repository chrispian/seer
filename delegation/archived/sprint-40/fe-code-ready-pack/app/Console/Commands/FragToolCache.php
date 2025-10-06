<?php

namespace App\Console\Commands;

use App\Services\Tools\Providers\FileSystemTool;
use App\Services\Tools\Providers\GmailTool;
use App\Services\Tools\Providers\MCPTool;
use App\Services\Tools\Providers\ShellTool;
use App\Services\Tools\Providers\TodoistTool;
use App\Services\Tools\ToolRegistry;
use Illuminate\Console\Command;

class FragToolCache extends Command
{
    protected $signature = 'frag:tool:cache';

    protected $description = 'Register built-in tools (in-memory for this process)';

    public function handle(): int
    {
        $reg = app(ToolRegistry::class);
        $reg->register(new ShellTool);
        $reg->register(new FileSystemTool);
        $reg->register(new MCPTool);
        $reg->register(new GmailTool);
        $reg->register(new TodoistTool);
        $this->info('Registered tools: shell, fs, mcp, gmail, todoist');

        return self::SUCCESS;
    }
}
