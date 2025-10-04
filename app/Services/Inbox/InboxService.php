<?php

namespace App\Services\Inbox;

use App\Events\FragmentAccepted;
use App\Events\FragmentArchived;
use App\Models\Fragment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class InboxService
{
    /**
     * Get inbox fragments with filtering and pagination
     */
    public function getInboxFragments(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Fragment::query()
            ->inInbox()
            ->inboxSortDefault();

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get all inbox fragments without pagination
     */
    public function getAllInboxFragments(array $filters = []): Collection
    {
        $query = Fragment::query()
            ->inInbox()
            ->inboxSortDefault();

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Accept a single fragment
     */
    public function acceptFragment(int $fragmentId, int $userId, array $edits = []): bool
    {
        $fragment = Fragment::findOrFail($fragmentId);

        if ($fragment->inbox_status !== 'pending') {
            throw new \InvalidArgumentException("Fragment {$fragmentId} is not in pending status");
        }

        $success = $fragment->acceptInInbox($userId, $edits);

        if ($success) {
            event(new FragmentAccepted($fragment, $userId, $edits));
        }

        return $success;
    }

    /**
     * Accept multiple fragments
     */
    public function acceptFragments(array $fragmentIds, int $userId, array $bulkEdits = []): array
    {
        $results = [];
        $fragments = Fragment::whereIn('id', $fragmentIds)->inInbox()->get();

        foreach ($fragments as $fragment) {
            try {
                $success = $fragment->acceptInInbox($userId, $bulkEdits);

                if ($success) {
                    event(new FragmentAccepted($fragment, $userId, $bulkEdits));
                }

                $results[$fragment->id] = ['success' => $success];
            } catch (\Exception $e) {
                $results[$fragment->id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Accept all pending fragments with optional filters
     */
    public function acceptAll(int $userId, array $filters = [], array $bulkEdits = []): array
    {
        $query = Fragment::query()->inInbox();
        $query = $this->applyFilters($query, $filters);

        $fragmentIds = $query->pluck('id')->toArray();

        return $this->acceptFragments($fragmentIds, $userId, $bulkEdits);
    }

    /**
     * Archive a fragment
     */
    public function archiveFragment(int $fragmentId, int $userId, ?string $reason = null): bool
    {
        $fragment = Fragment::findOrFail($fragmentId);

        if ($fragment->inbox_status !== 'pending') {
            throw new \InvalidArgumentException("Fragment {$fragmentId} is not in pending status");
        }

        $success = $fragment->archiveInInbox($userId, $reason);

        if ($success) {
            event(new FragmentArchived($fragment, $userId, $reason));
        }

        return $success;
    }

    /**
     * Skip a fragment
     */
    public function skipFragment(int $fragmentId, int $userId, ?string $reason = null): bool
    {
        $fragment = Fragment::findOrFail($fragmentId);

        if ($fragment->inbox_status !== 'pending') {
            throw new \InvalidArgumentException("Fragment {$fragmentId} is not in pending status");
        }

        return $fragment->skipInInbox($userId, $reason);
    }

    /**
     * Reopen a fragment back to inbox
     */
    public function reopenFragment(int $fragmentId): bool
    {
        $fragment = Fragment::findOrFail($fragmentId);

        if ($fragment->inbox_status === 'pending') {
            throw new \InvalidArgumentException("Fragment {$fragmentId} is already pending");
        }

        return $fragment->reopenInInbox();
    }

    /**
     * Get inbox statistics
     */
    public function getInboxStats(): array
    {
        return [
            'pending' => Fragment::inInbox()->count(),
            'accepted' => Fragment::accepted()->count(),
            'archived' => Fragment::archived()->count(),
            'total' => Fragment::count(),
            'by_type' => Fragment::inInbox()
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by type
        if (! empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $query->whereIn('type', $filters['type']);
            } else {
                $query->where('type', $filters['type']);
            }
        }

        // Filter by tags
        if (! empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $query->withAnyTag($tags);
        }

        // Filter by category
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by vault
        if (! empty($filters['vault'])) {
            $query->where('vault', $filters['vault']);
        }

        // Filter by date range
        if (! empty($filters['from_date'])) {
            $query->where('inbox_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('inbox_at', '<=', $filters['to_date']);
        }

        // Filter by inbox reason
        if (! empty($filters['inbox_reason'])) {
            $query->where('inbox_reason', 'like', '%'.$filters['inbox_reason'].'%');
        }

        return $query;
    }
}
