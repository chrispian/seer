<?php

use Illuminate\Support\Facades\Route;
use Modules\UiBuilder\app\Http\Controllers\DataSourceController;

Route::prefix('api')->middleware('api')->group(function () {
    // V2 UI DataSource routes (with /query for POST, direct GET for capabilities)
    Route::prefix('v2/ui/datasources')->group(function () {
        Route::get('{alias}/query', [DataSourceController::class, 'query']);
        Route::post('{alias}/query', [DataSourceController::class, 'query']);
        Route::post('{alias}', [DataSourceController::class, 'store']);
        Route::get('{alias}/capabilities', [DataSourceController::class, 'capabilities']);
    });
    
    // Legacy API routes (backward compatibility)
    Route::prefix('datasources')->group(function () {
        Route::get('{alias}', [DataSourceController::class, 'query']);
        Route::post('{alias}', [DataSourceController::class, 'query']);
        Route::get('{alias}/capabilities', [DataSourceController::class, 'capabilities']);
    });
});
