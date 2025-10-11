<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\WorkSession;
use App\Services\Orchestration\SessionPersistenceService;
use Symfony\Component\HttpFoundation\Response;

class SessionAware
{
    public function __construct(
        protected SessionPersistenceService $persistence
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = $request->header('X-Session-Key');

        if ($sessionKey) {
            $session = WorkSession::where('session_key', $sessionKey)
                ->where('status', '!=', 'completed')
                ->where('status', '!=', 'abandoned')
                ->first();

            if ($session) {
                $request->attributes->set('work_session', $session);
                $request->attributes->set('work_session_id', $session->id);
                $request->attributes->set('work_session_key', $session->session_key);
            }
        }

        $response = $next($request);

        if ($sessionKey && isset($session)) {
            $response->headers->set('X-Session-Key', $session->session_key);
            $response->headers->set('X-Session-Status', $session->status);
        }

        return $response;
    }

    public static function getSessionFromRequest(Request $request): ?WorkSession
    {
        return $request->attributes->get('work_session');
    }

    public static function getSessionIdFromRequest(Request $request): ?string
    {
        return $request->attributes->get('work_session_id');
    }

    public static function getSessionKeyFromRequest(Request $request): ?string
    {
        return $request->attributes->get('work_session_key');
    }
}
