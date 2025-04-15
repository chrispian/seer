<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\SeerLogController;

    Route::post('/log', [SeerLogController::class, 'store']);
    Route::patch('/log/{log}', [SeerLogController::class, 'update']);
    Route::get('/log', [SeerLogController::class, 'index']);
    Route::get('/search', [SeerLogController::class, 'search']);
    Route::get('/recall', [SeerLogController::class, 'recall']);


