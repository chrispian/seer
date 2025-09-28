<?php

use App\Http\Controllers\AnalyzeFragmentController;
use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ChatApiController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\FragmentController;
use App\Http\Controllers\FragmentDetailController;
use App\Http\Controllers\SeerLogController;
use Illuminate\Support\Facades\Route;

Route::post('/fragment', [FragmentController::class, 'store']);
Route::patch('/fragment/{fragment}', [FragmentController::class, 'update']);
Route::delete('/fragments/{fragment}', [FragmentController::class, 'destroy']);
Route::get('/fragment', [FragmentController::class, 'index']);
Route::get('/search', [FragmentController::class, 'search']);
Route::get('/recall', [FragmentController::class, 'recall']);
Route::post('/analyze-fragment', AnalyzeFragmentController::class);
Route::post('/log', [SeerLogController::class, 'store']);

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
Route::get('/bookmarks/recent', [BookmarkController::class, 'getRecent']);
Route::get('/bookmarks/search', [BookmarkController::class, 'search']);
Route::post('/bookmarks/{id}/mark-viewed', [BookmarkController::class, 'markAsViewed']);

// File upload endpoints
Route::post('/files', [FileUploadController::class, 'store']);

// Search endpoints
Route::get('/search/hybrid', [\App\Http\Controllers\FragmentController::class, 'hybridSearch'])->name('fragments.hybrid-search');


Route::middleware('web')->group(function () {
    Route::post('/messages', [ChatApiController::class, 'send']);
    Route::get('/chat/stream/{messageId}', [ChatApiController::class, 'stream']);
});
