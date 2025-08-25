<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class UndoFragmentDelete
{
    public function __invoke(int $fragmentId): ?Fragment
    {
        Log::debug('UndoFragmentDelete::invoke()', ['fragment_id' => $fragmentId]);

        $fragment = Fragment::withTrashed()->find($fragmentId);

        if (! $fragment) {
            Log::warning('Fragment not found for undo', ['fragment_id' => $fragmentId]);

            return null;
        }

        if (! $fragment->trashed()) {
            Log::debug('Fragment not deleted, no undo needed', ['fragment_id' => $fragmentId]);

            return $fragment;
        }

        // Check if it's within the 60-second undo window
        $deletedAt = $fragment->deleted_at;
        $undoWindow = now()->subSeconds(60);

        if ($deletedAt->lt($undoWindow)) {
            Log::warning('Undo window expired', [
                'fragment_id' => $fragmentId,
                'deleted_at' => $deletedAt->toISOString(),
                'undo_deadline' => $undoWindow->toISOString(),
            ]);

            return null;
        }

        // Restore the fragment
        $fragment->restore();

        Log::info('Fragment restored successfully', [
            'fragment_id' => $fragmentId,
            'message' => $fragment->message,
        ]);

        return $fragment;
    }
}
