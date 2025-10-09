<?php

namespace App\Commands;

class BookmarkListCommand extends BaseCommand
{
    public function handle(): array
    {
        // Get recent bookmarks
        $bookmarks = $this->getBookmarks();

        return [
            'type' => 'bookmark',
            'component' => 'BookmarkListModal',
            'data' => $bookmarks,
        ];
    }

    private function getBookmarks(): array
    {
        if (class_exists(\App\Models\Bookmark::class)) {
            $bookmarks = \App\Models\Bookmark::query()
                ->orderBy('last_viewed_at', 'desc')
                ->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($bookmark) {
                    $firstFragment = $bookmark->first_fragment;

                    return [
                        'id' => $bookmark->id,
                        'name' => $bookmark->name,
                        'fragment_id' => $firstFragment?->id,
                        'fragment_title' => $firstFragment ? \Illuminate\Support\Str::limit($firstFragment->message, 100) : 'Fragment not found',
                        'fragment_ids' => $bookmark->fragment_ids ?? [],
                        'vault_id' => $bookmark->vault_id,
                        'project_id' => $bookmark->project_id,
                        'last_viewed_at' => $bookmark->last_viewed_at?->toISOString(),
                        'created_at' => $bookmark->created_at?->toISOString(),
                        'updated_at' => $bookmark->updated_at?->toISOString(),
                        'last_viewed_human' => $bookmark->last_viewed_at?->diffForHumans(),
                        'updated_human' => $bookmark->updated_at?->diffForHumans(),
                    ];
                })
                ->filter(function ($bookmark) {
                    return $bookmark['fragment_id'] !== null;
                })
                ->values()
                ->all();

            return $bookmarks;
        }

        return [];
    }

    public static function getName(): string
    {
        return 'Bookmark List';
    }

    public static function getDescription(): string
    {
        return 'List all bookmarks with recent activity';
    }

    public static function getUsage(): string
    {
        return '/bookmark';
    }

    public static function getCategory(): string
    {
        return 'Navigation';
    }
}
