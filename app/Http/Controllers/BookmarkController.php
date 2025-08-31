<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Fragment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookmarkController extends Controller
{
    public function checkBookmarkStatus($fragmentId)
    {
        $fragment = Fragment::find($fragmentId);
        if (! $fragment) {
            return response()->json(['error' => 'Fragment not found'], 404);
        }

        // Check if this fragment is bookmarked
        $bookmark = Bookmark::whereJsonContains('fragment_ids', (int) $fragmentId)->first();

        return response()->json([
            'fragment_id' => (int) $fragmentId,
            'is_bookmarked' => $bookmark !== null,
            'bookmark_name' => $bookmark ? $bookmark->name : null,
        ]);
    }

    public function toggleBookmark(Request $request, $fragmentId)
    {
        $fragment = Fragment::find($fragmentId);
        if (! $fragment) {
            return response()->json(['error' => 'Fragment not found'], 404);
        }

        // Check if this fragment is already bookmarked
        $existingBookmark = Bookmark::whereJsonContains('fragment_ids', (int) $fragmentId)->first();

        if ($existingBookmark) {
            // Remove from bookmark
            $fragmentIds = $existingBookmark->fragment_ids;
            $fragmentIds = array_values(array_filter($fragmentIds, fn ($id) => $id !== (int) $fragmentId));

            if (empty($fragmentIds)) {
                // Delete the bookmark if no fragments left
                $existingBookmark->delete();
                $action = 'removed';
                $bookmarkName = null;
            } else {
                // Update the bookmark
                $existingBookmark->fragment_ids = $fragmentIds;
                $existingBookmark->save();
                $action = 'removed';
                $bookmarkName = $existingBookmark->name;
            }
        } else {
            // Add to bookmarks - create a new bookmark for this fragment
            $title = Str::slug(substr($fragment->message, 0, 30)).'-'.now()->format('His');

            $bookmark = Bookmark::create([
                'name' => $title,
                'fragment_ids' => [(int) $fragmentId],
            ]);

            $action = 'added';
            $bookmarkName = $bookmark->name;
        }

        return response()->json([
            'fragment_id' => (int) $fragmentId,
            'action' => $action,
            'is_bookmarked' => $action === 'added',
            'bookmark_name' => $bookmarkName,
        ]);
    }

    public function getRecent(Request $request)
    {
        $limit = min($request->get('limit', 10), 20); // Max 20 items

        $bookmarks = Bookmark::query()
            ->orderBy('last_viewed_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($bookmark) {
                $firstFragment = $bookmark->first_fragment;

                return [
                    'id' => $bookmark->id,
                    'name' => $bookmark->name,
                    'fragment_id' => $firstFragment?->id,
                    'fragment_title' => $firstFragment ? Str::limit($firstFragment->message, 50) : 'Fragment not found',
                    'last_viewed_at' => $bookmark->last_viewed_at?->format('M j, Y g:i A'),
                    'created_at' => $bookmark->created_at?->format('M j, Y'),
                    'updated_at' => $bookmark->updated_at?->diffForHumans(),
                ];
            })
            ->filter(function ($bookmark) {
                return $bookmark['fragment_id'] !== null;
            })
            ->values();

        return response()->json(['bookmarks' => $bookmarks]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        // Minimum 2 characters required
        if (strlen($query) < 2) {
            return response()->json(['bookmarks' => []]);
        }

        $limit = min($request->get('limit', 10), 20); // Max 20 items

        $bookmarks = Bookmark::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('last_viewed_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($bookmark) {
                $firstFragment = $bookmark->first_fragment;

                return [
                    'id' => $bookmark->id,
                    'name' => $bookmark->name,
                    'fragment_id' => $firstFragment?->id,
                    'fragment_title' => $firstFragment ? Str::limit($firstFragment->message, 50) : 'Fragment not found',
                    'last_viewed_at' => $bookmark->last_viewed_at?->format('M j, Y g:i A'),
                    'created_at' => $bookmark->created_at->format('M j, Y'),
                    'updated_at' => $bookmark->updated_at->diffForHumans(),
                ];
            })
            ->filter(function ($bookmark) {
                return $bookmark['fragment_id'] !== null;
            })
            ->values();

        return response()->json(['bookmarks' => $bookmarks]);
    }

    public function markAsViewed($bookmarkId)
    {
        $bookmark = Bookmark::find($bookmarkId);
        if (! $bookmark) {
            return response()->json(['error' => 'Bookmark not found'], 404);
        }

        $bookmark->updateLastViewed();

        return response()->json(['success' => true]);
    }
}
