<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Fragment;

class InboxCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $action = $command->arguments['identifier'] ?? 'pending';

        return match ($action) {
            'pending' => $this->handlePending($command),
            'bookmarked' => $this->handleBookmarked($command),
            'todos' => $this->handleTodos($command),
            'all' => $this->handleAll($command),
            default => $this->handlePending($command)
        };
    }

    private function handlePending(CommandRequest $command): CommandResponse
    {
        $limit = (int) ($command->arguments['limit'] ?? 20);

        // Find fragments that could be considered "pending" - open todos, recent items
        $query = Fragment::query()
            ->with('type')
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('type', 'todo')
                        ->whereJsonPath('state.status', '=', 'open');
                })
                    ->orWhere('created_at', '>=', now()->subDays(7)); // Recent items
            })
            ->latest()
            ->limit($limit);

        $fragments = $query->get();

        if ($fragments->isEmpty()) {
            return new CommandResponse(
                type: 'inbox',
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'pending',
                    'message' => '📥 Inbox is empty! No pending items found.',
                    'fragments' => [],
                    'type' => 'pending',
                ],
            );
        }

        $fragmentData = $this->formatFragments($fragments);

        return new CommandResponse(
            type: 'inbox',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'pending',
                'message' => '📥 Found **'.count($fragmentData).'** pending item'.((count($fragmentData) !== 1) ? 's' : '').' in your inbox',
                'fragments' => $fragmentData,
                'type' => 'pending',
            ],
        );
    }

    private function handleBookmarked(CommandRequest $command): CommandResponse
    {
        $limit = (int) ($command->arguments['limit'] ?? 20);

        // Get bookmarked fragments through the Bookmark model
        $bookmarks = \App\Models\Bookmark::latest()->limit($limit)->get();
        $fragmentIds = $bookmarks->pluck('fragment_ids')->flatten()->unique()->take($limit)->all();

        $fragments = Fragment::query()
            ->with('type')
            ->whereIn('id', $fragmentIds)
            ->latest()
            ->get();

        if ($fragments->isEmpty()) {
            return new CommandResponse(
                type: 'inbox',
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'bookmarked',
                    'message' => '🔖 No bookmarked items found.',
                    'fragments' => [],
                    'type' => 'bookmarked',
                ],
            );
        }

        $fragmentData = $this->formatFragments($fragments);

        return new CommandResponse(
            type: 'inbox',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'bookmarked',
                'message' => '🔖 Found **'.count($fragmentData).'** bookmarked item'.((count($fragmentData) !== 1) ? 's' : ''),
                'fragments' => $fragmentData,
                'type' => 'bookmarked',
            ],
        );
    }

    private function handleTodos(CommandRequest $command): CommandResponse
    {
        $status = $command->arguments['status'] ?? 'open';
        $limit = (int) ($command->arguments['limit'] ?? 20);

        $query = Fragment::todosByStatus($status)
            ->with('type')
            ->latest()
            ->limit($limit);

        $fragments = $query->get();

        if ($fragments->isEmpty()) {
            $statusText = $status === 'open' ? 'open' : $status;

            return new CommandResponse(
                type: 'inbox',
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'todos',
                    'message' => "📝 No {$statusText} todos found.",
                    'fragments' => [],
                    'type' => 'todos',
                    'status' => $status,
                ],
            );
        }

        $fragmentData = $this->formatFragments($fragments);
        $statusText = $status === 'open' ? 'open' : $status;

        return new CommandResponse(
            type: 'inbox',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'todos',
                'message' => '📝 Found **'.count($fragmentData)."** {$statusText} todo".((count($fragmentData) !== 1) ? 's' : ''),
                'fragments' => $fragmentData,
                'type' => 'todos',
                'status' => $status,
            ],
        );
    }

    private function handleAll(CommandRequest $command): CommandResponse
    {
        $limit = (int) ($command->arguments['limit'] ?? 30);

        // Get all "actionable" items - open todos, and recent fragments
        $query = Fragment::query()
            ->with('type')
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('type', 'todo')
                        ->whereJsonPath('state.status', '=', 'open');
                })
                    ->orWhere('created_at', '>=', now()->subDays(7)); // Recent items
            })
            ->latest()
            ->limit($limit);

        $fragments = $query->get();

        if ($fragments->isEmpty()) {
            return new CommandResponse(
                type: 'inbox',
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'all',
                    'message' => '📥 Inbox is empty! No actionable items found.',
                    'fragments' => [],
                    'type' => 'all',
                ],
            );
        }

        $fragmentData = $this->formatFragments($fragments);

        return new CommandResponse(
            type: 'inbox',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'all',
                'message' => '📥 Found **'.count($fragmentData).'** actionable item'.((count($fragmentData) !== 1) ? 's' : '').' in your inbox',
                'fragments' => $fragmentData,
                'type' => 'all',
            ],
        );
    }

    private function formatFragments($fragments): array
    {
        return $fragments->map(function (Fragment $fragment) {
            return [
                'id' => $fragment->id,
                'message' => $fragment->message,
                'title' => $fragment->title,
                'created_at' => $fragment->created_at,
                'updated_at' => $fragment->updated_at,
                'type' => [
                    'name' => $fragment->type?->label ?? ucfirst($fragment->type?->value ?? 'fragment'),
                    'value' => $fragment->type?->value ?? 'fragment',
                ],
                'tags' => $fragment->tags ?? [],
                'state' => $fragment->state ?? [],
                'snippet' => $this->createSnippet($fragment->message),
            ];
        })->all();
    }

    private function createSnippet(?string $message): string
    {
        if (! $message) {
            return '';
        }

        $cleaned = strip_tags($message);

        return strlen($cleaned) > 150 ? substr($cleaned, 0, 150).'...' : $cleaned;
    }
}
