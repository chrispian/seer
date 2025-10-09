<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserProfile;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\File;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $profileSettings = $user->profile_settings ?? [];

        $readwiseSettings = $profileSettings['integrations']['readwise'] ?? [];
        $profileSettings['integrations']['readwise'] = array_merge([
            'token_present' => false,
            'sync_enabled' => false,
            'reader_sync_enabled' => false,
            'last_synced_at' => null,
        ], Arr::except($readwiseSettings, ['api_token', 'next_cursor']) ?? []);

        if (! empty($readwiseSettings['api_token'])) {
            $profileSettings['integrations']['readwise']['token_present'] = true;
        }

        $obsidianSettings = $profileSettings['integrations']['obsidian'] ?? [];
        $profileSettings['integrations']['obsidian'] = array_merge([
            'vault_path' => null,
            'sync_enabled' => false,
            'enrich_enabled' => false,
            'last_synced_at' => null,
            'file_count' => 0,
        ], $obsidianSettings);

        $hardcoverSettings = $profileSettings['integrations']['hardcover'] ?? [];
        $profileSettings['integrations']['hardcover'] = array_merge([
            'api_key_present' => false,
            'sync_enabled' => false,
            'last_synced_at' => null,
        ], Arr::except($hardcoverSettings, ['bearer_token']) ?? []);

        if (! empty($hardcoverSettings['bearer_token'])) {
            $profileSettings['integrations']['hardcover']['api_key_present'] = true;
        }

        return view('settings.index', [
            'user' => $user,
            'profile_settings' => $profileSettings,
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

    public function updateIntegrations(Request $request)
    {
        $validated = $request->validate([
            'readwise_api_token' => 'nullable|string|max:255',
            'readwise_sync_enabled' => 'nullable|boolean',
            'obsidian_vault_path' => 'nullable|string|max:500',
            'obsidian_sync_enabled' => 'nullable|boolean',
            'obsidian_enrich_enabled' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $currentSettings = $user->profile_settings ?? [];

        $integrations = $currentSettings['integrations'] ?? [];
        $readwise = $integrations['readwise'] ?? [
            'api_token' => null,
            'sync_enabled' => false,
            'last_synced_at' => null,
            'next_cursor' => null,
        ];

        if (array_key_exists('readwise_api_token', $validated)) {
            $token = $validated['readwise_api_token'];
            $readwise['api_token'] = $token ? Crypt::encryptString($token) : null;
        }

        if (array_key_exists('readwise_sync_enabled', $validated)) {
            $readwise['sync_enabled'] = (bool) $validated['readwise_sync_enabled'];
        }

        if (array_key_exists('readwise_reader_sync_enabled', $validated)) {
            $readwise['reader_sync_enabled'] = (bool) $validated['readwise_reader_sync_enabled'];
        }

        $integrations['readwise'] = $readwise;

        $obsidian = $integrations['obsidian'] ?? [
            'vault_path' => null,
            'sync_enabled' => false,
            'enrich_enabled' => false,
            'last_synced_at' => null,
            'file_count' => 0,
        ];

        if (array_key_exists('obsidian_vault_path', $validated)) {
            $path = $validated['obsidian_vault_path'];

            if ($path && ! is_dir($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid vault path: directory does not exist',
                ], 422);
            }

            if ($path && ! is_readable($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid vault path: directory is not readable',
                ], 422);
            }

            $obsidian['vault_path'] = $path;
        }

        if (array_key_exists('obsidian_sync_enabled', $validated)) {
            $obsidian['sync_enabled'] = (bool) $validated['obsidian_sync_enabled'];
        }

        if (array_key_exists('obsidian_enrich_enabled', $validated)) {
            $obsidian['enrich_enabled'] = (bool) $validated['obsidian_enrich_enabled'];
        }

        $integrations['obsidian'] = $obsidian;

        $hardcover = $integrations['hardcover'] ?? [
            'bearer_token' => null,
            'sync_enabled' => false,
            'last_synced_at' => null,
        ];

        if (array_key_exists('hardcover_api_key', $validated)) {
            $token = $validated['hardcover_api_key'];
            $hardcover['bearer_token'] = $token ? Crypt::encryptString($token) : null;
        }

        if (array_key_exists('hardcover_sync_enabled', $validated)) {
            $hardcover['sync_enabled'] = (bool) $validated['hardcover_sync_enabled'];
        }

        $integrations['hardcover'] = $hardcover;
        $currentSettings['integrations'] = $integrations;

        $user->update(['profile_settings' => $currentSettings]);

        Log::info('Integrations settings updated', [
            'user_id' => $user->id,
            'integrations' => array_keys(array_filter([
                'readwise' => array_key_exists('readwise_api_token', $validated) || array_key_exists('readwise_sync_enabled', $validated) || array_key_exists('readwise_reader_sync_enabled', $validated),
                'obsidian' => array_key_exists('obsidian_vault_path', $validated) || array_key_exists('obsidian_sync_enabled', $validated),
                'hardcover' => array_key_exists('hardcover_api_key', $validated) || array_key_exists('hardcover_sync_enabled', $validated),
            ])),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Integration settings saved',
            'integrations' => [
                'readwise' => [
                    'token_present' => ! empty($readwise['api_token']),
                    'sync_enabled' => $readwise['sync_enabled'] ?? false,
                    'reader_sync_enabled' => $readwise['reader_sync_enabled'] ?? false,
                    'last_synced_at' => $readwise['last_synced_at'] ?? null,
                    'next_cursor' => $readwise['next_cursor'] ?? null,
                ],
                'obsidian' => [
                    'vault_path' => $obsidian['vault_path'] ?? null,
                    'sync_enabled' => $obsidian['sync_enabled'] ?? false,
                    'enrich_enabled' => $obsidian['enrich_enabled'] ?? false,
                    'last_synced_at' => $obsidian['last_synced_at'] ?? null,
                    'file_count' => $obsidian['file_count'] ?? 0,
                ],
                'hardcover' => [
                    'api_key_present' => ! empty($hardcover['bearer_token']),
                    'sync_enabled' => $hardcover['sync_enabled'] ?? false,
                    'last_synced_at' => $hardcover['last_synced_at'] ?? null,
                ],
            ],
        ]);
    }

    public function testObsidianPath(Request $request)
    {
        $validated = $request->validate([
            'vault_path' => 'required|string|max:500',
        ]);

        $path = $validated['vault_path'];

        if (! is_dir($path)) {
            return response()->json([
                'valid' => false,
                'error' => 'Path does not exist or is not a directory',
            ], 422);
        }

        if (! is_readable($path)) {
            return response()->json([
                'valid' => false,
                'error' => 'Directory is not readable',
            ], 422);
        }

        $markdownFiles = [];
        $fileCount = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'md') {
                    $fileCount++;
                    if (count($markdownFiles) < 3) {
                        $markdownFiles[] = str_replace($path.'/', '', $file->getPathname());
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'error' => 'Failed to scan directory: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'file_count' => $fileCount,
            'sample_files' => $markdownFiles,
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
