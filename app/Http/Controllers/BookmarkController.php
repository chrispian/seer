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
        if (!$fragment) {
            return response()->json(['error' => 'Fragment not found'], 404);
        }

        // Check if this fragment is bookmarked
        $bookmark = Bookmark::whereJsonContains('fragment_ids', (int)$fragmentId)->first();
        
        return response()->json([
            'fragment_id' => (int)$fragmentId,
            'is_bookmarked' => $bookmark !== null,
            'bookmark_name' => $bookmark ? $bookmark->name : null
        ]);
    }

    public function toggleBookmark(Request $request, $fragmentId)
    {
        $fragment = Fragment::find($fragmentId);
        if (!$fragment) {
            return response()->json(['error' => 'Fragment not found'], 404);
        }

        // Check if this fragment is already bookmarked
        $existingBookmark = Bookmark::whereJsonContains('fragment_ids', (int)$fragmentId)->first();
        
        if ($existingBookmark) {
            // Remove from bookmark
            $fragmentIds = $existingBookmark->fragment_ids;
            $fragmentIds = array_values(array_filter($fragmentIds, fn($id) => $id !== (int)$fragmentId));
            
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
            $title = Str::slug(substr($fragment->message, 0, 30)) . '-' . now()->format('His');
            
            $bookmark = Bookmark::create([
                'name' => $title,
                'fragment_ids' => [(int)$fragmentId],
            ]);
            
            $action = 'added';
            $bookmarkName = $bookmark->name;
        }

        return response()->json([
            'fragment_id' => (int)$fragmentId,
            'action' => $action,
            'is_bookmarked' => $action === 'added',
            'bookmark_name' => $bookmarkName
        ]);
    }
}