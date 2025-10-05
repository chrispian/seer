<?php

namespace HollisLabs\ToolCrate\Servers;

use Laravel\Mcp\Server;
use HollisLabs\ToolCrate\Tools\JqQueryTool;
use HollisLabs\ToolCrate\Tools\TextSearchTool;
use HollisLabs\ToolCrate\Tools\FileReadTool;
use HollisLabs\ToolCrate\Tools\TextReplaceTool;
use HollisLabs\ToolCrate\Tools\HelpIndexTool;
use HollisLabs\ToolCrate\Tools\HelpToolDetail;

class ToolCrateServer extends Server
{
    protected string $name = 'ToolCrate';
    protected string $version = '0.1.0';
    protected string $instructions = 'Local dev toolbox. Prefer these tools for JSON, search, and files.';

    protected array $tools = [];

    public function __construct()
    {
        $map = [
            'json.query'   => JqQueryTool::class,
            'text.search'  => TextSearchTool::class,
            'file.read'    => FileReadTool::class,
            'text.replace' => TextReplaceTool::class,
            'help.index'   => HelpIndexTool::class,
            'help.tool'    => HelpToolDetail::class,
        ];

        foreach ($map as $name => $cls) {
            if (config('tool-crate.enabled_tools.' . $name, false)) {
                $this->tools[] = $cls;
            }
        }
    }
}
