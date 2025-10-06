<?php

use App\Http\Controllers\InboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/inbox', [InboxController::class, 'index']);
    Route::post('/inbox/accept', [InboxController::class, 'accept']);
    Route::post('/inbox/accept-all', [InboxController::class, 'acceptAll']);
    Route::post('/inbox/archive', [InboxController::class, 'archive']);
    Route::post('/inbox/reopen', [InboxController::class, 'reopen']);
    Route::post('/inbox/tag', [InboxController::class, 'tag']);
});
