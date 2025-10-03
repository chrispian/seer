<?php

use App\Http\Controllers\AnalyzeFragmentController;
use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ChatApiController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\FragmentController;
use App\Http\Controllers\FragmentDetailController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SeerLogController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\WidgetApiController;
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

// Command execution endpoints
Route::post('/commands/execute', [CommandController::class, 'execute']);

// Model endpoints
Route::get('/models/available', [ModelController::class, 'available']);

// Chat session endpoints
Route::get('/chat-sessions', [ChatSessionController::class, 'index']);
Route::get('/chat-sessions/pinned', [ChatSessionController::class, 'pinned']);
Route::post('/chat-sessions', [ChatSessionController::class, 'store']);
Route::get('/chat-sessions/context', [ChatSessionController::class, 'getContext']);
Route::get('/chat-sessions/{chatSession}', [ChatSessionController::class, 'show']);
Route::put('/chat-sessions/{chatSession}', [ChatSessionController::class, 'update']);
Route::put('/chat-sessions/{chatSession}/model', [ChatSessionController::class, 'updateModel']);
Route::delete('/chat-sessions/{chatSession}', [ChatSessionController::class, 'destroy']);
Route::post('/chat-sessions/{chatSession}/pin', [ChatSessionController::class, 'togglePin']);
Route::post('/chat-sessions/pin-order', [ChatSessionController::class, 'updatePinOrder']);

// Vault endpoints
Route::get('/vaults', [VaultController::class, 'index']);
Route::post('/vaults', [VaultController::class, 'store']);
Route::get('/vaults/{vault}', [VaultController::class, 'show']);
Route::put('/vaults/{vault}', [VaultController::class, 'update']);
Route::delete('/vaults/{vault}', [VaultController::class, 'destroy']);
Route::post('/vaults/{vault}/set-default', [VaultController::class, 'setDefault']);

// Project endpoints
Route::get('/projects', [ProjectController::class, 'index']);
Route::post('/projects', [ProjectController::class, 'store']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);
Route::put('/projects/{project}', [ProjectController::class, 'update']);
Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
Route::get('/vaults/{vault}/projects', [ProjectController::class, 'getForVault']);
Route::post('/projects/{project}/set-default', [ProjectController::class, 'setDefault']);

// Search endpoints
Route::get('/search/hybrid', [\App\Http\Controllers\FragmentController::class, 'hybridSearch'])->name('fragments.hybrid-search');

Route::middleware('web')->group(function () {
    Route::post('/messages', [ChatApiController::class, 'send']);
    Route::get('/chat/stream/{messageId}', [ChatApiController::class, 'stream']);
});

// Widget API routes
Route::prefix('widgets')->group(function () {
    Route::get('/today-activity', [WidgetApiController::class, 'todayActivity']);
    Route::get('/bookmarks', [WidgetApiController::class, 'bookmarks']);
    Route::get('/tool-calls', [WidgetApiController::class, 'toolCalls']);
});
