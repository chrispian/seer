<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Servers\ToolCrateServer;
use HollisLabs\ToolCrate\Support\ToolRegistry;

class HelpIndexTool extends Tool
{
    protected string $name = 'help.index';
    protected string $title = 'List tools (prioritized)';
    protected string $description = 'Return top tools + categorized full list with short hints.';

    public function schema(JsonSchema $s): array
    {
        return [
            'limit' => $s->integer()->default(6),
        ];
    }

    public function handle(Request $r): Response
    {
        $server = new ToolCrateServer();
        $ref = new \ReflectionClass($server);
        $prop = $ref->getProperty('tools');
        $prop->setAccessible(true);
        $toolClasses = $prop->getValue($server);

        $info = ToolRegistry::summarize($toolClasses);
        $priority = array_values(array_filter(config('tool-crate.priority_tools', []), fn($n) => isset($info[$n])));
        $limit = (int) $r->get('limit', 6);
        $recommended = [];
        foreach ($priority as $name) {
            if (count($recommended) >= $limit) break;
            $recommended[] = $info[$name];
        }

        $cats = [];
        foreach (config('tool-crate.categories', []) as $cat => $names) {
            $list = [];
            foreach ($names as $n) {
                if (isset($info[$n])) $list[] = $info[$n];
            }
            if ($list) $cats[] = ['category' => $cat, 'tools' => $list];
        }

        return Response::json([
            'recommended' => $recommended,
            'categories' => $cats,
            'note' => "For details call: help.tool { name: '<tool-name>' }"
        ]);
    }
}
