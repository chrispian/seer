<?php

use Illuminate\Support\Facades\Route;
use HollisLabs\UiBuilder\Http\Controllers\DataSourceController;
use HollisLabs\UiBuilder\Http\Controllers\UiPageController;
use HollisLabs\UiBuilder\Http\Controllers\BuilderController;

Route::prefix('api')->middleware('api')->group(function () {
    // UI DataSource routes (standard REST)
    Route::prefix('/ui/datasources')->group(function () {
        Route::get('{alias}', [DataSourceController::class, 'query']);
        Route::post('{alias}', [DataSourceController::class, 'store']);
        Route::get('{alias}/capabilities', [DataSourceController::class, 'capabilities']);
        Route::get('{alias}/{id}', [DataSourceController::class, 'show']);
        Route::put('{alias}/{id}', [DataSourceController::class, 'update']);
        Route::patch('{alias}/{id}', [DataSourceController::class, 'update']);
        Route::delete('{alias}/{id}', [DataSourceController::class, 'destroy']);
    });
    
    // UI Page API routes
    Route::prefix('/ui')->group(function () {
        Route::get('/pages/{key}', [UiPageController::class, 'show']);
    });
    
    // Page Builder API routes
    Route::prefix('/ui/builder')->group(function () {
        // Session management
        Route::post('/save-progress', [BuilderController::class, 'saveProgress']);
        Route::get('/load-progress', [BuilderController::class, 'loadProgress']);
        Route::get('/preview', [BuilderController::class, 'getPreview']);
        
        // Page component management
        Route::get('/page-components', [BuilderController::class, 'getPageComponents']);
        Route::post('/page-component', [BuilderController::class, 'createPageComponent']);
        Route::get('/page-component/{id}', [BuilderController::class, 'getComponentForm']);
        Route::put('/page-component/{id}', [BuilderController::class, 'updatePageComponent']);
        Route::delete('/page-component/{id}', [BuilderController::class, 'deletePageComponent']);
        
        // Publishing
        Route::post('/save-draft', [BuilderController::class, 'saveDraft']);
        Route::post('/publish', [BuilderController::class, 'publish']);
    });
});
