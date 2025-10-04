<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserProfile;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\File;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('settings.index', [
            'user' => $user,
            'profile_settings' => $user->profile_settings ?? [],
        ]);
    }

    public function updateProfile(Request $request, CreateUserProfile $createProfile)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.Auth::id(),
        ]);

        $user = Auth::user();

        // Update basic user fields
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Update profile using action
        $createProfile($user, [
            'display_name' => $validated['display_name'],
        ]);

        Log::info('User profile updated', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);
    }

    public function updateAvatar(Request $request, AvatarService $avatarService)
    {
        $request->validate([
            'avatar' => ['nullable', File::image()->max(5000)],
            'use_gravatar' => 'boolean',
        ]);

        $user = Auth::user();

        try {
            if ($request->hasFile('avatar')) {
                $avatarService->processUpload($user, $request->file('avatar'));
            } else {
                // Update gravatar preference
                $user->update(['use_gravatar' => $request->boolean('use_gravatar', true)]);

                // Cache Gravatar if enabled
                if ($user->use_gravatar && $user->email) {
                    $avatarService->cacheGravatar($user);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar updated successfully',
                'avatar_url' => $avatarService->getAvatarUrl($user),
            ]);

        } catch (\Exception $e) {
            Log::error('Avatar update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'nullable|string|in:light,dark,system',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'notifications' => 'nullable|array',
            'notifications.email' => 'boolean',
            'notifications.desktop' => 'boolean',
            'notifications.sound' => 'boolean',
            'layout' => 'nullable|array',
            'layout.sidebar_collapsed' => 'boolean',
            'layout.right_rail_width' => 'nullable|integer|min:200|max:600',
            'layout.compact_mode' => 'boolean',
        ]);

        $user = Auth::user();
        $currentSettings = $user->profile_settings ?? [];

        // Merge new settings with existing ones
        $newSettings = array_merge($currentSettings, array_filter($validated, fn ($value) => ! is_null($value)));

        $user->update(['profile_settings' => $newSettings]);

        Log::info('User preferences updated', [
            'user_id' => $user->id,
            'updated_keys' => array_keys($validated),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'settings' => $newSettings,
        ]);
    }

    public function updateAISettings(Request $request)
    {
        $validated = $request->validate([
            'default_provider' => 'nullable|string|max:50',
            'default_model' => 'nullable|string|max:100',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:32000',
            'stream_responses' => 'boolean',
            'auto_title' => 'boolean',
            'context_length' => 'nullable|integer|min:1000|max:128000',
        ]);

        $user = Auth::user();
        $currentSettings = $user->profile_settings ?? [];

        // Update AI-specific settings
        $aiSettings = array_merge($currentSettings['ai'] ?? [], array_filter($validated, fn ($value) => ! is_null($value)));
        $currentSettings['ai'] = $aiSettings;

        $user->update(['profile_settings' => $currentSettings]);

        Log::info('User AI settings updated', [
            'user_id' => $user->id,
            'updated_keys' => array_keys($validated),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI settings updated successfully',
            'ai_settings' => $aiSettings,
        ]);
    }

    public function exportSettings()
    {
        $user = Auth::user();

        $export = [
            'profile' => [
                'display_name' => $user->display_name,
                'use_gravatar' => $user->use_gravatar,
            ],
            'settings' => $user->profile_settings ?? [],
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
        ];

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="fragments-settings-'.now()->format('Y-m-d').'.json"');
    }
}
