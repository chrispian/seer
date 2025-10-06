<?php

namespace App\Tools;

use App\Contracts\ToolContract;
use App\Models\Artifact;
use App\Support\ToolRegistry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportGenerateTool implements ToolContract
{
    public function __construct(protected ToolRegistry $registry) {}

    public function name(): string
    {
        return 'export.generate';
    }

    public function scope(): string
    {
        return 'export.read';
    }

    public function inputSchema(): array
    {
        return $this->registry->loadContract('export.generate')['input_schema'] ?? [];
    }

    public function outputSchema(): array
    {
        return $this->registry->loadContract('export.generate')['output_schema'] ?? [];
    }

    public function run(array $payload): array
    {
        $this->registry->ensureScope($this->scope());

        $format = $payload['format'];
        $filename = 'export_'.Str::random(8).'.'.$format;
        $path = 'exports/'.$filename;

        // Minimal deterministic content for now; implement adapters later
        $content = json_encode([
            'entity' => $payload['entity'] ?? null,
            'query_ref' => $payload['query_ref'] ?? null,
            'params' => $payload['params'] ?? (object) [],
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT);

        if ($format === 'md') {
            $content = "# Export\n\n```json\n".$content."\n```\n";
        }
        if ($format === 'txt') {
            $content = $content;
        }

        Storage::put($path, $content);
        $abs = Storage::path($path);
        $sha = hash_file('sha256', $abs);

        $artifact = new Artifact;
        $artifact->type = 'export.'.$format;
        $artifact->mime = match ($format) {
            'md' => 'text/markdown',
            'txt' => 'text/plain',
            'json' => 'application/json',
            default => 'application/octet-stream'
        };
        $artifact->path = $abs;
        $artifact->sha256 = $sha;
        $artifact->created_by_tool = $this->name();
        $artifact->metadata = [
            'storage' => 'local',
            'relative_path' => $path,
        ];
        $artifact->save();

        return [
            'artifact_id' => (string) $artifact->id,
            'path' => $path,
            'mime' => $artifact->mime,
            'sha256' => $sha,
        ];
    }
}
