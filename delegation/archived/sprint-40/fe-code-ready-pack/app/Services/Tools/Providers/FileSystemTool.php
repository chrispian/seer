<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileSystemTool implements Tool
{
    public function slug(): string
    {
        return 'fs';
    }

    public function capabilities(): array
    {
        return ['read', 'write', 'list'];
    }

    protected function root(): string
    {
        return Config::get('fragments.tools.fs.root');
    }

    public function call(array $args, array $context = []): array
    {
        $op = $args['op'] ?? 'list';
        $path = $this->sanitize($args['path'] ?? '');

        switch ($op) {
            case 'list':
                $files = collect(File::files($this->root().'/'.$path))->map(fn ($f) => $f->getFilename())->values()->all();

                return ['files' => $files];
            case 'read':
                $full = $this->root().'/'.$path;

                return ['content' => File::exists($full) ? File::get($full) : null];
            case 'write':
                $full = $this->root().'/'.$path;
                File::ensureDirectoryExists(dirname($full));
                File::put($full, $args['content'] ?? '');

                return ['ok' => true];
            default:
                throw new \InvalidArgumentException('Unknown op');
        }
    }

    protected function sanitize(string $rel): string
    {
        $rel = Str::of($rel)->ltrim('/')->replace('..', '')->toString();

        return $rel;
    }
}
