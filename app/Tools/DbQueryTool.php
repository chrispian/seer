<?php

namespace App\Tools;

use App\Contracts\ToolContract;
use App\Support\ToolRegistry;
use Illuminate\Database\Eloquent\Builder;

class DbQueryTool implements ToolContract
{
    public function __construct(protected ToolRegistry $registry) {}

    public function name(): string
    {
        return 'db.query';
    }

    public function scope(): string
    {
        return 'read/db.query';
    }

    public function inputSchema(): array
    {
        return $this->registry->loadContract('db.query')['input_schema'] ?? [];
    }

    public function outputSchema(): array
    {
        return $this->registry->loadContract('db.query')['output_schema'] ?? [];
    }

    public function run(array $payload): array
    {
        $this->registry->ensureScope($this->scope());

        $entity = $payload['entity'];
        $filters = $payload['filters'] ?? [];
        $search = $payload['search'] ?? null;
        $orderBy = $payload['order_by'] ?? [];
        $limit = $payload['limit'] ?? 50;
        $offset = $payload['offset'] ?? 0;

        $q = $this->builderFor($entity);
        if (! $q) {
            return ['items' => [], 'cursor' => null, 'explain' => ['reason' => 'unknown_entity']];
        }

        // basic filters
        foreach ($filters as $f) {
            $op = $f['op'] ?? '=';
            $field = $f['field'] ?? null;
            $value = $f['value'] ?? null;
            if (! $field) {
                continue;
            }

            switch ($op) {
                case 'in':  $q->whereIn($field, (array) $value);
                    break;
                case 'nin': $q->whereNotIn($field, (array) $value);
                    break;
                case 'lt':  $q->where($field, '<', $value);
                    break;
                case 'lte': $q->where($field, '<=', $value);
                    break;
                case 'gt':  $q->where($field, '>', $value);
                    break;
                case 'gte': $q->where($field, '>=', $value);
                    break;
                case 'like':
                case 'ilike':
                    $q->where($field, 'like', '%'.$value.'%');
                    break;
                case 'json_contains':
                    $q->whereJsonContains($field, $value);
                    break;
                case 'fulltext':
                    // Use Laravel's whereFullText for full-text search
                    // Falls back to LIKE search if full-text indexes aren't available
                    try {
                        $q->whereFullText($field, $value);
                    } catch (\Exception $e) {
                        // Fallback to LIKE search if full-text not supported
                        $q->where($field, 'like', '%'.$value.'%');
                    }
                    break;
                default:
                    $q->where($field, $op, $value);
            }
        }

        // naive search hook (adjust per-entity with FTS)
        if ($search) {
            $q->where(function ($qq) use ($search, $entity) {
                $fields = match ($entity) {
                    'work_items' => ['tags', 'state', 'metadata'],
                    default => ['tags', 'metadata']
                };
                foreach ($fields as $f) {
                    $qq->orWhere($f, 'like', '%'.$search.'%');
                }
            });
        }

        foreach ($orderBy as $o) {
            $dir = strtolower($o['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $q->orderBy($o['field'], $dir);
        }

        $items = $q->limit($limit)->offset($offset)->get();

        return [
            'items' => $items->toArray(),
            'cursor' => null,
            'explain' => ['entity' => $entity, 'count' => $items->count()],
        ];
    }

    protected function builderFor(string $entity): ?Builder
    {
        return match ($entity) {
            'work_items' => \App\Models\WorkItem::query(),
            'fragments' => \App\Models\Fragment::query(),
            'bookmarks' => \App\Models\Bookmark::query(),
            'chat_sessions' => \App\Models\ChatSession::query(),
            'vault_routing_rules' => \App\Models\VaultRoutingRule::query(),
            'saved_queries' => \App\Models\SavedQuery::query(),
            'artifacts' => \App\Models\Artifact::query(),
            'agent_notes' => \App\Models\AgentNote::query(),
            'agent_decisions' => \App\Models\AgentDecision::query(),
            'sprints' => \App\Models\Sprint::query(),
            'sprint_items' => \App\Models\SprintItem::query(),
            default => null
        };
    }
}
