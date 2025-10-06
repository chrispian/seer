<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Support\Exec;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class JqQueryTool extends Tool implements SummarizesTool
{
    protected string $name = 'json.query';
    protected string $title = 'Query JSON with jq';
    protected string $description = 'Query JSON using jq; prefer this over ad-hoc parsing.';

    public function schema(JsonSchema $s): array
    {
        return [
            'program' => $s->string()->required(),
            'json'    => $s->string(),
            'file'    => $s->string(),
            'raw'     => $s->boolean()->default(false),
            'slurp'   => $s->boolean()->default(false),
        ];
    }

    public function handle(Request $r): Response
    {
        $args = [];
        if ($r->get('raw')) $args[] = '--raw-output';
        if ($r->get('slurp')) $args[] = '--slurp';
        $args[] = (string) $r->get('program');
        $stdin = null;
        if ($r->has('json')) $stdin = (string) $r->get('json');
        if ($r->has('file')) $args[] = (string) $r->get('file');

        $res = Exec::run(array_merge(['jq'], $args), $stdin, 8.0);
        if (!$res->ok) return Response::error(trim($res->stderr ?: $res->stdout));
        return Response::json(['stdout' => $res->stdout]);
    }

    public static function summaryName(): string { return 'json.query'; }
    public static function summaryTitle(): string { return 'Query JSON with jq'; }
    public static function summaryDescription(): string { return 'Query/transform JSON via jq.'; }
    public static function schemaSummary(): array
    {
        return [
            'program' => 'jq program',
            'json'    => 'inline JSON (optional)',
            'file'    => 'path to JSON file (optional)',
            'raw'     => 'emit raw strings',
            'slurp'   => 'accumulate inputs into array'
        ];
    }
}
