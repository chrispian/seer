<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class AppShellController extends Controller
{
    public function index()
    {
        $isAuthenticated = Auth::check();
        $hasUsers = \App\Models\User::query()->exists();

        return view('app.chat', [
            'isAuthenticated' => $isAuthenticated,
            'hasUsers' => $hasUsers,
            'user' => $isAuthenticated ? Auth::user()->only(['id', 'name', 'email']) : null,
        ]);
    }
}
