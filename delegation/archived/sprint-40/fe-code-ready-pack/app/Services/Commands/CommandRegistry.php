<?php

namespace App\Services\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommandRegistry
{
    public function rebuild(): array
    {
        $paths = [
            config('fragments.paths.user_command_packs'),
            config('fragments.paths.command_packs'),
        ];

        $registered = [];

        foreach ($paths as $root) {
            if (! $root || ! is_dir($root)) {
                continue;
            }
            foreach (glob($root.'/*', GLOB_ONLYDIR) as $dir) {
                $manifest = $dir.'/command.yaml';
                if (! file_exists($manifest)) {
                    continue;
                }
                $slug = basename($dir);
                $stepsHash = substr(hash('sha256', file_get_contents($manifest)), 0, 16);
                DB::table('command_registry')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'id' => (string) Str::uuid(),
                        'version' => '1.0.0',
                        'source_path' => $dir,
                        'steps_hash' => $stepsHash,
                        'capabilities' => json_encode([]),
                        'requires_secrets' => json_encode([]),
                        'reserved' => false,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $registered[] = $slug;
            }
        }

        return $registered;
    }

    public function get(string $slug): array
    {
        $row = DB::table('command_registry')->where('slug', $slug)->first();
        if (! $row) {
            throw new \RuntimeException("Command pack not found: $slug");
        }
        $path = $row->source_path;
        $yaml = file_get_contents($path.'/command.yaml');

        return ['path' => $path, 'yaml' => $yaml];
    }
}
