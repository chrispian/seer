<?php

namespace App\Actions;

use App\DTOs\CommandRequest;
use Illuminate\Support\Facades\Log;

class ParseSlashCommand
{
    public function __invoke(string $input): CommandRequest
    {
        logger('PARSE_SLASH_COMMAND: input', ['input' => $input]);
        $input = trim($input);
        Log::debug("Parsing Slash command input: {$input}");

        // Expect input to start with '/'
        if (! str_starts_with($input, '/')) {
            throw new \InvalidArgumentException('Invalid slash command syntax.');
        }

        // Remove leading slash
        $input = ltrim($input, '/');

        // First word is command
        $parts = preg_split('/\s+/', $input, 2);
        $command = strtolower($parts[0] ?? '');
        $argumentsString = $parts[1] ?? '';

        // Parse arguments into key/value hints
        $arguments = [];

        // Match vault:xxx, type:xxx, context:xxx, etc
        if (preg_match_all('/(\w+):([^\s]+)/', $argumentsString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $arguments[$match[1]] = $match[2];
            }
        }

        // Handle special cases for join/channel commands: if argument starts with # treat it as channel identifier
        if (in_array($command, ['join', 'j']) && preg_match('/^#(\w+)/', trim($argumentsString))) {
            $arguments['identifier'] = trim($argumentsString);
        } else {
            // Match #tags (but not channel identifiers for join commands)
            if (preg_match_all('/#(\w+)/', $argumentsString, $tagMatches)) {
                // For join commands, don't treat # patterns as tags
                if (! in_array($command, ['join', 'j'])) {
                    $arguments['tags'] = $tagMatches[1];
                }
            }

            // Remaining text could be identifier or message (after removing structured args and tags)
            $remaining = trim(preg_replace('/(\w+):([^\s]+)/', '', $argumentsString));

            // For non-join commands, also remove hashtag patterns from remaining text
            if (! in_array($command, ['join', 'j'])) {
                $remaining = trim(preg_replace('/#\w+/', '', $remaining));
            }

            if (! empty($remaining)) {
                $arguments['identifier'] = $remaining;
            }
        }

        return new CommandRequest($command, $arguments, $input);
    }
}
