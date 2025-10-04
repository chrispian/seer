<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;

class HelpCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $section = $command->arguments['identifier'] ?? '';

        // Define all sections
        $sections = [
            'recall' => $this->getRecallSection(),
            'bookmark' => $this->getBookmarkSection(),
            'fragment' => $this->getFragmentSection(),
            'todo' => $this->getTodoSection(),
            'search' => $this->getSearchSection(),
            'join' => $this->getJoinSection(),
            'session' => $this->getSessionSection(),
            'system' => $this->getSystemSection(),
            'tools' => $this->getToolsSection(),
        ];

        // Handle aliases
        if ($section === 'sessions') {
            $section = 'session';
        }

        // If specific section requested, return only that section
        if (! empty($section) && isset($sections[$section])) {
            $message = "# {$this->getSectionTitle($section)}\n\n".$sections[$section];
        } else {
            // Return all sections
            $message = "# Commands Cheat Sheet\n\nHere are the commands you can use in the chat:\n\n";
            foreach ($sections as $key => $content) {
                $message .= "## {$this->getSectionTitle($key)}\n{$content}\n\n";
            }
            $message .= "---\nAll fragments are stored and processed. Bookmarks and sessions give you quick access to curated memories and grouped ideas.";
        }

        return new CommandResponse(
            type: 'help',
            shouldOpenPanel: true,
            panelData: [
                'message' => $message,
            ],
        );
    }

    private function getSectionTitle(string $section): string
    {
        return match ($section) {
            'recall' => 'Recall Commands',
            'bookmark' => 'Bookmark Commands',
            'fragment' => 'Fragment Commands',
            'todo' => 'Todo Management',
            'search' => 'Search Commands',
            'join' => 'Channel Navigation',
            'session' => 'Sessions',
            'system' => 'System Management',
            'tools' => 'Chat Tools',
            default => 'Commands',
        };
    }

    private function getRecallSection(): string
    {
        return <<<'MARKDOWN'
- `/recall type:todo limit:5` – Recall recent fragments by type
- `/recall session {hint}` – Recall all fragments from a saved session (coming soon 🚀)
MARKDOWN;
    }

    private function getBookmarkSection(): string
    {
        return <<<'MARKDOWN'
- `/bookmark` – Bookmark the most recent fragment
- `/bookmark list` – List saved bookmarks
- `/bookmark show {hint}` – Replay a bookmark in the chat
- `/bookmark forget {hint}` – Delete a saved bookmark
MARKDOWN;
    }

    private function getFragmentSection(): string
    {
        return <<<'MARKDOWN'
- `/frag This is something worth remembering` – Log a fragment
- `/todo Fix the login bug #urgent` – Create a new todo fragment with tags
MARKDOWN;
    }

    private function getTodoSection(): string
    {
        return <<<'MARKDOWN'
- `/todo` – List 25 open todos (newest first)
- `/todo list` – List 25 open todos (explicit)
- `/todo list status:completed` – List completed todos
- `/todo list #urgent limit:5` – List 5 urgent todos
- `/todo list search:client` – Search todos for "client"
- `/todo complete:1` – Mark first todo complete
- `/todo complete:"login bug"` – Mark matching todo complete
MARKDOWN;
    }

    private function getSearchSection(): string
    {
        return <<<'MARKDOWN'
- `/search your query here` – Search all fragments with hybrid search
- `/s your query here` – Shorthand for search
MARKDOWN;
    }

    private function getJoinSection(): string
    {
        return <<<'MARKDOWN'
- `/join #c5` – Join channel #c5 by short code
- `/join #custom` – Join channel by custom name  
- `/join #` – Show all available channels
- `/join bug` – Search for channels containing "bug"
- `/j #c1` – Shorthand for join (alias)

**Examples:**
- `/join #c1` – Switch to channel #c1 (by short code)
- `/join #ProjectAlpha` – Switch to channel named "ProjectAlpha"
- `/join project` – Find channels about "project"
- `/join #` – Browse all active channels

**Related Commands:**
- `/channels` – List all available channels
- `/name Custom Name` – Rename current channel
MARKDOWN;
    }

    private function getSessionSection(): string
    {
        return <<<'MARKDOWN'
- `/session vault:work type:meeting Project Sync #weekly` – Start a scoped session for all new fragments
- `/session show` – Display current active session details
- `/session end` – End the current session and return to normal logging

Sessions automatically attach vault, type, tags, and a session key to all fragments you create while active.

Example:
`/session vault:writing type:seeds Ideas for Short Stories #shorts`

✅ All fragments during this session will be stored with:
- Vault: `writing`
- Type: `seeds`
- Tags: `shorts`
- Identifier: `Ideas for Short Stories`
- Session grouping key for easy recall later.
MARKDOWN;
    }

    private function getSystemSection(): string
    {
        return <<<'MARKDOWN'
- `/inbox` – Open Inbox Management Interface for fragment review
- `/types` – Open Type System Management to view type packs and validation
- `/scheduler` – Open Scheduler Management for automation and scheduled commands

**System Aliases:**
- `/pending`, `/review` – Same as `/inbox`
- `/type-system`, `/typepacks` – Same as `/types`
- `/schedules`, `/cron`, `/automation` – Same as `/scheduler`
MARKDOWN;
    }

    private function getToolsSection(): string
    {
        return <<<'MARKDOWN'
- `/clear` – Clear the current chat view
- `/help` – Show this help message
MARKDOWN;
    }
}
