<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use Illuminate\Support\Facades\File;

class HelpCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $section = $command->arguments['identifier'] ?? '';

        // Get tool-provided help content
        $toolHelp = $this->discoverToolHelp();
        
        // Define core sections
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
            'orchestration' => $this->getOrchestrationSection(),
        ];

        // Merge tool-provided help
        $allSections = array_merge($sections, $toolHelp);

        // Handle aliases
        if ($section === 'sessions') {
            $section = 'session';
        }

        // If specific section requested, return only that section
        if (! empty($section)) {
            // First check if there's full tool documentation available
            $toolDoc = $this->getToolDocumentation($section);
            if ($toolDoc) {
                $message = $toolDoc;
            } elseif (isset($allSections[$section])) {
                $message = "# {$this->getSectionTitle($section)}\n\n".$allSections[$section];
            } else {
                $message = "# Command Not Found\n\nNo help available for `/{$section}`. Use `/help` to see all available commands.";
            }
        } else {
            // Return all sections
            $message = "# Commands Cheat Sheet\n\nHere are the commands you can use in the chat:\n\n";
            
            // Core sections first
            foreach ($sections as $key => $content) {
                $message .= "## {$this->getSectionTitle($key)}\n{$content}\n\n";
            }
            
            // Tool-provided sections
            if (!empty($toolHelp)) {
                $message .= "## Tool-Provided Commands\n\n";
                foreach ($toolHelp as $key => $content) {
                    $message .= "### {$this->getSectionTitle($key)}\n{$content}\n\n";
                }
            }
            
            $message .= "---\n**Navigation:** Use `/help <command>` for detailed help on specific commands.\n";
            $message .= "All fragments are stored and processed. Bookmarks and sessions give you quick access to curated memories and grouped ideas.";
        }

        return new CommandResponse(
            type: 'help',
            shouldOpenPanel: true,
            panelData: [
                'message' => $message,
            ],
        );
    }

    private function discoverToolHelp(): array
    {
        $helpContent = [];
        $commandsPath = base_path('fragments/commands');
        
        if (!File::exists($commandsPath)) {
            return $helpContent;
        }
        
        $commandDirs = File::directories($commandsPath);
        
        foreach ($commandDirs as $dir) {
            $commandName = basename($dir);
            $readmePath = $dir . '/README.md';
            
            if (File::exists($readmePath)) {
                $content = File::get($readmePath);
                
                // Extract a brief summary from the README for the main help
                $summary = $this->extractSummary($content, $commandName);
                if ($summary) {
                    $helpContent[$commandName] = $summary;
                }
            }
        }
        
        return $helpContent;
    }

    private function extractSummary(string $content, string $commandName): string
    {
        // Try to extract a concise summary for the main help view
        $lines = explode("\n", $content);
        
        // Look for usage examples or overview section
        $summary = '';
        $inUsageSection = false;
        $inOverviewSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Check for overview section
            if (preg_match('/^##?\s*(overview|description|about)/i', $line)) {
                $inOverviewSection = true;
                continue;
            }
            
            // Check for usage section
            if (preg_match('/^##?\s*(usage|examples?|commands?)/i', $line)) {
                $inUsageSection = true;
                continue;
            }
            
            // Stop at next section
            if (preg_match('/^##?\s+/', $line) && ($inUsageSection || $inOverviewSection)) {
                break;
            }
            
            // Collect content from overview or usage
            if (($inOverviewSection || $inUsageSection) && !empty($line) && !preg_match('/^#{1,6}\s/', $line)) {
                $summary .= $line . "\n";
                
                // Limit summary length
                if (strlen($summary) > 300) {
                    $summary = substr($summary, 0, 300) . "...\n\n*Use `/help {$commandName}` for full documentation.*";
                    break;
                }
            }
        }
        
        // Fallback: generate basic summary from command name
        if (empty($summary)) {
            $summary = "- `/{$commandName}` – Advanced {$commandName} command functionality\n\n*Use `/help {$commandName}` for full documentation.*";
        } else {
            $summary .= "\n\n*Use `/help {$commandName}` for full documentation.*";
        }
        
        return $summary;
    }

    private function getToolDocumentation(string $commandName): ?string
    {
        $commandPath = base_path("fragments/commands/{$commandName}");
        $readmePath = $commandPath . '/README.md';
        
        if (!File::exists($readmePath)) {
            return null;
        }
        
        $content = File::get($readmePath);
        
        // Return the full README content as markdown
        return $content;
    }

    private function getOrchestrationSection(): string
    {
        return <<<'MARKDOWN'
- `/sprints` – List all sprints with progress summaries
- `/tasks` – List work items and tasks with filtering options
- `/agents` – List agent profiles and capabilities
- `/ailogs` – View AI interaction logs from various sources
- `/task-create "Title" --priority=high --estimate="2 days"` – Create new tasks
- `/sprint-detail <id>` – View detailed sprint information
- `/task-detail <id>` – View detailed task information

**Examples:**
- `/tasks --status=todo --priority=high` – List high-priority todos
- `/sprints --limit=5` – Show recent 5 sprints
- `/agents --status=active` – List active agents
- `/ailogs --source=claude --limit=20` – View recent Claude logs
MARKDOWN;
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
            'orchestration' => 'Orchestration & Task Management',
            // Dynamic titles for tool-provided commands
            default => ucwords(str_replace(['-', '_'], ' ', $section)) . ' Command',
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
- `/todos-ui` or `/todos` – Open todo management interface
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
- `/todos` – Open Todo Management Interface for task organization

**System Aliases:**
- `/pending`, `/review` – Same as `/inbox`
- `/type-system`, `/typepacks` – Same as `/types`
- `/schedules`, `/cron`, `/automation` – Same as `/scheduler`
- `/todo-manager`, `/todo-list`, `/todos-ui` – Same as `/todos`
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
