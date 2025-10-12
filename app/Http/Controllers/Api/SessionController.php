<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Orchestration\SessionManager;
use App\Services\Orchestration\SessionPersistenceService;
use App\Services\Orchestration\TimeTrackingService;
use App\Models\WorkSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __construct(
        protected SessionManager $sessionManager,
        protected SessionPersistenceService $persistence,
        protected TimeTrackingService $timeTracking
    ) {}

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'nullable|uuid',
            'source' => 'nullable|in:cli,mcp,api,gui',
            'session_type' => 'nullable|in:work,planning,review',
        ]);

        $session = $this->sessionManager->startSession([
            'agent_id' => $validated['agent_id'] ?? null,
            'user_id' => auth()->id(),
            'source' => $validated['source'] ?? 'api',
            'session_type' => $validated['session_type'] ?? 'work',
        ]);

        return response()->json([
            'success' => true,
            'session' => $session,
        ], 201);
    }

    public function status(Request $request, ?string $sessionKey = null): JsonResponse
    {
        $sessionKey = $sessionKey ?? $request->header('X-Session-Key');

        if (!$sessionKey) {
            return response()->json([
                'success' => false,
                'error' => 'No session key provided',
            ], 400);
        }

        $session = WorkSession::where('session_key', $sessionKey)->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
            ], 404);
        }

        $context = $this->persistence->getSessionContext($session);

        return response()->json([
            'success' => true,
            'session' => $session,
            'context' => $context,
        ]);
    }

    public function end(Request $request, ?string $sessionKey = null): JsonResponse
    {
        $sessionKey = $sessionKey ?? $request->header('X-Session-Key');

        if (!$sessionKey) {
            return response()->json([
                'success' => false,
                'error' => 'No session key provided',
            ], 400);
        }

        $session = WorkSession::where('session_key', $sessionKey)->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
            ], 404);
        }

        $validated = $request->validate([
            'summary' => 'nullable|string',
        ]);

        $validation = $this->sessionManager->validateCompletion($session->id);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'error' => 'Session validation failed',
                'validation' => $validation,
            ], 422);
        }

        $session = $this->sessionManager->endSession($session->id, [
            'summary' => $validated['summary'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    public function pause(Request $request, ?string $sessionKey = null): JsonResponse
    {
        $sessionKey = $sessionKey ?? $request->header('X-Session-Key');

        if (!$sessionKey) {
            return response()->json([
                'success' => false,
                'error' => 'No session key provided',
            ], 400);
        }

        $session = WorkSession::where('session_key', $sessionKey)->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
            ], 404);
        }

        $session = $this->sessionManager->pauseSession($session->id);

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    public function resume(Request $request, ?string $sessionKey = null): JsonResponse
    {
        $sessionKey = $sessionKey ?? $request->header('X-Session-Key');

        if (!$sessionKey) {
            return response()->json([
                'success' => false,
                'error' => 'No session key provided',
            ], 400);
        }

        $session = WorkSession::where('session_key', $sessionKey)->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
            ], 404);
        }

        $session = $this->sessionManager->resumeSession($session->id);

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $sessions = WorkSession::where('user_id', auth()->id())
            ->orWhereNull('user_id')
            ->orderBy('started_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }
}
