<?php

use Illuminate\Support\Facades\Route;
use Modules\UiBuilder\Controllers\UiDataSourceController;
use Modules\UiBuilder\Controllers\DataSourceController;

Route::prefix('api')->middleware('api')->group(function () {
    // V2 UI routes
    Route::prefix('v2/ui')->group(function () {
        Route::get('datasources/{key}', [UiDataSourceController::class, 'show']);
        Route::post('datasources/{key}', [UiDataSourceController::class, 'execute']);
    });
    
    // Legacy API routes (for backward compatibility)
    Route::prefix('datasources')->group(function () {
        Route::get('{key}', [DataSourceController::class, 'show']);
        Route::post('{key}', [DataSourceController::class, 'execute']);
    });
});