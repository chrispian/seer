<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Tools\ToolRegistry;
use App\Services\Tools\Providers\{ShellTool,FileSystemTool,MCPTool,GmailTool,TodoistTool};

class FragToolCache extends Command
{
    protected $signature = 'frag:tool:cache';
    protected $description = 'Register built-in tools (in-memory for this process)';

    public function handle(): int
    {
        $reg = app(ToolRegistry::class);
        $reg->register(new ShellTool());
        $reg->register(new FileSystemTool());
        $reg->register(new MCPTool());
        $reg->register(new GmailTool());
        $reg->register(new TodoistTool());
        $this->info('Registered tools: shell, fs, mcp, gmail, todoist');
        return self::SUCCESS;
    }
}
