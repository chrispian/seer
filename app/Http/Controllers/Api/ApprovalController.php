<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Services\Security\ApprovalManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API endpoints for approval request management.
 *
 * Provides REST API for approving, rejecting, and querying approval requests
 * for high-risk security operations. Integrates with ApprovalManager for
 * business logic and executes approved operations.
 *
 * Endpoints:
 * - POST /api/approvals/{id}/approve - Approve and execute operation
 * - POST /api/approvals/{id}/reject - Reject operation
 * - GET /api/approvals/{id} - Get approval details
 * - GET /api/approvals/pending - List pending approvals for user
 *
 * Authentication:
 * All endpoints require authenticated user (web middleware).
 *
 * @example Frontend usage (React)
 * ```javascript
 * // Approve operation
 * const response = await fetch(`/api/approvals/${approvalId}/approve`, {
 *   method: 'POST',
 *   headers: { 'X-CSRF-TOKEN': csrf },
 * });
 * const data = await response.json();
 * // data.execution_result contains command output
 * ```
 *
 * @see ApprovalManager
 * @see ApprovalRequest
 */
class ApprovalController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  ApprovalManager  $approvalManager  Service for approval workflow
     */
    public function __construct(
        private ApprovalManager $approvalManager
    ) {}

    /**
     * Approve an approval request and execute the operation.
     *
     * Approves the request, executes it with approved: true flag (bypassing
     * security guards), and returns both approval status and execution results.
     *
     * Route: POST /api/approvals/{id}/approve
     * Auth: Required (web middleware)
     *
     * @param  Request  $request  HTTP request
     * @param  string  $id  Approval request ID
     * @return JsonResponse JSON response with approval and execution results
     *
     * @response 200 {
     *   "success": true,
     *   "approval": {...},
     *   "execution_result": {
     *     "executed": true,
     *     "success": true,
     *     "output": "command output...",
     *     "error": "",
     *     "exit_code": 0
     *   }
     * }
     * @response 400 {
     *   "error": "Request already processed",
     *   "status": "approved"
     * }
     * @response 404 {
     *   "message": "No query results for model [ApprovalRequest] {id}"
     * }
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::with('fragment')->findOrFail($id);

        if (! $approvalRequest->isPending()) {
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

    /**
     * Execute the approved operation with security bypass flag.
     *
     * Currently supports 'command' operations. The approved: true flag
     * in context tells security guards to bypass policy checks since
     * the user has explicitly approved the operation.
     *
     * @param  ApprovalRequest  $approval  The approved request to execute
     * @return array{
     *     executed: bool,
     *     success?: bool,
     *     output?: string,
     *     error?: string,
     *     exit_code?: int,
     *     reason?: string
     * } Execution result with output or error details
     */
    private function executeApprovedOperation(ApprovalRequest $approval): array
    {
        $details = $approval->operation_details;

        \Log::info('Executing approved operation', [
            'approval_id' => $approval->id,
            'operation_type' => $approval->operation_type,
            'command' => $details['command'] ?? 'N/A',
            'details' => $details,
        ]);

        try {
            if ($approval->operation_type === 'command') {
                $executor = app(\App\Services\Security\EnhancedShellExecutor::class);

                // Pass approved flag to bypass approval check in guards
                $context = array_merge($details['context'] ?? [], ['approved' => true]);

                \Log::info('About to execute command', [
                    'command' => $details['command'],
                    'context' => $context,
                ]);

                $result = $executor->execute($details['command'], ['context' => $context]);

                \Log::info('Command executed', [
                    'success' => $result['success'],
                    'output_length' => strlen($result['stdout'] ?? ''),
                ]);

                // Note: ?? operators are defensive - these keys should always exist per EnhancedShellExecutor contract
                return [
                    'executed' => true,
                    'success' => $result['success'],
                    'output' => $result['stdout'] ?? '',
                    'error' => $result['stderr'] ?? '',
                    'exit_code' => $result['exit_code'] ?? null,
                ];
            }

            return ['executed' => false, 'reason' => 'Operation type not yet supported'];
        } catch (\Exception $e) {
            \Log::error('Failed to execute approved operation', [
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'executed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reject an approval request and prevent execution.
     *
     * Marks the request as rejected with optional reason. The operation
     * will not be executed after rejection.
     *
     * Route: POST /api/approvals/{id}/reject
     * Auth: Required (web middleware)
     *
     * @param  Request  $request  HTTP request with optional 'reason' field
     * @param  string  $id  Approval request ID
     * @return JsonResponse JSON response with updated approval status
     *
     * @response 200 {
     *   "success": true,
     *   "approval": {...}
     * }
     * @response 400 {
     *   "error": "Request already processed",
     *   "status": "rejected"
     * }
     * @response 404 {
     *   "message": "No query results for model [ApprovalRequest] {id}"
     * }
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);

        if (! $approvalRequest->isPending()) {
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

    /**
     * Get details for a specific approval request.
     *
     * Returns formatted approval data including fragment preview if available.
     * Useful for displaying approval status after page refresh.
     *
     * Route: GET /api/approvals/{id}
     * Auth: Required (web middleware)
     *
     * @param  string  $id  Approval request ID
     * @return JsonResponse JSON response with approval details
     *
     * @response 200 {
     *   "approval": {
     *     "id": "123",
     *     "operationType": "command",
     *     "operationSummary": "Execute: ls -la",
     *     "riskScore": 35,
     *     "status": "pending",
     *     ...
     *   }
     * }
     * @response 404 {
     *   "message": "No query results for model [ApprovalRequest] {id}"
     * }
     */
    public function show(string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::with(['fragment', 'approvedBy'])->findOrFail($id);

        return response()->json([
            'approval' => $this->approvalManager->formatForChat($approvalRequest),
        ]);
    }

    /**
     * List pending approval requests for the current user.
     *
     * Returns up to 50 most recent pending approvals that either belong
     * to the current user or are unassigned. Useful for approval dashboards
     * and notification systems.
     *
     * Route: GET /api/approvals/pending
     * Auth: Required (web middleware)
     *
     * @return JsonResponse JSON response with pending approvals array
     *
     * @response 200 {
     *   "approvals": [
     *     {
     *       "id": "123",
     *       "operationType": "command",
     *       "status": "pending",
     *       ...
     *     }
     *   ]
     * }
     */
    public function pending(): JsonResponse
    {
        $pending = ApprovalRequest::pending()
            ->where('approved_by_user_id', auth()->id())
            ->orWhereNull('approved_by_user_id')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'approvals' => $pending->map(fn ($a) => $this->approvalManager->formatForChat($a)),
        ]);
    }
}
