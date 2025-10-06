<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;

class FileReadTool extends Tool implements SummarizesTool
{
    protected string $name = 'file.read';
    protected string $title = 'Read file';
    protected string $description = 'Read a text file (UTF-8) with optional size cap & slice.';

    public function schema(JsonSchema $s): array
    {
        return [
            'path' => $s->string()->required(),
            'max_bytes' => $s->integer()->default(262144),
            'start' => $s->integer()->default(0),
            'end' => $s->integer()->nullable(),
        ];
    }

    public function handle(Request $r): Response
    {
        $path = (string) $r->get('path');
        if (!is_file($path)) return Response::error("File not found: {$path}");
        $size = filesize($path);
        $max = (int) $r->get('max_bytes', 262144);
        $start = (int) $r->get('start', 0);
        $end = $r->get('end');

        $fh = fopen($path, 'rb');
        if ($fh === false) return Response::error("Unable to open file: {$path}");
        try {
            $toRead = min($size, $max);
            $data = fread($fh, $toRead);
        } finally {
            fclose($fh);
        }
        $slice = $data;
        if ($end !== null || $start > 0) {
            $slice = substr($data, $start, $end !== null ? ($end - $start) : null);
        }
        return Response::json([
            'path' => $path,
            'size' => $size,
            'content' => $slice,
            'truncated' => $size > $max,
        ]);
    }

    public static function summaryName(): string { return 'file.read'; }
    public static function summaryTitle(): string { return 'Read file'; }
    public static function summaryDescription(): string { return 'Read file content with size cap.'; }
    public static function schemaSummary(): array
    {
        return [
            'path' => 'file path',
            'max_bytes' => 'max bytes to read',
            'start' => 'slice start',
            'end' => 'slice end',
        ];
    }
}
