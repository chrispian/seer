<?php

namespace App\Http\Controllers;

use App\Services\Inbox\InboxAiAssist;
use App\Services\Inbox\InboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function __construct(
        protected InboxService $inboxService,
        protected InboxAiAssist $aiAssist
    ) {}

    /**
     * List inbox fragments with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => 'sometimes|array',
            'type.*' => 'string',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'category' => 'sometimes|string',
            'vault' => 'sometimes|string',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date',
            'inbox_reason' => 'sometimes|string',
        ]);

        $perPage = $request->input('per_page', 25);
        $fragments = $this->inboxService->getInboxFragments($filters, $perPage);

        return response()->json([
            'data' => $fragments->items(),
            'meta' => [
                'current_page' => $fragments->currentPage(),
                'last_page' => $fragments->lastPage(),
                'per_page' => $fragments->perPage(),
                'total' => $fragments->total(),
            ],
        ]);
    }

    /**
     * Get inbox statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->inboxService->getInboxStats();

        return response()->json($stats);
    }

    /**
     * Get AI assist data for a fragment
     */
    public function aiAssist(int $fragmentId): JsonResponse
    {
        $fragment = \App\Models\Fragment::findOrFail($fragmentId);
        $aiData = $this->aiAssist->getAiAssistData($fragment);

        return response()->json($aiData);
    }

    /**
     * Accept a single fragment
     */
    public function accept(Request $request, int $fragmentId): JsonResponse
    {
        $validated = $request->validate([
            'edits' => 'sometimes|array',
            'edits.title' => 'sometimes|string|max:255',
            'edits.type' => 'sometimes|string|max:50',
            'edits.tags' => 'sometimes|array',
            'edits.category' => 'sometimes|string|max:100',
            'edits.vault' => 'sometimes|string|max:100',
            'edits.edited_message' => 'sometimes|string|nullable',
        ]);

        $userId = auth()->id() ?? 1; // Default to user 1 for testing
        $edits = $validated['edits'] ?? [];

        try {
            $success = $this->inboxService->acceptFragment($fragmentId, $userId, $edits);

            return response()->json([
                'success' => $success,
                'message' => 'Fragment accepted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Accept multiple fragments
     */
    public function acceptMultiple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fragment_ids' => 'required|array',
            'fragment_ids.*' => 'integer',
            'edits' => 'sometimes|array',
            'edits.type' => 'sometimes|string|max:50',
            'edits.tags' => 'sometimes|array',
            'edits.category' => 'sometimes|string|max:100',
            'edits.vault' => 'sometimes|string|max:100',
        ]);

        $userId = auth()->id() ?? 1; // Default to user 1 for testing
        $fragmentIds = $validated['fragment_ids'];
        $bulkEdits = $validated['edits'] ?? [];

        $results = $this->inboxService->acceptFragments($fragmentIds, $userId, $bulkEdits);

        $successCount = count(array_filter($results, fn ($r) => $r['success']));
        $totalCount = count($results);

        return response()->json([
            'results' => $results,
            'summary' => [
                'total' => $totalCount,
                'success' => $successCount,
                'failed' => $totalCount - $successCount,
            ],
            'message' => "Processed {$totalCount} fragments: {$successCount} accepted",
        ]);
    }

    /**
     * Accept all pending fragments with optional filters
     */
    public function acceptAll(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => 'sometimes|array',
            'type.*' => 'string',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'category' => 'sometimes|string',
            'vault' => 'sometimes|string',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date',
            'inbox_reason' => 'sometimes|string',
            'edits' => 'sometimes|array',
        ]);

        $userId = auth()->id() ?? 1; // Default to user 1 for testing
        $filterCriteria = array_filter($filters, fn ($key) => $key !== 'edits', ARRAY_FILTER_USE_KEY);
        $bulkEdits = $filters['edits'] ?? [];

        $results = $this->inboxService->acceptAll($userId, $filterCriteria, $bulkEdits);

        $successCount = count(array_filter($results, fn ($r) => $r['success']));
        $totalCount = count($results);

        return response()->json([
            'results' => $results,
            'summary' => [
                'total' => $totalCount,
                'success' => $successCount,
                'failed' => $totalCount - $successCount,
            ],
            'message' => "Accepted all matching fragments: {$successCount} of {$totalCount}",
        ]);
    }

    /**
     * Archive a fragment
     */
    public function archive(Request $request, int $fragmentId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'sometimes|string|max:255',
        ]);

        $userId = auth()->id() ?? 1; // Default to user 1 for testing
        $reason = $validated['reason'] ?? null;

        try {
            $success = $this->inboxService->archiveFragment($fragmentId, $userId, $reason);

            return response()->json([
                'success' => $success,
                'message' => 'Fragment archived successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Skip a fragment
     */
    public function skip(Request $request, int $fragmentId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'sometimes|string|max:255',
        ]);

        $userId = auth()->id() ?? 1; // Default to user 1 for testing
        $reason = $validated['reason'] ?? null;

        try {
            $success = $this->inboxService->skipFragment($fragmentId, $userId, $reason);

            return response()->json([
                'success' => $success,
                'message' => 'Fragment skipped successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reopen a fragment back to inbox
     */
    public function reopen(int $fragmentId): JsonResponse
    {
        try {
            $success = $this->inboxService->reopenFragment($fragmentId);

            return response()->json([
                'success' => $success,
                'message' => 'Fragment reopened successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
