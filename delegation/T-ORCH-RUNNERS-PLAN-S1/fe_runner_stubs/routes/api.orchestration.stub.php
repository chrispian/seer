<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenHandsProxyController;

Route::prefix('runner/oh')->group(function () {
    Route::get('/sessions/{conversationId}', [OpenHandsProxyController::class, 'attach']);
    Route::post('/sessions/{conversationId}/action', [OpenHandsProxyController::class, 'action']);
});

Route::get('/runs/{runId}', fn($runId) => response()->json(['runId'=>$runId,'status'=>'stub']));
