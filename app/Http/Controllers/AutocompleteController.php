<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Fragment;
use App\Services\CommandRegistry;
use Illuminate\Http\Request;

class AutocompleteController extends Controller
{
    public function commands(Request $request)
    {
        $query = $request->get('q', '');

        // Get PHP-based commands only (YAML commands should be migrated to PHP)
        $allCommands = CommandRegistry::all();

        // Filter by query if provided
        if (! empty($query)) {
            $allCommands = array_filter($allCommands, function ($command) use ($query) {
                return str_contains(strtolower($command), strtolower($query));
            });
        }

        $results = array_map(function ($command) {
            return [
                'type' => 'command',
                'value' => $command,
                'display' => "/{$command}",
                'description' => $this->getCommandDescription($command),
            ];
        }, $allCommands);

        return response()->json(['results' => array_values($results)]);
    }

    public function contacts(Request $request)
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 50);

        $contacts = Contact::query()
            ->with('fragment:id,message')
            ->when($query, function ($q) use ($query) {
                $q->search($query);
            })
            ->limit($limit)
            ->get();

        $results = $contacts->map(function ($contact) {
            $displayName = $contact->full_name ?: 'Unknown';
            $primaryEmail = is_array($contact->emails) && ! empty($contact->emails) ? $contact->emails[0] : null;

            return [
                'type' => 'contact',
                'value' => $displayName,
                'display' => "@{$displayName}",
                'description' => $primaryEmail ? "({$primaryEmail})" : null,
                'organization' => $contact->organization,
                'fragment_id' => $contact->fragment_id,
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function fragments(Request $request)
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 50);

        $fragments = Fragment::query()
            ->with('type')
            ->when($query, function ($q) use ($query) {
                $q->where('message', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        $results = $fragments->map(function ($fragment) {
            // Use first 80 chars of message as title
            $title = mb_substr($fragment->message, 0, 80);
            if (mb_strlen($fragment->message) > 80) {
                $title .= '...';
            }

            // Use first 150 chars as preview
            $preview = mb_substr($fragment->message, 0, 150);
            if (mb_strlen($fragment->message) > 150) {
                $preview .= '...';
            }

            return [
                'type' => 'fragment',
                'value' => $title,
                'display' => "[[{$title}]]",
                'description' => $preview !== $title ? $preview : null,
                'fragment_type' => $fragment->type instanceof \App\Models\Type ? $fragment->type->value : $fragment->type,
                'fragment_id' => $fragment->id,
                'created_at' => $fragment->created_at->format('M j, Y'),
            ];
        });

        return response()->json(['results' => $results]);
    }

    private function getCommandDescription(string $command): string
    {
        $descriptions = [
            // Old PHP commands
            'session' => 'Start or manage chat sessions',
            'recall' => 'Recall saved fragments and todos',
            'bookmark' => 'Save and manage bookmarks',
            'help' => 'Show available commands',
            'clear' => 'Clear chat history',
            'frag' => 'Create fragment from text',

            // YAML commands
            'accept' => 'Accept and process incoming items',
            'agent-profiles' => 'List and manage agent profiles',
            'backlog-list' => 'View and manage backlog items',
            'channels' => 'Manage communication channels',
            'frag-simple' => 'Create simple fragments',
            'inbox' => 'View and manage inbox items',
            'join' => 'Join channels and conversations',
            'link' => 'Save and manage links',
            'name' => 'Set or change names',
            'news-digest' => 'Get news and updates',
            'note' => 'Create and manage notes',
            'remind' => 'Set and manage reminders',
            'routing' => 'Configure routing rules',
            'schedule-create' => 'Create scheduled tasks',
            'schedule-delete' => 'Delete scheduled tasks',
            'schedule-detail' => 'View schedule details',
            'schedule-list' => 'List scheduled tasks',
            'schedule-pause' => 'Pause scheduled tasks',
            'schedule-resume' => 'Resume scheduled tasks',
            'scheduler-ui' => 'Access scheduler interface',
            'search' => 'Search fragments and content',
            'session' => 'Manage chat sessions',
            'setup' => 'Initial setup and configuration',
            'sprint-detail' => 'View sprint details',
            'sprints' => 'List and manage sprints',
            'task-assign' => 'Assign tasks to team members',
            'task-create' => 'Create new tasks',
            'task-detail' => 'View task details',
            'tasks' => 'List and manage tasks',
            'todo' => 'Create and manage todos',
            'types-ui' => 'Manage content types',
        ];

        return $descriptions[$command] ?? 'Execute command';
    }
}
