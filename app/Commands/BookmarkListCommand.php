<?php

namespace App\Commands;

use App\Commands\Concerns\FormatsListData;

class BookmarkListCommand extends BaseCommand
{
    use FormatsListData;

    public function handle(): array
    {
        $bookmarks = $this->getBookmarks();
        return $this->respond(['items' => $bookmarks]);
    }

    private function getBookmarks(): array
    {
        if (! class_exists(\App\Models\Bookmark::class)) {
            return [];
        }

        $limit = $this->command?->pagination_default ?? 50;

        $bookmarks = \App\Models\Bookmark::query()
            ->orderBy('last_viewed_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($bookmark) => $this->formatBookmark($bookmark))
            ->filter(fn ($bookmark) => $bookmark['fragment_id'] !== null)
            ->values()
            ->all();

        return $bookmarks;
    }

    private function formatBookmark(\App\Models\Bookmark $bookmark): array
    {
        $firstFragment = $bookmark->first_fragment;

        return array_merge(
            $this->formatListItem($bookmark),
            [
                'name' => $bookmark->name,
                'fragment_id' => $firstFragment?->id,
                'fragment_title' => $firstFragment
                    ? \Illuminate\Support\Str::limit($firstFragment->message, 100)
                    : 'Fragment not found',
                'fragment_ids' => $bookmark->fragment_ids ?? [],
                'vault_id' => $bookmark->vault_id,
                'project_id' => $bookmark->project_id,
                'last_viewed_at' => $bookmark->last_viewed_at?->toISOString(),
                'last_viewed_human' => $bookmark->last_viewed_at?->diffForHumans(),
            ]
        );
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
