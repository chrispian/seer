<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ImportSettingsRequest;
use App\Http\Requests\Settings\ResetSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ImportExportController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    /**
     * Import settings from uploaded JSON file
     */
    public function import(ImportSettingsRequest $request): JsonResponse
    {
        $user = Auth::user();

        // Rate limit: 5 imports per hour per user
        $key = 'settings-import:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'error' => "Too many import attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour

        try {
            // Get uploaded file and parse JSON
            $file = $request->file('file');
            $content = file_get_contents($file->getPathname());
            $settingsData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid JSON format: '.json_last_error_msg(),
                ], 422);
            }

            // Import settings using service
            $changes = $this->settingsService->importSettings($user, $settingsData);

            // Log successful import
            Log::info('Settings imported successfully', [
                'user_id' => $user->id,
                'changes' => $changes,
                'file_name' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings imported successfully',
                'changes' => $changes,
            ]);

        } catch (\Exception $e) {
            Log::error('Settings import failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file_name' => $request->file('file')?->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Import failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reset specific settings sections to defaults
     */
    public function reset(ResetSettingsRequest $request): JsonResponse
    {
        $user = Auth::user();
        $sections = $request->validated('sections', []);

        // Rate limit: 3 resets per hour per user
        $key = 'settings-reset:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'error' => "Too many reset attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour

        try {
            // Reset settings using service
            $reset = $this->settingsService->resetSettings($user, $sections);

            // Log successful reset
            Log::info('Settings reset successfully', [
                'user_id' => $user->id,
                'sections' => $reset,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings reset successfully',
                'reset_sections' => $reset,
            ]);

        } catch (\Exception $e) {
            Log::error('Settings reset failed', [
                'user_id' => $user->id,
                'sections' => $sections,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Reset failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate reset confirmation token
     */
    public function generateResetToken(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Generate a simple token for this session
        $token = Str::random(32);

        // Store in session for verification
        $request->session()->put('settings_reset_token', $token);
        $request->session()->put('settings_reset_token_expires', now()->addMinutes(5));

        Log::info('Reset token generated', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'expires_in' => 300, // 5 minutes
        ]);
    }
}
