<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class V2ShellController extends Controller
{
    public function show(string $key): View
    {
        return view('v2.shell', [
            'pageKey' => $key,
            'isAuthenticated' => auth()->check(),
            'hasUsers' => \App\Models\User::exists(),
            'user' => auth()->user()?->only(['id', 'name', 'email']),
        ]);
    }
}
