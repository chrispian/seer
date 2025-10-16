<?php

namespace Modules\UiBuilder\app\Http\Controllers\V2;

use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;

class V2ShellController extends Controller
{
    public function show(string $key): View
    {
        return view('ui-builder::v2.shell', [
            'pageKey' => $key,
            'isAuthenticated' => auth()->check(),
            'hasUsers' => \App\Models\User::exists(),
            'user' => auth()->user()?->only(['id', 'name', 'email']),
        ]);
    }
}
