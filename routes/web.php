<?php

use App\Http\Controllers\AppShellController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

// Setup wizard routes
Route::middleware([\App\Http\Middleware\EnsureDefaultUser::class])->prefix('setup')->name('setup.')->group(function () {
    Route::get('/welcome', [SetupController::class, 'welcome'])->name('welcome');
    Route::get('/profile', [SetupController::class, 'profile'])->name('profile');
    Route::post('/profile', [SetupController::class, 'storeProfile'])->name('profile.store');
    Route::get('/avatar', [SetupController::class, 'avatar'])->name('avatar');
    Route::post('/avatar', [SetupController::class, 'storeAvatar'])->name('avatar.store');
    Route::get('/preferences', [SetupController::class, 'preferences'])->name('preferences');
    Route::post('/preferences', [SetupController::class, 'storePreferences'])->name('preferences.store');
    Route::get('/complete', [SetupController::class, 'complete'])->name('complete');
    Route::post('/finalize', [SetupController::class, 'finalize'])->name('finalize');
});

// Main application routes
Route::middleware([\App\Http\Middleware\EnsureDefaultUser::class])->group(function () {
    Route::get('/', [AppShellController::class, 'index'])->name('root');

    // Settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/avatar', [SettingsController::class, 'updateAvatar'])->name('avatar.update');
        Route::post('/preferences', [SettingsController::class, 'updatePreferences'])->name('preferences.update');
        Route::post('/ai', [SettingsController::class, 'updateAISettings'])->name('ai.update');
        Route::get('/export', [SettingsController::class, 'exportSettings'])->name('export');
    });
});

Route::get('/test-ui', function () {
    return view('layouts.app');
});

Route::get('/design', function () {
    return view('design');
});
