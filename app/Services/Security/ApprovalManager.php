<?php

namespace App\Services\Security;

use App\Models\ApprovalRequest;
use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class ApprovalManager
{
    public function __construct(
        private DryRunSimulator $simulator,
        private RiskScorer $scorer
    ) {}

    /**
     * Create an approval request for a high-risk operation
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

        // 1. Determine operation type
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
     * Approve an approval request
     */
    public function approveRequest(
        ApprovalRequest $request,
        int $userId,
        string $method = 'button_click',
        ?string $message = null
    ): void {
        if (!$request->isPending()) {
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
     * Reject an approval request
     */
    public function rejectRequest(
        ApprovalRequest $request,
        int $userId,
        string $method = 'button_click',
        ?string $message = null
    ): void {
        if (!$request->isPending()) {
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
     * Detect approval/rejection intent in natural language message
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
     * Get pending approval requests for a conversation
     */
    public function getPendingForConversation(string $conversationId): \Illuminate\Support\Collection
    {
        return ApprovalRequest::pending()
            ->byConversation($conversationId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Format approval request for chat response
     */
    public function formatForChat(ApprovalRequest $request): array
    {
        $contentSize = $this->calculateContentSize($request->operation_details);

        return [
            'approval_request' => [
                'id' => (string)$request->id,
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

    private function runDryRun(string $type, array $operation): ?array
    {
        try {
            return match($type) {
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

    private function calculateRisk(string $type, array $operation): array
    {
        try {
            return match($type) {
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
            'read_time_minutes' => max(1, ceil($wordCount / 200)), // Avg reading speed
        ];
    }
}
