<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class V2ShellController extends Controller
{
    public function show(string $key)
    {
        $isAuthenticated = Auth::check();
        $hasUsers = \App\Models\User::query()->exists();

        return view('v2.shell', [
            'isAuthenticated' => $isAuthenticated,
            'hasUsers' => $hasUsers,
            'user' => $isAuthenticated ? Auth::user()->only(['id', 'name', 'email']) : null,
            'pageKey' => $key,
        ]);
    }
}
