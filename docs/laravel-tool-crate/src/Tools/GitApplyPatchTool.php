<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Support\GitRunner;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class GitApplyPatchTool extends Tool implements SummarizesTool
{
    protected string $name = 'git.apply_patch';
    protected string $title = 'Git apply patch';
    protected string $description = 'Apply a unified diff patch; defaults to check-only for safety.';

    public function schema(JsonSchema $s): array
    {
        return [
            'cwd' => $s->string()->description('Repository root or subdir'),
            'patch' => $s->string()->required()->description('Unified diff text'),
            'check_only' => $s->boolean()->default(true),
            'three_way' => $s->boolean()->default(true),
            'index' => $s->boolean()->default(false),
        ];
    }

    public function handle(Request $r): Response
    {
        if (!GitRunner::hasGit()) return Response::error('git CLI not found on PATH');
        $cwd = $r->get('cwd');
        $patch = (string) $r->get('patch');
        $check = (bool) $r->get('check_only', true);
        $three = (bool) $r->get('three_way', true);
        $index = (bool) $r->get('index', false);

        $flags = [];
        if ($check) $flags[] = '--check';
        if ($three) $flags[] = '--3way';
        if ($index) $flags[] = '--index';

        $cmd = array_merge(['git', 'apply'], $flags);
        $res = GitRunner::run($cmd, $patch, $cwd, 30.0);
        if (!$res->ok) return Response::error(trim($res->stderr ?: $res->stdout));

        return Response::json([
            'applied' => !$check,
            'check_only' => $check,
            'stdout' => $res->stdout,
        ]);
    }

    public static function name(): string { return 'git.apply_patch'; }
    public static function title(): string { return 'Git apply patch'; }
    public static function shortDescription(): string { return 'Apply patch (defaults to check-only).'; }
    public static function schemaSummary(): array
    {
        return [
            'patch' => 'unified diff text',
            'check_only' => 'validate only',
            'three_way' => 'use --3way',
            'index' => 'update index',
            'cwd' => 'repo dir',
        ];
    }
}
