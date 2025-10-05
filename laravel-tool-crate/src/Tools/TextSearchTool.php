<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\Finder\Finder;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class TextSearchTool extends Tool implements SummarizesTool
{
    protected string $name = 'text.search';
    protected string $title = 'Search text (grep-like)';
    protected string $description = 'Search files or text via regex/literal; optional context.';

    public function schema(JsonSchema $s): array
    {
        return [
            'pattern' => $s->string()->required(),
            'fixed' => $s->boolean()->default(false),
            'ignore_case' => $s->boolean()->default(false),
            'paths' => $s->array()->items($s->string()),
            'text' => $s->string(),
            'include_globs' => $s->array()->items($s->string()),
            'exclude_globs' => $s->array()->items($s->string()),
            'max_matches' => $s->integer()->default(500),
            'before_context' => $s->integer()->default(0),
            'after_context' => $s->integer()->default(0),
        ];
    }

    public function handle(Request $r): Response
    {
        $pattern = (string) $r->get('pattern');
        $fixed = (bool) $r->get('fixed', false);
        $ignore = (bool) $r->get('ignore_case', false);
        $max = (int) $r->get('max_matches', 500);
        $before = (int) $r->get('before_context', 0);
        $after  = (int) $r->get('after_context', 0);

        $regex = $fixed ? '/' . preg_quote($pattern, '/') . '/' : '/' . $pattern . '/';
        if ($ignore) $regex .= 'i';

        $matches = [];

        // Inline text
        if ($r->has('text') && $r->get('text') !== null) {
            $lines = preg_split('/\r?\n/', (string) $r->get('text'));
            foreach ($lines as $i => $line) {
                if (preg_match($regex, $line)) {
                    $matches[] = [
                        'file' => null,
                        'line' => $i + 1,
                        'col' => 1,
                        'match' => $line,
                        'before' => $before > 0 ? array_slice($lines, max(0, $i - $before), $before) : [],
                        'after' => $after > 0 ? array_slice($lines, $i + 1, $after) : [],
                    ];
                    if (count($matches) >= $max) return Response::json(['matches' => $matches]);
                }
            }
        }

        // Files
        $paths = $r->get('paths', []);
        if (is_array($paths) && count($paths) > 0) {
            $finder = new Finder();
            $finder->in($paths)->ignoreDotFiles(true);
            $exclude = $r->get('exclude_globs', ['**/node_modules/**','**/.git/**','**/vendor/**']);
            foreach ($exclude as $ex) $finder->notPath($ex);
            $include = $r->get('include_globs', ['**/*']);
            $finder->files()->name($include);

            foreach ($finder as $file) {
                $contents = @file_get_contents($file->getRealPath());
                if ($contents === false) continue;
                $lines = preg_split('/\r?\n/', $contents);
                foreach ($lines as $i => $line) {
                    if (preg_match($regex, $line)) {
                        $matches[] = [
                            'file' => str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $file->getRealPath()),
                            'line' => $i + 1,
                            'col' => 1,
                            'match' => $line,
                            'before' => $before > 0 ? array_slice($lines, max(0, $i - $before), $before) : [],
                            'after' => $after > 0 ? array_slice($lines, $i + 1, $after) : [],
                        ];
                        if (count($matches) >= $max) return Response::json(['matches' => $matches]);
                    }
                }
            }
        }

        return Response::json(['matches' => $matches]);
    }

    public static function name(): string { return 'text.search'; }
    public static function title(): string { return 'Search text (grep-like)'; }
    public static function shortDescription(): string { return 'Regex/literal search with context.'; }
    public static function schemaSummary(): array
    {
        return [
            'pattern' => 'regex or fixed string',
            'fixed' => 'treat pattern as literal',
            'ignore_case' => 'case-insensitive',
            'paths' => 'files/dirs to search',
            'text' => 'inline text (optional)',
        ];
    }
}
