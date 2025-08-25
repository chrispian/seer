<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class ParseAtomicFragment
{
    public function __invoke(Fragment $fragment): Fragment
    {

        Log::debug('ParseAtomicFragment::invoke()');

        // Match `type` as first word (with optional colon)
        preg_match('/^(\w+):?\s+(.*)$/s', trim($fragment->message), $matches);
        $type = strtolower(trim($matches[1] ?? 'note'));
        $body = trim($matches[2] ?? $fragment->message); // Fallback to full message if no match

        // Extract tags and clean message
        preg_match_all('/#(\w+)/', $body, $tagMatches);
        $tags = $tagMatches[1] ?? [];

        $cleanMessage = preg_replace('/#\w+/', '', $body);

        $fragment->type = $type;
        $fragment->tags = $tags;
        $fragment->message = trim($cleanMessage);

        return tap($fragment)->save();
    }
}
