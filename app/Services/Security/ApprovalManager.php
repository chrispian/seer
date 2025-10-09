<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\ApprovalRequest;
use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

/**
 * Manages approval workflow for high-risk security operations.
 *
 * Coordinates the creation, approval, rejection, and formatting of approval
 * requests for operations that require explicit user consent. Integrates with
 * RiskScorer for risk assessment and DryRunSimulator for operation preview.
 *
 * Key Features:
 * - Automatic risk assessment and approval requirement determination
 * - Dry-run simulation before execution
 * - Large content storage in Fragment model for modal preview
 * - Auto-timeout of stale approval requests (5 minutes default)
 * - User attribution and audit trail
 * - Natural language approval detection in chat messages
 *
 * Approval Lifecycle:
 * 1. createApprovalRequest() - Risk assessment and request creation
 * 2. User sees approval UI with Approve/Reject buttons
 * 3. approveRequest() or rejectRequest() - User decision
 * 4. Operation executes with approved: true flag (bypasses guards)
 *
 * @example Request approval for a command
 * ```php
 * $manager = app(ApprovalManager::class);
 *
 * $approval = $manager->createApprovalRequest([
 *     'type' => 'command',
 *     'command' => 'git push --force',
 *     'summary' => 'Execute: git push --force',
 *     'context' => ['workdir' => '/workspace', 'repo' => 'production'],
 * ], 'conversation-123', 'message-456');
 *
 * if ($approval) {
 *     // Show approval UI to user
 *     return response()->json([
 *         'requires_approval' => true,
 *         'approval_request' => $manager->formatForChat($approval),
 *     ]);
 * }
 * ```
 * @example Approve and execute
 * ```php
 * $approval = ApprovalRequest::find($id);
 * $manager->approveRequest($approval, auth()->id(), 'button_click');
 *
 * // Now execute with approved flag
 * $executor->execute($command, ['context' => ['approved' => true]]);
 * ```
 *
 * @see ApprovalRequest
 * @see RiskScorer
 * @see DryRunSimulator
 */
class ApprovalManager
{
    /**
     * Average reading speed in words per minute.
     * Used to calculate estimated read time for approval content.
     */
    private const AVERAGE_READING_SPEED_WPM = 200;

    public function __construct(
        private RiskScorer $scorer,
        private DryRunSimulator $simulator
    ) {}

    /**
     * Create an approval request for a high-risk operation.
     *
     * Performs the following steps:
     * 1. Auto-timeout any existing pending approvals in this conversation
     * 2. Determine operation type (command, file_operation, network, tool_call)
     * 3. Run dry-run simulation to preview effects
     * 4. Calculate risk score
     * 5. Return null if auto-approved (score < 26)
     * 6. Store large content in Fragment for modal preview
     * 7. Create ApprovalRequest record with 5-minute timeout
     *
     * @param array{
     *     type: string,
     *     command?: string,
     *     summary: string,
     *     context?: array,
     *     full_content?: string,
     *     fragment_type?: string,
     *     title?: string,
     *     tags?: array
     * } $operation Operation details to evaluate
     * @param  string  $conversationId  The conversation ID for grouping approvals
     * @param  string|null  $messageId  Optional message ID for linking
     * @return ApprovalRequest|null Approval request if approval needed, null if auto-approved
     *
     * @example
     * ```php
     * $approval = $manager->createApprovalRequest([
     *     'type' => 'command',
     *     'command' => 'rm -rf /tmp/*',
     *     'summary' => 'Execute: rm -rf /tmp/*',
     *     'context' => ['workdir' => '/tmp'],
     * ], 'conv-123');
     *
     * if ($approval) {
     *     // Approval required - show UI
     * } else {
     *     // Auto-approved - execute directly
     * }
     * ```
     */
    public function createApprovalRequest(array $operation, string $conversationId, ?string $messageId = null): ?ApprovalRequest
    {
        // 0. Auto-timeout any existing pending approvals in this conversation
        $oldPending = ApprovalRequest::where('conversation_id', $conversationId)
            ->where('status', 'pending')
            ->get();

        if ($oldPending->isNotEmpty()) {
            $count = $oldPending->count();
            foreach ($oldPending as $old) {
                $old->update(['status' => 'timeout']);
            }

            Log::info('Auto-timed out old pending approvals', [
                'conversation_id' => $conversationId,
                'count' => $count,
            ]);
        }

        // 1. Determine operation type (defensive fallback, should always be provided)
        $operationType = $operation['type'] ?? 'tool_call';

        // 2. Run dry-run simulation
        $dryRun = $this->runDryRun($operationType, $operation);

        // 3. Calculate risk
        $risk = $this->calculateRisk($operationType, $operation);

        // 4. Check if approval needed
        if ($risk['action'] === 'auto_approve') {
            Log::info('Operation auto-approved', [
                'type' => $operationType,
                'risk_score' => $risk['score'],
            ]);

            return null; // No approval needed
        }

        // 5. Determine if should use modal (based on content size)
        $contentSize = $this->calculateContentSize($operation);
        $fragmentId = null;

        if ($contentSize['use_modal']) {
            // Store as fragment
            $fragment = Fragment::create([
                'type' => $operation['fragment_type'] ?? 'plan',
                'title' => $operation['title'] ?? $operation['summary'],
                'message' => $operation['full_content'],
                'tags' => array_merge(
                    ['approval-request', 'security'],
                    $operation['tags'] ?? []
                ),
                'metadata' => [
                    'risk_score' => $risk['score'],
                    'risk_level' => $risk['level'],
                    'operation_type' => $operationType,
                    'word_count' => $contentSize['word_count'],
                    'read_time_minutes' => $contentSize['read_time_minutes'],
                ],
            ]);
            $fragmentId = $fragment->id;

            Log::info('Created fragment for approval request', [
                'fragment_id' => $fragmentId,
                'word_count' => $contentSize['word_count'],
            ]);
        }

        // 6. Create approval request
        $approvalRequest = ApprovalRequest::create([
            'operation_type' => $operationType,
            'operation_summary' => $operation['summary'],
            'operation_details' => $operation,
            'risk_score' => $risk['score'],
            'risk_level' => $risk['level'],
            'risk_factors' => $risk['factors'],
            'dry_run_result' => $dryRun,
            'fragment_id' => $fragmentId,
            'status' => 'pending',
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'timeout_at' => now()->addMinutes(config('security.approval.timeout_minutes', 5)),
        ]);

        Log::info('Approval request created', [
            'approval_id' => $approvalRequest->id,
            'risk_score' => $risk['score'],
            'risk_level' => $risk['level'],
            'use_modal' => $contentSize['use_modal'],
        ]);

        return $approvalRequest;
    }

    /**
     * Approve an approval request and mark it for execution.
     *
     * Updates the approval status, records user attribution, and logs to
     * audit trail. After approval, the operation can execute with the
     * approved: true flag to bypass security guards.
     *
     * @param  ApprovalRequest  $request  The approval request to approve
     * @param  int  $userId  The user ID granting approval
     * @param  string  $method  How approval was granted ('button_click'|'text_response'|'voice')
     * @param  string|null  $message  Optional user message/justification
     *
     * @throws \RuntimeException If request is not in pending status
     *
     * @example
     * ```php
     * $approval = ApprovalRequest::find($id);
     * $manager->approveRequest($approval, auth()->id(), 'button_click');
     * // Now execute with approved flag
     * ```
     */
    public function approveRequest(
        ApprovalRequest $request,
        int $userId,
        string $method = 'button_click',
        ?string $message = null
    ): void {
        if (! $request->isPending()) {
            throw new \RuntimeException("Cannot approve request in status: {$request->status}");
        }

        $request->update([
            'status' => 'approved',
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
            'approval_method' => $method,
            'user_message' => $message,
        ]);

        // Log to audit trail
        activity()
            ->causedBy($userId)
            ->performedOn($request)
            ->withProperties([
                'operation_type' => $request->operation_type,
                'risk_score' => $request->risk_score,
                'method' => $method,
            ])
            ->event('approval_granted')
            ->log("Approved {$request->operation_type}: {$request->operation_summary}");

        Log::info('Approval granted', [
            'approval_id' => $request->id,
            'user_id' => $userId,
            'method' => $method,
        ]);
    }

    /**
     * Reject an approval request and prevent execution.
     *
     * Updates the approval status, records user attribution, and logs to
     * audit trail. The operation will not execute after rejection.
     *
     * @param  ApprovalRequest  $request  The approval request to reject
     * @param  int  $userId  The user ID denying approval
     * @param  string  $method  How rejection was communicated ('button_click'|'text_response')
     * @param  string|null  $message  Optional reason for rejection
     *
     * @throws \RuntimeException If request is not in pending status
     *
     * @example
     * ```php
     * $approval = ApprovalRequest::find($id);
     * $manager->rejectRequest($approval, auth()->id(), 'button_click', 'Too risky');
     * ```
     */
    public function rejectRequest(
        ApprovalRequest $request,
        int $userId,
        string $method = 'button_click',
        ?string $message = null
    ): void {
        if (! $request->isPending()) {
            throw new \RuntimeException("Cannot reject request in status: {$request->status}");
        }

        $request->update([
            'status' => 'rejected',
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
            'approval_method' => $method,
            'user_message' => $message,
        ]);

        // Log to audit trail
        activity()
            ->causedBy($userId)
            ->performedOn($request)
            ->withProperties([
                'operation_type' => $request->operation_type,
                'risk_score' => $request->risk_score,
                'method' => $method,
                'reason' => $message,
            ])
            ->event('approval_denied')
            ->log("Rejected {$request->operation_type}: {$request->operation_summary}");

        Log::info('Approval rejected', [
            'approval_id' => $request->id,
            'user_id' => $userId,
            'method' => $method,
        ]);
    }

    /**
     * Detect approval/rejection intent in natural language message.
     *
     * Checks user message for approval or rejection keywords to enable
     * conversational approval workflow (e.g., user types "yes" instead
     * of clicking button).
     *
     * @param  string  $message  The user's text message
     * @return string|null 'approve', 'reject', or null if ambiguous
     *
     * @example
     * ```php
     * $intent = $manager->detectApprovalInMessage('yes, go ahead');
     * // Returns: 'approve'
     *
     * $intent = $manager->detectApprovalInMessage('no, cancel that');
     * // Returns: 'reject'
     * ```
     */
    public function detectApprovalInMessage(string $message): ?string
    {
        $config = config('security.approval.natural_language_approval', []);
        $approvalKeywords = $config['approval_keywords'] ?? ['yes', 'approve', 'go ahead', 'do it', 'proceed', 'ok', 'sure'];
        $rejectionKeywords = $config['rejection_keywords'] ?? ['no', 'reject', 'cancel', 'stop', 'don\'t', 'nope'];

        $messageLower = strtolower($message);

        // Check for rejection first (more conservative)
        foreach ($rejectionKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return 'reject';
            }
        }

        // Check for approval
        foreach ($approvalKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return 'approve';
            }
        }

        return null; // Ambiguous
    }

    /**
     * Get all pending approval requests for a conversation.
     *
     * Returns requests in descending order (newest first) to show the
     * most recent pending approval at the top.
     *
     * @param  string  $conversationId  The conversation ID to query
     * @return \Illuminate\Support\Collection<int, ApprovalRequest> Collection of pending approvals
     *
     * @example
     * ```php
     * $pending = $manager->getPendingForConversation('conv-123');
     * if ($pending->isNotEmpty()) {
     *     // Show pending approval UI
     * }
     * ```
     */
    public function getPendingForConversation(string $conversationId): \Illuminate\Support\Collection
    {
        return ApprovalRequest::pending()
            ->byConversation($conversationId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Format approval request for chat UI display.
     *
     * Converts database model to frontend-friendly format with camelCase
     * keys and includes modal preview information if content is large.
     *
     * @param  ApprovalRequest  $request  The approval request to format
     * @return array{
     *     approval_request: array{
     *         id: string,
     *         operationType: string,
     *         operationSummary: string,
     *         riskScore: int,
     *         riskLevel: string,
     *         riskFactors: array,
     *         status: string,
     *         approvedAt: ?string,
     *         rejectedAt: ?string,
     *         useModal: bool,
     *         fragmentId: ?int,
     *         wordCount: int,
     *         readTimeMinutes: int
     *     }
     * } Formatted approval data for frontend
     *
     * @example
     * ```php
     * $formatted = $manager->formatForChat($approval);
     * return response()->json([
     *     'requires_approval' => true,
     *     ...$formatted,
     * ]);
     * ```
     */
    public function formatForChat(ApprovalRequest $request): array
    {
        $contentSize = $this->calculateContentSize($request->operation_details);

        return [
            'approval_request' => [
                'id' => (string) $request->id,
                'operationType' => $request->operation_type,
                'operationSummary' => $request->operation_summary,
                'riskScore' => $request->risk_score,
                'riskLevel' => $request->risk_level,
                'riskFactors' => $request->risk_factors,
                'status' => $request->status,
                'approvedAt' => $request->approved_at?->toIso8601String(),
                'rejectedAt' => $request->status === 'rejected' ? $request->approved_at?->toIso8601String() : null,

                // Modal preview data
                'useModal' => $contentSize['use_modal'],
                'fragmentId' => $request->fragment_id,
                'fragmentTitle' => $request->fragment?->title,
                'fragmentContent' => $request->fragment?->message,
                'wordCount' => $contentSize['word_count'],
                'readTimeMinutes' => $contentSize['read_time_minutes'],
            ],
        ];
    }

    // ==================== Private Methods ====================

    /**
     * Run dry-run simulation for the operation.
     *
     * Delegates to DryRunSimulator based on operation type to preview
     * side effects without actually executing.
     *
     * @param  string  $type  Operation type ('command'|'file_operation'|'network'|'tool_call')
     * @param  array<string, mixed>  $operation  Operation details
     * @return array|null Simulation result or null if simulation failed
     */
    private function runDryRun(string $type, array $operation): ?array
    {
        try {
            return match ($type) {
                'command' => $this->simulator->simulateCommand(
                    $operation['command'],
                    $operation['context'] ?? []
                ),
                'file_operation' => $this->simulator->simulateFileOperation(
                    $operation['path'],
                    $operation['operation'],
                    $operation['context'] ?? []
                ),
                'network' => $this->simulator->simulateNetworkOperation(
                    $operation['url'],
                    $operation['context'] ?? []
                ),
                'tool_call' => $this->simulator->simulateToolCall(
                    $operation['tool_id'],
                    $operation['parameters'] ?? [],
                    $operation['context'] ?? []
                ),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Dry-run simulation failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Calculate risk score for the operation.
     *
     * Delegates to RiskScorer based on operation type. Returns conservative
     * high-risk score (75) if calculation fails to ensure approval required.
     *
     * @param  string  $type  Operation type ('command'|'file_operation'|'network'|'tool_call')
     * @param  array<string, mixed>  $operation  Operation details
     * @return array{
     *     score: int,
     *     level: string,
     *     action: string,
     *     factors: array,
     *     requires_approval: bool
     * } Risk assessment result
     */
    private function calculateRisk(string $type, array $operation): array
    {
        try {
            return match ($type) {
                'command' => $this->scorer->scoreCommand(
                    $operation['command'],
                    $operation['context'] ?? []
                ),
                'file_operation' => $this->scorer->scoreFileOperation(
                    $operation['path'],
                    $operation['operation'],
                    $operation['context'] ?? []
                ),
                'network' => $this->scorer->scoreNetworkOperation(
                    $operation['domain'] ?? parse_url($operation['url'], PHP_URL_HOST),
                    $operation['context'] ?? []
                ),
                'tool_call' => $this->scorer->scoreToolCall(
                    $operation['tool_id'],
                    $operation['parameters'] ?? []
                ),
                default => [
                    'score' => 50,
                    'level' => 'medium',
                    'action' => 'require_approval',
                    'factors' => ['Unknown operation type'],
                    'requires_approval' => true,
                ],
            };
        } catch (\Exception $e) {
            Log::error('Risk calculation failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            // Default to requiring approval on error
            return [
                'score' => 75,
                'level' => 'high',
                'action' => 'require_approval',
                'factors' => ['Risk calculation error - defaulting to high'],
                'requires_approval' => true,
            ];
        }
    }

    /**
     * Calculate content size and determine if modal preview is needed.
     *
     * Uses thresholds from config to decide between inline approval
     * (small content) and modal approval (large content requiring preview).
     *
     * Thresholds (configurable):
     * - max_words: 100
     * - max_characters: 500
     * - max_lines: 15
     *
     * @param  array<string, mixed>  $operation  Operation with full_content or summary
     * @return array{
     *     use_modal: bool,
     *     word_count: int,
     *     read_time_minutes: int,
     *     char_count: int,
     *     line_count: int
     * } Content size analysis
     */
    private function calculateContentSize(array $operation): array
    {
        $content = $operation['full_content'] ?? $operation['summary'] ?? '';
        $wordCount = str_word_count($content);
        $charCount = strlen($content);
        $lineCount = substr_count($content, "\n") + 1;

        $config = config('security.approval.inline_approval', [
            'max_words' => 100,
            'max_characters' => 500,
            'max_lines' => 15,
        ]);

        $useModal = (
            $wordCount > $config['max_words'] ||
            $charCount > $config['max_characters'] ||
            $lineCount > $config['max_lines']
        );

        return [
            'use_modal' => $useModal,
            'word_count' => $wordCount,
            'char_count' => $charCount,
            'line_count' => $lineCount,
            'read_time_minutes' => max(1, ceil($wordCount / self::AVERAGE_READING_SPEED_WPM)),
        ];
    }
}
