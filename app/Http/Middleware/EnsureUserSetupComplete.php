<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSetupComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip setup check for:
        // - Unauthenticated users
        // - Setup-related routes
        // - API routes (handled separately)
        // - Asset requests
        if (! $user ||
            $request->routeIs('setup.*') ||
            $request->is('api/*') ||
            $request->is('assets/*') ||
            $request->is('storage/*')) {
            return $next($request);
        }

        // If user hasn't completed setup, redirect to setup wizard
        if (! $user->hasCompletedSetup()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Profile setup required',
                    'setup_required' => true,
                ], 422);
            }

            return redirect()->route('setup.welcome');
        }

        return $next($request);
    }
}
