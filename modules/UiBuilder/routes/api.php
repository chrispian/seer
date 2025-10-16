<?php

use Illuminate\Support\Facades\Route;
use Modules\UiBuilder\app\Http\Controllers\DataSourceController;

Route::prefix('api')->middleware('api')->group(function () {
    // V2 UI DataSource routes
    Route::prefix('v2/ui/datasources')->group(function () {
        Route::get('{alias}', [DataSourceController::class, 'query']);
        Route::post('{alias}', [DataSourceController::class, 'query']);
        Route::get('{alias}/capabilities', [DataSourceController::class, 'capabilities']);
    });
});
