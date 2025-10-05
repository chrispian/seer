<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingsService
{
    /**
     * Import settings from validated data
     */
    public function importSettings(User $user, array $data): array
    {
        // Validate the settings data structure
        $this->validateSettingsData($data);

        DB::beginTransaction();

        try {
            $changes = [];

            // Import profile data (limited to safe fields)
            if (isset($data['profile'])) {
                $profileChanges = $this->importProfileData($user, $data['profile']);
                if (! empty($profileChanges)) {
                    $changes['profile'] = $profileChanges;
                }
            }

            // Import preferences from settings object
            if (isset($data['settings'])) {
                $settingsChanges = $this->importSettingsData($user, $data['settings']);
                if (! empty($settingsChanges)) {
                    $changes = array_merge($changes, $settingsChanges);
                }
            }

            DB::commit();

            $this->logSettingsActivity('import', $user, [
                'imported_sections' => array_keys($changes),
                'changes_summary' => $this->summarizeChanges($changes),
                'import_source' => 'file_upload',
            ]);

            return $changes;

        } catch (\Exception $e) {
            DB::rollback();

            $this->logSettingsActivity('import_failed', $user, [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Reset specific settings sections to defaults
     */
    public function resetSettings(User $user, array $sections): array
    {
        $defaults = $this->getDefaultSettings();
        $reset = [];

        DB::beginTransaction();

        try {
            $currentSettings = $user->profile_settings ?? [];

            foreach ($sections as $section) {
                switch ($section) {
                    case 'preferences':
                        $this->resetPreferences($user, $defaults['preferences']);
                        $reset[] = 'preferences';
                        break;

                    case 'ai':
                        $this->resetAiSettings($user, $defaults['ai']);
                        $reset[] = 'ai';
                        break;

                    case 'notifications':
                        $this->resetNotifications($user, $defaults['notifications']);
                        $reset[] = 'notifications';
                        break;

                    case 'layout':
                        $this->resetLayout($user, $defaults['layout']);
                        $reset[] = 'layout';
                        break;
                }
            }

            DB::commit();

            $this->logSettingsActivity('reset', $user, [
                'reset_sections' => $reset,
                'sections_count' => count($reset),
            ]);

            return $reset;

        } catch (\Exception $e) {
            DB::rollback();

            $this->logSettingsActivity('reset_failed', $user, [
                'sections' => $sections,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);

            throw $e;
        }
    }

    /**
     * Validate settings data structure and values
     */
    public function validateSettingsData(array $data): void
    {
        $validator = Validator::make($data, [
            'version' => 'sometimes|string',
            'exported_at' => 'sometimes|string',
            'profile' => 'sometimes|array',
            'profile.display_name' => 'sometimes|nullable|string|max:255',
            'profile.use_gravatar' => 'sometimes|boolean',
            'settings' => 'sometimes|array',
            'settings.theme' => 'sometimes|string|in:light,dark,system',
            'settings.language' => 'sometimes|string|max:10',
            'settings.timezone' => 'sometimes|string|max:50',
            'settings.notifications' => 'sometimes|array',
            'settings.notifications.email' => 'sometimes|boolean',
            'settings.notifications.desktop' => 'sometimes|boolean',
            'settings.notifications.sound' => 'sometimes|boolean',
            'settings.layout' => 'sometimes|array',
            'settings.layout.sidebar_collapsed' => 'sometimes|boolean',
            'settings.layout.right_rail_width' => 'sometimes|integer|min:200|max:600',
            'settings.layout.compact_mode' => 'sometimes|boolean',
            'settings.ai' => 'sometimes|array',
            'settings.ai.default_provider' => 'sometimes|nullable|string|max:50',
            'settings.ai.default_model' => 'sometimes|nullable|string|max:100',
            'settings.ai.temperature' => 'sometimes|numeric|min:0|max:2',
            'settings.ai.max_tokens' => 'sometimes|integer|min:1|max:32000',
            'settings.ai.stream_responses' => 'sometimes|boolean',
            'settings.ai.auto_title' => 'sometimes|boolean',
            'settings.ai.context_length' => 'sometimes|integer|min:1000|max:128000',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid settings data: '.$validator->errors()->first());
        }
    }

    /**
     * Get default settings for reset operations
     */
    public function getDefaultSettings(): array
    {
        return [
            'preferences' => [
                'theme' => 'system',
                'language' => 'en',
                'timezone' => 'UTC',
            ],
            'notifications' => [
                'email' => true,
                'desktop' => true,
                'sound' => false,
            ],
            'layout' => [
                'sidebar_collapsed' => false,
                'right_rail_width' => 320,
                'compact_mode' => false,
            ],
            'ai' => [
                'default_provider' => '',
                'default_model' => '',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'stream_responses' => true,
                'auto_title' => true,
                'context_length' => 4000,
            ],
        ];
    }

    /**
     * Import profile data (limited to safe fields)
     */
    private function importProfileData(User $user, array $profileData): array
    {
        $changes = [];
        $updates = [];

        // Only allow specific safe profile fields
        if (isset($profileData['display_name'])) {
            $updates['display_name'] = $profileData['display_name'];
            $changes['display_name'] = $profileData['display_name'];
        }

        if (isset($profileData['use_gravatar'])) {
            $updates['use_gravatar'] = $profileData['use_gravatar'];
            $changes['use_gravatar'] = $profileData['use_gravatar'];
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        return $changes;
    }

    /**
     * Import settings data into profile_settings
     */
    private function importSettingsData(User $user, array $settingsData): array
    {
        $currentSettings = $user->profile_settings ?? [];
        $changes = [];

        // Merge with existing settings, preserving structure
        foreach ($settingsData as $key => $value) {
            if ($value !== null) {
                $currentSettings[$key] = $value;
                $changes[$key] = $value;
            }
        }

        $user->update(['profile_settings' => $currentSettings]);

        return $changes;
    }

    /**
     * Reset preferences to defaults
     */
    private function resetPreferences(User $user, array $defaults): void
    {
        $currentSettings = $user->profile_settings ?? [];

        // Reset theme, language, timezone
        $currentSettings['theme'] = $defaults['theme'];
        $currentSettings['language'] = $defaults['language'];
        $currentSettings['timezone'] = $defaults['timezone'];

        $user->update(['profile_settings' => $currentSettings]);
    }

    /**
     * Reset AI settings to defaults
     */
    private function resetAiSettings(User $user, array $defaults): void
    {
        $currentSettings = $user->profile_settings ?? [];
        $currentSettings['ai'] = $defaults;

        $user->update(['profile_settings' => $currentSettings]);
    }

    /**
     * Reset notification settings to defaults
     */
    private function resetNotifications(User $user, array $defaults): void
    {
        $currentSettings = $user->profile_settings ?? [];
        $currentSettings['notifications'] = $defaults;

        $user->update(['profile_settings' => $currentSettings]);
    }

    /**
     * Reset layout settings to defaults
     */
    private function resetLayout(User $user, array $defaults): void
    {
        $currentSettings = $user->profile_settings ?? [];
        $currentSettings['layout'] = $defaults;

        $user->update(['profile_settings' => $currentSettings]);
    }

    /**
     * Log settings-related activities for audit purposes
     */
    private function logSettingsActivity(string $action, User $user, array $details = []): void
    {
        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $action,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details,
        ];

        Log::info("Settings {$action} activity", $logData);

        // Also log to a dedicated settings audit channel if configured
        Log::channel('settings_audit')->info("Settings {$action}", $logData);
    }

    /**
     * Create a summary of changes for audit logging
     */
    private function summarizeChanges(array $changes): array
    {
        $summary = [];

        foreach ($changes as $section => $sectionChanges) {
            if (is_array($sectionChanges)) {
                $summary[$section] = count($sectionChanges).' settings updated';
            } else {
                $summary[$section] = 'updated';
            }
        }

        return $summary;
    }
}
