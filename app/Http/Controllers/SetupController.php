<?php

namespace App\Http\Controllers;

use App\Actions\CompleteUserSetup;
use App\Actions\CreateUserProfile;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\File;

class SetupController extends Controller
{
    public function welcome()
    {
        $user = Auth::user();

        // If already completed, redirect to main app
        if ($user && $user->hasCompletedSetup()) {
            return redirect('/');
        }

        return view('setup.welcome');
    }

    public function profile()
    {
        return view('setup.profile');
    }

    public function storeProfile(Request $request, CreateUserProfile $createProfile)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'profile_settings' => 'nullable|array',
        ]);

        $user = Auth::user();
        $createProfile($user, $validated);

        Log::info('Setup profile step completed', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'next_step' => route('setup.avatar'),
        ]);
    }

    public function avatar()
    {
        return view('setup.avatar');
    }

    public function storeAvatar(Request $request, AvatarService $avatarService)
    {
        Log::info('Avatar setup request received', [
            'data' => $request->all(),
            'files' => $request->allFiles(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->header('User-Agent'),
            'is_ajax' => $request->ajax(),
        ]);

        try {
            $request->validate([
                'avatar' => ['nullable', File::image()->max(5000)],
                'use_gravatar' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Avatar validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        }

        $user = Auth::user();

        // Convert string boolean to actual boolean
        $useGravatar = $request->input('use_gravatar') === 'true' || $request->boolean('use_gravatar', true);

        try {
            if ($request->hasFile('avatar')) {
                $avatarService->processUpload($user, $request->file('avatar'));

                Log::info('Avatar upload initiated', [
                    'user_id' => $user->id,
                    'file_size' => $request->file('avatar')->getSize(),
                ]);
            }

            // Always update gravatar preference
            $user->update(['use_gravatar' => $useGravatar]);

            // Cache Gravatar if enabled and no file upload
            if ($useGravatar && $user->email && ! $request->hasFile('avatar')) {
                $avatarService->cacheGravatar($user);
            }

            return response()->json([
                'success' => true,
                'next_step' => route('setup.preferences'),
            ]);

        } catch (\Exception $e) {
            Log::error('Avatar setup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function preferences()
    {
        return view('setup.preferences');
    }

    public function storePreferences(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'nullable|string|in:light,dark,system',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'ai_provider_preference' => 'nullable|string|max:50',
            'notifications' => 'nullable|array',
        ]);

        $user = Auth::user();
        $user->setProfileSetting('theme', $validated['theme'] ?? 'system');
        $user->setProfileSetting('language', $validated['language'] ?? 'en');
        $user->setProfileSetting('timezone', $validated['timezone'] ?? 'UTC');
        $user->setProfileSetting('ai_provider_preference', $validated['ai_provider_preference'] ?? null);
        $user->setProfileSetting('notifications', $validated['notifications'] ?? []);

        Log::info('Setup preferences step completed', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'next_step' => route('setup.complete'),
        ]);
    }

    public function complete(CompleteUserSetup $completeSetup)
    {
        $user = Auth::user();
        $completeSetup($user);

        Log::info('User setup wizard completed', ['user_id' => $user->id]);

        return view('setup.complete');
    }

    public function finalize()
    {
        return response()->json([
            'success' => true,
            'redirect' => '/',
        ]);
    }
}
