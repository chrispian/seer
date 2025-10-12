<?php

namespace App\Commands\Security;

use App\Commands\BaseCommand;
use App\Models\ApprovalRequest;

class DashboardCommand extends BaseCommand
{
    public function handle(): array
    {
        $approvalRequests = $this->getApprovalRequests();
        $stats = $this->getSecurityStats();

        return $this->respond([
            'approval_requests' => $approvalRequests,
            'stats' => $stats,
        ]);
    }

    private function getApprovalRequests(): array
    {
        $requests = ApprovalRequest::query()
            ->with(['fragment', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'operation_type' => $request->operation_type,
                'status' => $request->status,
                'risk_score' => $request->risk_score,
                'risk_level' => $this->getRiskLevel($request->risk_score),
                'fragment_id' => $request->fragment_id,
                'fragment_title' => $request->fragment?->title ?? 'Unknown',
                'conversation_id' => $request->conversation_id,
                'operation_details' => $request->operation_details ?? [],
                'risk_factors' => $request->risk_factors ?? [],
                'timeout_at' => $request->timeout_at?->toISOString(),
                'approved_at' => $request->approved_at?->toISOString(),
                'approved_by_user_id' => $request->approved_by_user_id,
                'approved_by_name' => $request->approvedBy?->name,
                'created_at' => $request->created_at->toISOString(),
            ];
        })->all();
    }

    private function getSecurityStats(): array
    {
        return [
            'pending_count' => ApprovalRequest::pending()->count(),
            'approved_today' => ApprovalRequest::approved()
                ->whereDate('approved_at', today())
                ->count(),
            'rejected_today' => ApprovalRequest::rejected()
                ->whereDate('created_at', today())
                ->count(),
            'timed_out_count' => ApprovalRequest::timedOut()->count(),
            'high_risk_pending' => ApprovalRequest::pending()
                ->where('risk_score', '>=', 70)
                ->count(),
        ];
    }

    private function getRiskLevel(int $score): string
    {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    public static function getName(): string
    {
        return 'Security Dashboard';
    }

    public static function getDescription(): string
    {
        return 'View approval requests, risk scores, and security status';
    }

    public static function getUsage(): string
    {
        return '/security';
    }

    public static function getCategory(): string
    {
        return 'Security';
    }
}
