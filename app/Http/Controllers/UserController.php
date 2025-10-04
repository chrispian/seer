<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'display_name' => $user->display_name,
                'avatar_path' => $user->avatar_path,
                'avatar_url' => $user->avatar_url,
                'use_gravatar' => $user->use_gravatar,
                'profile_settings' => $user->profile_settings,
                'profile_completed_at' => $user->profile_completed_at,
            ],
        ]);
    }
}
