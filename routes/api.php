<?php

use App\Http\Controllers\AnalyzeFragmentController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\CredentialController;
use App\Http\Controllers\Api\ModelController as ApiModelController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ChatApiController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\FragmentController;
use App\Http\Controllers\FragmentDetailController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SeerLogController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UserController;
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

// Provider API endpoints
Route::prefix('providers')->middleware(['throttle:60,1'])->group(function () {
    // Provider management
    Route::get('/', [ProviderController::class, 'index']);
    Route::get('/statistics', [ProviderController::class, 'statistics']);
    Route::post('/health-check', [ProviderController::class, 'bulkHealthCheck']);
    Route::post('/sync-capabilities', [ProviderController::class, 'syncCapabilities']);

    Route::prefix('{provider}')->group(function () {
        // Provider details and management
        Route::get('/', [ProviderController::class, 'show']);
        Route::put('/', [ProviderController::class, 'update']);
        Route::post('/toggle', [ProviderController::class, 'toggle']);

        // Provider testing (rate limited more strictly)
        Route::middleware(['throttle:10,1'])->group(function () {
            Route::post('/test', [ProviderController::class, 'test']);
            Route::get('/health', [ProviderController::class, 'health']);
        });

        // Credential management
        Route::prefix('credentials')->group(function () {
            Route::get('/', [CredentialController::class, 'index']);
            Route::post('/', [CredentialController::class, 'store']);
            Route::put('/{credential}', [CredentialController::class, 'update']);
            Route::delete('/{credential}', [CredentialController::class, 'destroy']);
            Route::post('/{credential}/test', [CredentialController::class, 'test'])->middleware(['throttle:5,1']);
        });

        // Provider models
        Route::get('/models', [ApiModelController::class, 'providerModels']);
        Route::put('/models/{model}', [ApiModelController::class, 'updateModel']);
    });
});

// Enhanced Model API endpoints
Route::prefix('models')->group(function () {
    Route::get('/', [ApiModelController::class, 'index']);
    Route::get('/show', [ApiModelController::class, 'show']);
    Route::get('/recommendations', [ApiModelController::class, 'recommendations']);
});

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

Route::middleware(['web', \App\Http\Middleware\ChatTelemetryMiddleware::class])->group(function () {
    Route::post('/messages', [ChatApiController::class, 'send']);
    Route::get('/chat/stream/{messageId}', [ChatApiController::class, 'stream']);
    Route::get('/user', [UserController::class, 'show']);
});

// Widget API routes
Route::prefix('widgets')->group(function () {
    Route::get('/today-activity', [WidgetApiController::class, 'todayActivity']);
    Route::get('/bookmarks', [WidgetApiController::class, 'bookmarks']);
    Route::get('/tool-calls', [WidgetApiController::class, 'toolCalls']);
});

// Inbox API routes
Route::prefix('inbox')->group(function () {
    Route::get('/', [InboxController::class, 'index']);
    Route::get('/stats', [InboxController::class, 'stats']);
    Route::get('/{fragmentId}/ai-assist', [InboxController::class, 'aiAssist']);
    Route::post('/{fragmentId}/accept', [InboxController::class, 'accept']);
    Route::post('/{fragmentId}/archive', [InboxController::class, 'archive']);
    Route::post('/{fragmentId}/skip', [InboxController::class, 'skip']);
    Route::post('/{fragmentId}/reopen', [InboxController::class, 'reopen']);
    Route::post('/accept-multiple', [InboxController::class, 'acceptMultiple']);
    Route::post('/accept-all', [InboxController::class, 'acceptAll']);
});

// Type System API routes
Route::prefix('types')->group(function () {
    Route::get('/', [TypeController::class, 'index']);
    Route::get('/stats', [TypeController::class, 'stats']);
    Route::get('/admin', [TypeController::class, 'admin']);
    Route::post('/{slug}/toggle', [TypeController::class, 'toggle']);
    Route::put('/{slug}/update', [TypeController::class, 'update']);
    Route::get('/{slug}', [TypeController::class, 'show']);
    Route::post('/{slug}/validate', [TypeController::class, 'validate']);
});

// Scheduler API routes
Route::prefix('schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::get('/stats', [ScheduleController::class, 'stats']);
    Route::get('/runs', [ScheduleController::class, 'runs']);
    Route::get('/{id}', [ScheduleController::class, 'show']);
});

// Orchestration Messaging API routes
Route::prefix('orchestration')->group(function () {
    Route::post('/agents/{agentId}/inbox', [\App\Http\Controllers\Orchestration\MessagingController::class, 'sendToAgent']);
    Route::get('/agents/{agentId}/inbox', [\App\Http\Controllers\Orchestration\MessagingController::class, 'listAgentInbox']);
    Route::post('/messages/{messageId}/read', [\App\Http\Controllers\Orchestration\MessagingController::class, 'markAsRead']);
    Route::post('/projects/{projectId}/broadcast', [\App\Http\Controllers\Orchestration\MessagingController::class, 'broadcast']);
    
    Route::post('/tasks/{taskId}/artifacts', [\App\Http\Controllers\Orchestration\ArtifactsController::class, 'createArtifact']);
    Route::get('/tasks/{taskId}/artifacts', [\App\Http\Controllers\Orchestration\ArtifactsController::class, 'listTaskArtifacts']);
    Route::get('/artifacts/{artifactId}/download', [\App\Http\Controllers\Orchestration\ArtifactsController::class, 'downloadArtifact']);
    
    Route::get('/tasks/{taskId}/activities', [\App\Http\Controllers\Orchestration\TaskActivityController::class, 'index']);
    Route::post('/tasks/{taskId}/activities', [\App\Http\Controllers\Orchestration\TaskActivityController::class, 'store']);
    Route::get('/tasks/{taskId}/activities/summary', [\App\Http\Controllers\Orchestration\TaskActivityController::class, 'summary']);
    Route::get('/tasks/{taskId}/activities/{activityId}', [\App\Http\Controllers\Orchestration\TaskActivityController::class, 'show']);
});

// Agent Profile API routes
Route::prefix('agent-profiles')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AgentProfileController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\AgentProfileController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\AgentProfileController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\Api\AgentProfileController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\AgentProfileController::class, 'destroy']);
});

// Agent API routes
Route::prefix('agents')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AgentController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\AgentController::class, 'store']);
    Route::get('/generate-designation', [\App\Http\Controllers\Api\AgentController::class, 'generateDesignation']);
    Route::get('/{id}', [\App\Http\Controllers\Api\AgentController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\Api\AgentController::class, 'update']);
    Route::post('/{id}/avatar', [\App\Http\Controllers\Api\AgentController::class, 'uploadAvatar']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\AgentController::class, 'destroy']);
});

// Approval endpoints (web middleware for session auth)
Route::middleware(['web'])->prefix('approvals')->group(function () {
    Route::post('/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/{id}/reject', [ApprovalController::class, 'reject']);
    Route::get('/{id}', [ApprovalController::class, 'show']);
    Route::get('/pending', [ApprovalController::class, 'pending']);
});

// Timeout endpoint for auto-canceling
Route::middleware(['web'])->post('/approvals/{id}/timeout', function($id) {
    $approval = \App\Models\ApprovalRequest::find($id);
    if ($approval && $approval->status === 'pending') {
        $approval->update(['status' => 'timeout']);
    }
    return response()->json(['success' => true]);
});
