<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Bookmark;
use App\Models\Fragment;
use Illuminate\Support\Str;

class BookmarkCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        if (!empty($command->arguments['identifier']) && $command->arguments['identifier'] === 'list') {
            return $this->renderBookmarksList();
        }

        if (!empty($command->arguments['identifier']) && str_starts_with($command->arguments['identifier'], 'show ')) {
            $hint = trim(str_replace('show ', '', $command->arguments['identifier']));
            return $this->renderBookmarkShow($hint);
        }

        if (!empty($command->arguments['identifier']) && Str::contains($command->arguments['identifier'], ['forget', 'rm', 'del'])) {
            if (preg_match('/^(forget|del|rm)\s+(.*)$/i', $command->arguments['identifier'], $matches)) {
                $hint = $matches[2]; // group 2 is after forget|del|rm
                return $this->renderBookmarkForget(trim($hint));
            }
        }

        // Otherwise, create a new bookmark
        return $this->createBookmark();
    }

    protected function renderBookmarksList(): CommandResponse
    {
        $bookmarks = Bookmark::orderByDesc('created_at')->get();

        if ($bookmarks->isEmpty()) {
            return new CommandResponse(
                message: "📑 No bookmarks found.",
                type: 'system'
            );
        }

        $lines = [];
        foreach ($bookmarks as $bookmark) {
            $lines[] = "- `{$bookmark->name}` (" . count($bookmark->fragment_ids) . " fragment" . (count($bookmark->fragment_ids) > 1 ? 's' : '') . ")";
        }

        $message = "📑 Bookmarks:\n" . implode("\n", $lines);

        return new CommandResponse(
            message: $message,
            type: 'system'
        );
    }

    protected function renderBookmarkShow(string $hint): CommandResponse
    {
        $bookmark = Bookmark::where('name', 'like', "%{$hint}%")->orderByDesc('created_at')->first();

        if (!$bookmark) {
            return new CommandResponse(
                message: "🔎 No bookmark found matching `{$hint}`.",
                type: 'system'
            );
        }

        $fragments = Fragment::whereIn('id', $bookmark->fragment_ids)
            ->orderByRaw("FIELD(id, " . implode(',', $bookmark->fragment_ids) . ")")
            ->get();

        if ($fragments->isEmpty()) {
            return new CommandResponse(
                message: "🔎 Bookmark `{$bookmark->name}` exists but no fragments found.",
                type: 'system'
            );
        }

        $message = "🔖 Showing bookmark `{$bookmark->name}` (" . count($fragments) . " fragment" . (count($fragments) > 1 ? 's' : '') . "):\n";

        foreach ($fragments as $fragment) {
            $message .= "- " . trim(Str::limit($fragment->message, 80)) . "\n";
        }

        return new CommandResponse(
            message: trim($message),
            type: 'system'
        );
    }

    protected function renderBookmarkForget(string $hint): CommandResponse
    {
        $bookmark = Bookmark::where('name', 'like', "%{$hint}%")->orderByDesc('created_at')->first();

        if (!$bookmark) {
            return new CommandResponse(
                message: "❌ No bookmark found matching `{$hint}` to forget.",
                type: 'system'
            );
        }

        $name = $bookmark->name;
        $bookmark->delete();

        return new CommandResponse(
            message: "🗑️ Bookmark `{$name}` has been forgotten.",
            type: 'system'
        );
    }


    protected function createBookmark(): CommandResponse
    {
        $lastFragment = Fragment::latest()->first();

        if (!$lastFragment) {
            return new CommandResponse(
                message: "⚡ No fragments found to bookmark.",
                type: 'system'
            );
        }

        $fragmentIds = [];

        if ($lastFragment->type === 'chaos' && isset($lastFragment->metadata['children'])) {
            $fragmentIds = $lastFragment->metadata['children'];
        } else {
            $fragmentIds = [$lastFragment->id];
        }

        $title = Str::slug(substr($lastFragment->message, 0, 30)) . '-' . now()->format('His');

        Bookmark::create([
            'name' => $title,
            'fragment_ids' => $fragmentIds,
        ]);

        return new CommandResponse(
            message: "📌 Bookmarked as `{$title}` (" . count($fragmentIds) . " fragment" . (count($fragmentIds) > 1 ? 's' : '') . ").",
            type: 'system'
        );
    }
}
