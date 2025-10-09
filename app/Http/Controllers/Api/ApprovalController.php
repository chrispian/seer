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
        $approvalRequest = ApprovalRequest::with('fragment')->findOrFail($id);

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

            // Execute the approved operation
            $executionResult = $this->executeApprovedOperation($approvalRequest);

            return response()->json([
                'success' => true,
                'approval' => $this->approvalManager->formatForChat($approvalRequest->fresh()),
                'execution_result' => $executionResult,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function executeApprovedOperation(ApprovalRequest $approval): array
    {
        $details = $approval->operation_details;

        try {
            if ($approval->operation_type === 'command') {
                $executor = app(\App\Services\Security\EnhancedShellExecutor::class);
                $result = $executor->execute($details['command'], $details['context'] ?? []);
                
                return [
                    'executed' => true,
                    'success' => $result['success'],
                    'output' => $result['stdout'] ?? '',
                    'error' => $result['stderr'] ?? '',
                ];
            }

            return ['executed' => false, 'reason' => 'Operation type not yet supported'];
        } catch (\Exception $e) {
            \Log::error('Failed to execute approved operation', [
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'executed' => false,
                'error' => $e->getMessage(),
            ];
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

