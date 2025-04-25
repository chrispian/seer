<?php

namespace App\Actions;

class ParseFragmentInput
{
    public function __invoke(string $input): array
    {
        // Match `type` as first word
        preg_match('/^(\w+)\s+(.*)$/s', trim($input), $matches);
        $type = strtolower(trim($matches[1] ?? 'note'));
        $body = trim($matches[2] ?? '');

        // Extract tags and clean message
        preg_match_all('/#(\w+)/', $body, $tagMatches);
        $tags = $tagMatches[1] ?? [];

        $cleanMessage = preg_replace('/#\w+/', '', $body);

        return [
            'type' => $type,
            'message' => trim($cleanMessage),
            'tags' => $tags,
        ];
    }
}
