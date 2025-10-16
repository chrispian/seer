<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TypesController;

Route::prefix('api/v2/types')->group(function () {
    Route::get('{alias}/query', [TypesController::class, 'query']);
    Route::get('{alias}/{id}',   [TypesController::class, 'show']);
});
