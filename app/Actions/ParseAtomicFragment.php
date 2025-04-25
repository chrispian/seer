<?php

namespace App\Actions;

use App\Models\Fragment;

class ParseAtomicFragment
{
    public function __invoke(Fragment $fragment): Fragment
    {
        // Match `type` as first word
        preg_match('/^(\w+)\s+(.*)$/s', trim($fragment->message), $matches);
        $type = strtolower(trim($matches[1] ?? 'note'));
        $body = trim($matches[2] ?? '');

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
