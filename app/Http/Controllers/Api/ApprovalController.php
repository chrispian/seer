<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Services\Security\ApprovalManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        private ApprovalManager $approvalManager
    ) {}

    public function approve(Request $request, string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);

        if (!$approvalRequest->isPending()) {
            return response()->json([
                'error' => 'Request already processed',
                'status' => $approvalRequest->status,
            ], 400);
        }

        try {
            $this->approvalManager->approveRequest(
                $approvalRequest,
                auth()->id(),
                'button_click'
            );

            return response()->json([
                'success' => true,
                'approval' => $this->approvalManager->formatForChat($approvalRequest->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);

        if (!$approvalRequest->isPending()) {
            return response()->json([
                'error' => 'Request already processed',
                'status' => $approvalRequest->status,
            ], 400);
        }

        $reason = $request->input('reason');

        try {
            $this->approvalManager->rejectRequest(
                $approvalRequest,
                auth()->id(),
                'button_click',
                $reason
            );

            return response()->json([
                'success' => true,
                'approval' => $this->approvalManager->formatForChat($approvalRequest->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::with(['fragment', 'approvedBy'])->findOrFail($id);

        return response()->json([
            'approval' => $this->approvalManager->formatForChat($approvalRequest),
        ]);
    }

    public function pending(): JsonResponse
    {
        $pending = ApprovalRequest::pending()
            ->where('approved_by_user_id', auth()->id())
            ->orWhereNull('approved_by_user_id')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'approvals' => $pending->map(fn($a) => $this->approvalManager->formatForChat($a)),
        ]);
    }
}

