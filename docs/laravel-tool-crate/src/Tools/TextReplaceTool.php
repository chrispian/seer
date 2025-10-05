<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class TextReplaceTool extends Tool implements SummarizesTool
{
    protected string $name = 'text.replace';
    protected string $title = 'Replace text (diff preview)';
    protected string $description = 'Apply regex/literal replacement and return a unified diff preview (no write yet).';

    public function schema(JsonSchema $s): array
    {
        return [
            'pattern'     => $s->string()->required(),
            'replacement' => $s->string()->required(),
            'text'        => $s->string()->required(),
            'regex'       => $s->boolean()->default(true),
            'global'      => $s->boolean()->default(true),
            'ignore_case' => $s->boolean()->default(false),
        ];
    }

    public function handle(Request $r): Response
    {
        $pattern = (string) $r->get('pattern');
        $replacement = (string) $r->get('replacement');
        $text = (string) $r->get('text');
        $regex = (bool) $r->get('regex', true);
        $global = (bool) $r->get('global', true);
        $ignore = (bool) $r->get('ignore_case', false);

        $flags = ($global ? 'g' : '') . ($ignore ? 'i' : '');
        if (!$regex) {
            $pattern = '/' . preg_quote($pattern, '/') . '/' . $flags;
        } else {
            $pattern = '/' . $pattern . '/' . $flags;
        }
        $after = preg_replace($pattern, $replacement, $text) ?? $text;
        $diff = self::unifiedDiff($text, $after, 'inline');

        return Response::json([
            'preview_diff' => $diff,
            'changed' => $after !== $text,
            'updated_text' => $after,
        ]);
    }

    private static function unifiedDiff(string $before, string $after, string $file): string
    {
        $b = explode("\n", $before);
        $a = explode("\n", $after);
        $out = [];
        $out[] = '--- a/' . $file;
        $out[] = '+++ b/' . $file;
        $out[] = '@@ 1,' . count($b) . ' 1,' . count($a) . ' @@';
        foreach ($b as $line) $out[] = '-' . $line;
        foreach ($a as $line) $out[] = '+' . $line;
        return implode("\n", $out) . "\n";
    }

    public static function name(): string { return 'text.replace'; }
    public static function title(): string { return 'Replace text (diff preview)'; }
    public static function shortDescription(): string { return 'Regex/literal replace; returns diff.'; }
    public static function schemaSummary(): array
    {
        return [
            'pattern' => 'regex or literal pattern',
            'replacement' => 'replacement string',
            'text' => 'input text to transform',
        ];
    }
}
