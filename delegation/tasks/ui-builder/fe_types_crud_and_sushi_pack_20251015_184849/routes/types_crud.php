<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TypesCrudController;

Route::prefix('api/v2/types')->group(function () {
    Route::get('{alias}',        [TypesCrudController::class, 'index']);
    Route::post('{alias}',       [TypesCrudController::class, 'store']);
    Route::get('{alias}/{id}',   [TypesCrudController::class, 'show']);
    Route::put('{alias}/{id}',   [TypesCrudController::class, 'update']);
    Route::delete('{alias}/{id}',[TypesCrudController::class, 'destroy']);
});
