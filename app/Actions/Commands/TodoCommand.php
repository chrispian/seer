<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class TodoCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        // Check if this is a subcommand (list, complete) or todo creation
        $identifier = $command->arguments['identifier'] ?? '';

        // Handle subcommands
        if ($identifier === 'list') {
            return $this->handleList($command);
        }

        // Handle completion actions
        $complete = $command->arguments['complete'] ?? null;
        if ($complete !== null) {
            return $this->handleCompletion($complete);
        }

        // If no subcommand and no structured arguments, treat as todo creation
        if (empty($command->arguments['status']) &&
            empty($command->arguments['search']) &&
            empty($command->arguments['limit']) &&
            ! empty($identifier)) {
            return $this->handleCreate($command);
        }

        // Default to list behavior for backwards compatibility
        return $this->handleList($command);
    }

    private function handleCreate(CommandRequest $command): CommandResponse
    {
        $message = $command->arguments['identifier'] ?? '';
        $tags = $command->arguments['tags'] ?? [];

        if (empty($message)) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: 'Please provide a message for the todo. Example: `/todo Fix the login bug #urgent`',
            );
        }

        // Get the todo type ID
        $todoType = \App\Models\Type::where('value', 'todo')->first();
        if (! $todoType) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: 'Todo type not found in database. Please contact administrator.',
            );
        }

        // Create the fragment
        $fragment = Fragment::create([
            'type' => 'todo',
            'type_id' => $todoType->id,
            'message' => $message,
            'tags' => $tags,
            'state' => ['status' => 'open'],
            'created_at' => now(),
        ]);

        $tagText = ! empty($tags) ? ' with tags: #'.implode(', #', $tags) : '';

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: '✅ Created todo: '.$this->truncate($message, 50).$tagText,
            toastData: [
                'title' => 'Todo Added',
                'message' => $this->truncate($message, 50).$tagText,
                'fragmentType' => 'todo',
                'fragmentId' => $fragment->id,
            ],
        );
    }

    private function handleList(CommandRequest $command): CommandResponse
    {
        // Parse arguments for list functionality
        $status = $command->arguments['status'] ?? 'open';
        $searchTerm = $command->arguments['search'] ?? null;
        $tags = $command->arguments['tags'] ?? [];
        $limit = (int) ($command->arguments['limit'] ?? 25);

        // Build query for todos using scopes
        $query = Fragment::todosByStatus($status)
            ->with('type');

        // Apply tag filters
        if (! empty($tags)) {
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Apply search filter
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('message', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('title', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply ordering and limit
        if ($status === 'completed') {
            // For completed todos, order by completion date
            $query->orderByRaw("(state::jsonb->>'completed_at') DESC");
        } else {
            // For open todos, order by creation date (newest first)
            $query->latest();
        }

        $results = $query->limit($limit)->get();

        if ($results->isEmpty()) {
            return $this->emptyResponse($status, $searchTerm, $tags);
        }

        return new CommandResponse(
            type: 'recall',
            shouldOpenPanel: true,
            panelData: [
                'type' => 'todo',
                'status' => $status,
                'search' => $searchTerm,
                'tags' => $tags,
                'fragments' => $results->toArray(),
                'message' => $this->buildResultMessage($status, $results->count(), $searchTerm, $tags),
            ],
        );
    }

    private function handleCompletion($complete): CommandResponse
    {
        try {
            if (is_numeric($complete)) {
                // Position-based completion
                return $this->completeByPosition((int) $complete);
            } else {
                // Keyword-based completion
                return $this->completeByKeyword($complete);
            }
        } catch (\Exception $e) {
            Log::error('Todo completion failed', [
                'complete' => $complete,
                'error' => $e->getMessage(),
            ]);

            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "Failed to complete todo: {$e->getMessage()}",
            );
        }
    }

    private function completeByPosition(int $position): CommandResponse
    {
        // Get open todos in the same order as displayed
        $todos = Fragment::openTodos()
            ->latest()
            ->get();

        if ($position < 1 || $position > $todos->count()) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "Invalid position {$position}. Available todos: 1-{$todos->count()}",
            );
        }

        $todo = $todos->skip($position - 1)->first();
        $this->markComplete($todo);

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: "✅ Completed todo #{$position}: ".$this->truncate($todo->message, 50),
        );
    }

    private function completeByKeyword(string $keyword): CommandResponse
    {
        // Search for matching open todo
        $todo = Fragment::openTodos()
            ->where(function ($q) use ($keyword) {
                $q->where('message', 'LIKE', "%{$keyword}%")
                    ->orWhere('title', 'LIKE', "%{$keyword}%");
            })
            ->first();

        if (! $todo) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "No open todo found matching: {$keyword}",
            );
        }

        $this->markComplete($todo);

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: '✅ Completed todo: '.$this->truncate($todo->message, 50),
        );
    }

    private function markComplete(Fragment $todo): void
    {
        $state = $todo->state ?? [];
        $state['status'] = 'complete';
        $state['completed_at'] = now()->toISOString();

        $todo->state = $state;
        $todo->save();
    }

    private function emptyResponse(string $status, ?string $searchTerm, array $tags): CommandResponse
    {
        $filters = [];
        if ($status !== 'open') {
            $filters[] = "status:{$status}";
        }
        if ($searchTerm) {
            $filters[] = "search:{$searchTerm}";
        }
        if (! empty($tags)) {
            $filters[] = '#'.implode(' #', $tags);
        }

        $filterText = ! empty($filters) ? ' with filters: '.implode(', ', $filters) : '';

        return new CommandResponse(
            type: 'recall',
            shouldOpenPanel: true,
            panelData: [
                'type' => 'todo',
                'status' => $status,
                'search' => $searchTerm,
                'tags' => $tags,
                'fragments' => [],
                'message' => "📝 No todos found{$filterText}",
            ],
        );
    }

    private function buildResultMessage(string $status, int $count, ?string $searchTerm, array $tags): string
    {
        $statusText = $status === 'completed' ? 'completed' : 'open';
        $base = "📝 Found **{$count}** {$statusText} todo".($count !== 1 ? 's' : '');

        $filters = [];
        if ($searchTerm) {
            $filters[] = "matching '{$searchTerm}'";
        }
        if (! empty($tags)) {
            $filters[] = 'tagged #'.implode(', #', $tags);
        }

        return $base.(! empty($filters) ? ' '.implode(' and ', $filters) : '');
    }

    private function truncate(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length).'...' : $text;
    }
}
