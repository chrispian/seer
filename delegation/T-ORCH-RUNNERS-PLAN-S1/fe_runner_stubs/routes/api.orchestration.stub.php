<?php

use App\Http\Controllers\OpenHandsProxyController;
use Illuminate\Support\Facades\Route;

Route::prefix('runner/oh')->group(function () {
    Route::get('/sessions/{conversationId}', [OpenHandsProxyController::class, 'attach']);
    Route::post('/sessions/{conversationId}/action', [OpenHandsProxyController::class, 'action']);
});

Route::get('/runs/{runId}', fn ($runId) => response()->json(['runId' => $runId, 'status' => 'stub']));
