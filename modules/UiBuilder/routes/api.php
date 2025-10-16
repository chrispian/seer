<?php

use Illuminate\Support\Facades\Route;
use Modules\UiBuilder\app\Http\Controllers\DataSourceController;
use Modules\UiBuilder\app\Http\Controllers\V2\UiPageController;

Route::prefix('api')->middleware('api')->group(function () {
    // V2 UI DataSource routes (standard REST)
    Route::prefix('v2/ui/datasources')->group(function () {
        Route::get('{alias}', [DataSourceController::class, 'query']);
        Route::post('{alias}', [DataSourceController::class, 'store']);
        Route::get('{alias}/capabilities', [DataSourceController::class, 'capabilities']);
    });
    
    // V2 UI Page API routes
    Route::prefix('v2/ui')->group(function () {
        Route::get('/pages/{key}', [UiPageController::class, 'show']);
    });
});
