<?php

namespace App\Services\Obsidian;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class LinkResolver
{
    public function resolve(array $links, int $sourceFragmentId): array
    {
        if (empty($links)) {
            return [
                'resolved' => [],
                'orphans' => [],
                'stats' => [
                    'total' => 0,
                    'resolved' => 0,
                    'orphaned' => 0,
                ],
            ];
        }

        $lookupMap = $this->buildLookupMap();

        $resolved = [];
        $orphans = [];

        foreach ($links as $link) {
            $target = strtolower($link['target']);

            if (isset($lookupMap[$target])) {
                $targetFragmentId = $lookupMap[$target];

                if ($targetFragmentId !== $sourceFragmentId) {
                    $resolved[] = array_merge($link, [
                        'target_fragment_id' => $targetFragmentId,
                    ]);
                }
            } else {
                $orphans[] = $link;
                Log::debug('Orphan link detected', [
                    'source_fragment_id' => $sourceFragmentId,
                    'target' => $link['target'],
                ]);
            }
        }

        return [
            'resolved' => $resolved,
            'orphans' => $orphans,
            'stats' => [
                'total' => count($links),
                'resolved' => count($resolved),
                'orphaned' => count($orphans),
            ],
        ];
    }

    private function buildLookupMap(): array
    {
        return Fragment::where('source_key', 'obsidian')
            ->get()
            ->mapWithKeys(function ($fragment) {
                $path = $fragment->metadata['obsidian_path'] ?? '';
                $filename = $this->extractFilename($path);

                return [strtolower($filename) => $fragment->id];
            })
            ->toArray();
    }

    private function extractFilename(string $path): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);

        return $filename;
    }
}
