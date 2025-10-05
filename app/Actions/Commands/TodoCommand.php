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

        // Handle reopen actions
        $reopen = $command->arguments['reopen'] ?? null;
        if ($reopen !== null) {
            return $this->handleReopen($reopen);
        }

        // Handle delete actions
        $delete = $command->arguments['delete'] ?? null;
        if ($delete !== null) {
            return $this->handleDelete($delete);
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
        if ($status === 'all') {
            $query = Fragment::where('type', 'todo')->with('type');
        } else {
            $query = Fragment::todosByStatus($status)->with('type');
        }

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
            } elseif (ctype_digit($complete)) {
                // ID-based completion (all digits, could be large ID)
                return $this->completeById($complete);
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

    private function completeById(string $id): CommandResponse
    {
        $todo = Fragment::where('type', 'todo')
            ->where('id', $id)
            ->whereRaw("(state::jsonb->>'status') IN ('open', 'in_progress', 'blocked') OR (state::jsonb->>'status') IS NULL")
            ->first();

        if (!$todo) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "No open todo found with ID: {$id}",
            );
        }

        $this->markComplete($todo);

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: "✅ Completed todo: ".$this->truncate($todo->message, 50),
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

    private function handleReopen($reopen): CommandResponse
    {
        try {
            if (is_numeric($reopen)) {
                // Position-based reopen
                return $this->reopenByPosition((int) $reopen);
            } elseif (ctype_digit($reopen)) {
                // ID-based reopen (all digits, could be large ID)
                return $this->reopenById($reopen);
            } else {
                // Keyword-based reopen
                return $this->reopenByKeyword($reopen);
            }
        } catch (\Exception $e) {
            Log::error('Todo reopen failed', [
                'reopen' => $reopen,
                'error' => $e->getMessage(),
            ]);

            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "Failed to reopen todo: {$e->getMessage()}",
            );
        }
    }

    private function reopenByPosition(int $position): CommandResponse
    {
        // Get completed todos in the same order as displayed
        $todos = Fragment::completedTodos()
            ->orderByRaw("(state::jsonb->>'completed_at') DESC")
            ->get();

        if ($position < 1 || $position > $todos->count()) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "Invalid position {$position}. Available completed todos: 1-{$todos->count()}",
            );
        }

        $todo = $todos->skip($position - 1)->first();
        $this->markOpen($todo);

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: "🔄 Reopened todo #{$position}: ".$this->truncate($todo->message, 50),
        );
    }

    private function reopenById(string $id): CommandResponse
    {
        $todo = Fragment::where('type', 'todo')
            ->where('id', $id)
            ->whereRaw("(state::jsonb->>'status') = 'complete'")
            ->first();

        if (!$todo) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "No completed todo found with ID: {$id}",
            );
        }

        $this->markOpen($todo);

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: "🔄 Reopened todo: ".$this->truncate($todo->message, 50),
        );
    }

    private function reopenByKeyword(string $keyword): CommandResponse
    {
        // Search for matching completed todo
        $todo = Fragment::completedTodos()
            ->where(function ($q) use ($keyword) {
                $q->where('message', 'LIKE', "%{$keyword}%")
                    ->orWhere('title', 'LIKE', "%{$keyword}%");
            })
            ->first();

        if (! $todo) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "No completed todo found matching: {$keyword}",
            );
        }

        $this->markOpen($todo);

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: '🔄 Reopened todo: '.$this->truncate($todo->message, 50),
        );
    }

    private function handleDelete($delete): CommandResponse
    {
        try {
            if (is_numeric($delete)) {
                // Position-based delete
                return $this->deleteByPosition((int) $delete);
            } elseif (ctype_digit($delete)) {
                // ID-based delete (all digits, could be large ID)
                return $this->deleteById($delete);
            } else {
                // Keyword-based delete
                return $this->deleteByKeyword($delete);
            }
        } catch (\Exception $e) {
            Log::error('Todo delete failed', [
                'delete' => $delete,
                'error' => $e->getMessage(),
            ]);

            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "Failed to delete todo: {$e->getMessage()}",
            );
        }
    }

    private function deleteByPosition(int $position): CommandResponse
    {
        // Get all todos (open and completed) in display order
        $openTodos = Fragment::openTodos()->latest()->get();
        $completedTodos = Fragment::completedTodos()
            ->orderByRaw("(state::jsonb->>'completed_at') DESC")
            ->get();
        
        $allTodos = $openTodos->concat($completedTodos);

        if ($position < 1 || $position > $allTodos->count()) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "Invalid position {$position}. Available todos: 1-{$allTodos->count()}",
            );
        }

        $todo = $allTodos->skip($position - 1)->first();
        $todoMessage = $this->truncate($todo->message, 50);
        
        $todo->delete();

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: "🗑️ Deleted todo #{$position}: {$todoMessage}",
        );
    }

    private function deleteById(string $id): CommandResponse
    {
        $todo = Fragment::where('type', 'todo')
            ->where('id', $id)
            ->first();

        if (!$todo) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "No todo found with ID: {$id}",
            );
        }

        $todoMessage = $this->truncate($todo->message, 50);
        $todo->delete();

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: "🗑️ Deleted todo: {$todoMessage}",
        );
    }

    private function deleteByKeyword(string $keyword): CommandResponse
    {
        // Search for matching todo (open or completed)
        $todo = Fragment::where('type', 'todo')
            ->where(function ($q) use ($keyword) {
                $q->where('message', 'LIKE', "%{$keyword}%")
                    ->orWhere('title', 'LIKE', "%{$keyword}%");
            })
            ->first();

        if (! $todo) {
            return new CommandResponse(
                type: 'system',
                shouldShowErrorToast: true,
                message: "No todo found matching: {$keyword}",
            );
        }

        $todoMessage = $this->truncate($todo->message, 50);
        $todo->delete();

        return new CommandResponse(
            type: 'system',
            shouldShowSuccessToast: true,
            message: "🗑️ Deleted todo: {$todoMessage}",
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

    private function markOpen(Fragment $todo): void
    {
        $state = $todo->state ?? [];
        $state['status'] = 'open';
        unset($state['completed_at']); // Remove completion timestamp

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
