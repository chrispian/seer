<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CompleteUserSetup
{
    public function __invoke(User $user): User
    {
        Log::debug('CompleteUserSetup::invoke()', [
            'user_id' => $user->id,
            'already_completed' => $user->hasCompletedSetup(),
        ]);

        // Mark setup as complete if not already done
        if (! $user->hasCompletedSetup()) {
            $user->markSetupComplete();

            Log::info('User setup marked as complete', [
                'user_id' => $user->id,
                'completed_at' => $user->profile_completed_at,
            ]);
        }

        return $user->fresh();
    }
}
