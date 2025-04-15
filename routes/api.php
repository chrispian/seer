<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\FragmentController;

    Route::post('/fragment', [FragmentController::class, 'store']);
    Route::patch('/fragment/{fragment}', [FragmentController::class, 'update']);
    Route::get('/fragment', [FragmentController::class, 'index']);
    Route::get('/search', [FragmentController::class, 'search']);
    Route::get('/recall', [FragmentController::class, 'recall']);


