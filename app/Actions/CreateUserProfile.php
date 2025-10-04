<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateUserProfile
{
    public function __invoke(User $user, array $profileData): User
    {
        Log::debug('CreateUserProfile::invoke()', [
            'user_id' => $user->id,
            'profile_data_keys' => array_keys($profileData)
        ]);

        // Validate profile data
        $validator = Validator::make($profileData, [
            'display_name' => 'nullable|string|max:255',
            'use_gravatar' => 'boolean',
            'profile_settings' => 'nullable|array',
            'profile_settings.theme' => 'nullable|string|in:light,dark,system',
            'profile_settings.language' => 'nullable|string|max:10',
            'profile_settings.timezone' => 'nullable|string|max:50',
            'profile_settings.ai_provider_preference' => 'nullable|string|max:50',
            'profile_settings.notifications' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Update user with profile data
        $updateData = array_filter([
            'display_name' => $validated['display_name'] ?? null,
            'use_gravatar' => $validated['use_gravatar'] ?? true,
            'profile_settings' => $validated['profile_settings'] ?? [],
        ]);

        $user->update($updateData);

        Log::debug('User profile created/updated', [
            'user_id' => $user->id,
            'display_name' => $user->display_name,
            'use_gravatar' => $user->use_gravatar,
        ]);

        return $user->fresh();
    }
}