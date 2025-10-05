<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Support\GitRunner;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class GitDiffTool extends Tool implements SummarizesTool
{
    protected string $name = 'git.diff';
    protected string $title = 'Git diff';
    protected string $description = 'Return a unified diff for a range, commit, PR (via gh), or working tree.';

    public function schema(JsonSchema $s): array
    {
        return [
            'cwd' => $s->string()->description('Repository root or subdir'),
            'range' => $s->string()->description('e.g., HEAD~1..HEAD or a commit'),
            'staged' => $s->boolean()->default(false),
            'paths' => $s->array()->items($s->string()),
            'unified' => $s->integer()->default(3),
            'pr_number' => $s->integer()->description('If set and gh is available, use gh pr diff {number}'),
        ];
    }

    public function handle(Request $r): Response
    {
        $cwd = $r->get('cwd');
        $unified = (int) $r->get('unified', 3);

        if ($r->has('pr_number') && GitRunner::hasGh()) {
            $num = (string) $r->get('pr_number');
            $res = GitRunner::run(['gh', 'pr', 'diff', $num], null, $cwd, 30.0);
            if (!$res->ok) return Response::error(trim($res->stderr ?: $res->stdout));
            return Response::json([ 'source' => 'gh', 'diff' => $res->stdout ]);
        }

        if (!GitRunner::hasGit()) return Response::error('git CLI not found on PATH');

        $cmd = ['git', 'diff', '-U' . $unified];
        if ($r->get('staged')) $cmd = ['git', 'diff', '--cached', '-U' . $unified];
        if ($r->has('range')) $cmd = array_merge(['git', 'diff', '-U' . $unified], explode(' ', (string) $r->get('range')));
        $paths = $r->get('paths', []);
        if (is_array($paths) && count($paths)) { $cmd[] = '--'; foreach ($paths as $p) $cmd[] = $p; }

        $res = GitRunner::run($cmd, null, $cwd, 30.0);
        if (!$res->ok) return Response::error(trim($res->stderr ?: $res->stdout));
        return Response::json([ 'source' => 'git', 'diff' => $res->stdout ]);
    }

    public static function name(): string { return 'git.diff'; }
    public static function title(): string { return 'Git diff'; }
    public static function shortDescription(): string { return 'Unified diff from git or gh PR.'; }
    public static function schemaSummary(): array
    {
        return [
            'range' => 'commit or A..B',
            'staged' => 'use --cached',
            'paths' => 'limit to files',
            'unified' => 'context lines',
            'pr_number' => 'use gh pr diff',
            'cwd' => 'repo dir',
        ];
    }
}
