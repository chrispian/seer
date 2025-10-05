<?php

use App\Http\Controllers\Internal\DbQueryController;
use App\Http\Controllers\Internal\ExportController;
use App\Http\Controllers\Internal\MemoryController;
use Illuminate\Support\Facades\Route;

Route::post('/db/query', [DbQueryController::class, 'query']);
Route::post('/export/generate', [ExportController::class, 'generate']);
Route::post('/memory/write', [MemoryController::class, 'write']);
Route::post('/memory/search', [MemoryController::class, 'search']);
