<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Support\GitRunner;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class GitStatusTool extends Tool implements SummarizesTool
{
    protected string $name = 'git.status';
    protected string $title = 'Git status (porcelain)';
    protected string $description = 'Return git working tree status (porcelain v2 by default).';

    public function schema(JsonSchema $s): array
    {
        return [
            'cwd' => $s->string()->description('Repository root or subdir'),
            'porcelain' => $s->boolean()->default(true),
            'include_untracked' => $s->boolean()->default(true),
            'show_ignored' => $s->boolean()->default(false),
        ];
    }

    public function handle(Request $r): Response
    {
        if (!GitRunner::hasGit()) return Response::error('git CLI not found on PATH');
        $cwd = $r->get('cwd');
        $porcelain = (bool) $r->get('porcelain', true);
        $untracked = (bool) $r->get('include_untracked', true);
        $ignored = (bool) $r->get('show_ignored', false);

        $cmd = ['git', 'status'];
        if ($porcelain) $cmd = ['git', 'status', '--porcelain=v2', '-z'];
        if (!$untracked) $cmd[] = '--untracked-files=no';
        if ($ignored) $cmd[] = '--ignored';

        $res = GitRunner::run($cmd, null, $cwd, 15.0);
        if (!$res->ok) return Response::error(trim($res->stderr ?: $res->stdout));
        return Response::json([
            'gh_detected' => GitRunner::hasGh(),
            'output' => $res->stdout,
        ]);
    }

    public static function name(): string { return 'git.status'; }
    public static function title(): string { return 'Git status (porcelain)'; }
    public static function shortDescription(): string { return 'Working tree status; uses git CLI.'; }
    public static function schemaSummary(): array
    {
        return [
            'cwd' => 'repo directory',
            'porcelain' => 'use porcelain v2',
            'include_untracked' => 'include untracked files',
            'show_ignored' => 'include ignored files',
        ];
    }
}
