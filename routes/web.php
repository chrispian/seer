<?php

use App\Http\Controllers\AppShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AppShellController::class, 'index'])->name('root');
Route::get('/test-ui', function () {
    return view('layouts.app');
});

Route::get('/design', function () {
    return view('design');
});
