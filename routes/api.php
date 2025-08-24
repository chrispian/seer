<?php

use App\Http\Controllers\AnalyzeFragmentController;
use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FragmentDetailController;
use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\FragmentController;

    Route::post('/fragment', [FragmentController::class, 'store']);
    Route::patch('/fragment/{fragment}', [FragmentController::class, 'update']);
    Route::get('/fragment', [FragmentController::class, 'index']);
    Route::get('/search', [FragmentController::class, 'search']);
    Route::get('/recall', [FragmentController::class, 'recall']);
    Route::post('/analyze-fragment', AnalyzeFragmentController::class);

    // Autocomplete endpoints
    Route::get('/autocomplete/commands', [AutocompleteController::class, 'commands']);
    Route::get('/autocomplete/contacts', [AutocompleteController::class, 'contacts']);
    Route::get('/autocomplete/fragments', [AutocompleteController::class, 'fragments']);

    // Detail endpoints for modal display
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
    Route::get('/fragments/{id}', [FragmentDetailController::class, 'show']);

    // Bookmark endpoints
    Route::get('/fragments/{id}/bookmark', [BookmarkController::class, 'checkBookmarkStatus']);
    Route::post('/fragments/{id}/bookmark', [BookmarkController::class, 'toggleBookmark']);



