<?php

namespace App\Commands;

use App\Commands\Concerns\FormatsListData;

class TodoCommand extends BaseCommand
{
    use FormatsListData;

    public function handle(): array
    {
        $fragments = $this->getFragments();
        
        // Transform fragments to TodoItem format (matching frontend expectations)
        $todos = array_map(fn($f) => $this->transformToTodoItem($f), $fragments);
        
        // Standard pattern - no panelData needed
        return $this->respond(['items' => $todos]);
    }

    private function getFragments(): array
    {
        if (! class_exists(\App\Models\Fragment::class)) {
            return [];
        }

        $limit = $this->command?->pagination_default ?? 50;

        return \App\Models\Fragment::query()
            ->where('type', 'todo')
            ->latest()
            ->limit($limit)
            ->get()
            ->all();
    }

    private function transformToTodoItem($fragment): array
    {
        $state = is_array($fragment->state) ? $fragment->state : [];
        
        // Parse tags - handle PostgreSQL array format
        $tags = $this->parseTags($fragment->tags);
        
        // Clean title - remove "Todo: " prefix if present
        $title = $fragment->title ?: ($fragment->message ? explode("\n", $fragment->message)[0] : 'Untitled Todo');
        if (str_starts_with($title, 'Todo: ')) {
            $title = substr($title, 6);
        }
        
        return [
            'id' => (string) $fragment->id,
            'fragment_id' => (string) $fragment->id,
            'title' => trim($title),
            'message' => $fragment->message ?? '',
            'status' => $state['status'] === 'complete' ? 'completed' : ($state['status'] ?? 'open'),
            'priority' => $state['priority'] ?? 'medium',
            'tags' => $tags,
            'project' => $state['project'] ?? null,
            'created_at' => $fragment->created_at?->toISOString(),
            'updated_at' => $fragment->updated_at?->toISOString(),
            'completed_at' => $state['completed_at'] ?? null,
            'due_at' => $state['due_at'] ?? null,
            'order' => $state['order'] ?? 0,
            'is_pinned' => $fragment->pinned ?? false,
        ];
    }

    private function parseTags($tags): array
    {
        if (! is_array($tags)) {
            return [];
        }

        $parsed = [];
        foreach ($tags as $tag) {
            if (! is_string($tag)) {
                continue;
            }
            
            // Handle PostgreSQL array format like '{"work","important"}'
            if (str_starts_with($tag, '{') && str_ends_with($tag, '}')) {
                $cleaned = substr($tag, 1, -1);
                $items = explode(',', $cleaned);
                foreach ($items as $item) {
                    $item = trim(str_replace('"', '', $item));
                    if ($item && $item !== 'todo') {
                        $parsed[] = $item;
                    }
                }
                continue;
            }
            
            // Handle JSON arrays
            if (str_starts_with($tag, '[')) {
                $decoded = json_decode($tag, true);
                if (is_array($decoded)) {
                    $parsed = array_merge($parsed, array_filter($decoded, fn($t) => $t !== 'todo'));
                    continue;
                }
            }
            
            // Regular tag
            if ($tag !== 'todo') {
                $parsed[] = $tag;
            }
        }
        
        return array_unique($parsed);
    }

    public static function getName(): string
    {
        return 'Todo Manager';
    }

    public static function getDescription(): string
    {
        return 'Manage todo items and task lists';
    }

    public static function getUsage(): string
    {
        return '/todo';
    }

    public static function getCategory(): string
    {
        return 'Productivity';
    }
}
