<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Servers\ToolCrateServer;
use HollisLabs\ToolCrate\Support\ToolRegistry;

class HelpToolDetail extends Tool
{
    protected string $name = 'help.tool';
    protected string $title = 'Describe a tool';
    protected string $description = 'Return concise details for a named tool (schema summary + hint).';

    public function schema(JsonSchema $s): array
    {
        return [
            'name' => $s->string()->required()->description('Tool name, e.g., json.query'),
        ];
    }

    public function handle(Request $r): Response
    {
        $name = (string) $r->get('name');

        $server = new ToolCrateServer();
        $ref = new \ReflectionClass($server);
        $prop = $ref->getProperty('tools');
        $prop->setAccessible(true);
        $toolClasses = $prop->getValue($server);

        $info = ToolRegistry::summarize($toolClasses);
        if (!isset($info[$name])) return Response::error("Unknown tool: {$name}");

        $t = $info[$name];
        return Response::json([
            'name' => $t['name'],
            'title' => $t['title'],
            'description' => $t['description'],
            'schema' => $t['schema'],
            'hint' => $t['hint'],
        ]);
    }
}
