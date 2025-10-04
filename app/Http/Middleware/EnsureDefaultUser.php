<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDefaultUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Always ensure we have a default user logged in
        if (! Auth::check()) {
            $user = $this->getOrCreateDefaultUser();
            Auth::login($user);
        }

        return $next($request);
    }

    private function getOrCreateDefaultUser(): User
    {
        // Get the first user or create a default one
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'name' => 'User',
                'email' => 'user@fragments.local',
                'password' => bcrypt('password'), // Won't be used
                'email_verified_at' => now(),
            ]);
        }

        return $user;
    }
}
