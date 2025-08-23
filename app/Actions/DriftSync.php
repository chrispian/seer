<?php

namespace App\Actions;

use App\Events\DriftSyncAvatarUpdated;
use Illuminate\Support\Facades\Log;

class DriftSync
{
    public function handle(?string $fragment): void
    {
        Log::debug('DriftSync::handle:start');

        $avatar = '/interface/avatars/default/error.png';

        if ($fragment) {
            $avatar = $this->determineAvatar($fragment);
        }

        try {
            Log::debug('DriftSync::about-to-broadcast', ['avatar' => $avatar]);
            broadcast(new DriftSyncAvatarUpdated($avatar));
            Log::debug('DriftSync::broadcast-complete');
        } catch (\Throwable $e) {
            Log::error('DriftSync broadcast failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    protected function determineAvatar(string $fragment): string
    {
        Log::debug('DriftSync::determineAvatar ' . $fragment);

        $fragment = strtolower($fragment);

        if (str_contains($fragment, 'error') || str_contains($fragment, 'fail')) {
            return '/interface/avatars/default/error.png';
        }

        if (str_contains($fragment, 'success') || str_contains($fragment, 'confirmed') || str_contains($fragment, 'great')) {
            return '/interface/avatars/default/excited-confirmation.png';
        }

        if (str_contains($fragment, 'wait') || str_contains($fragment, 'thinking') || str_contains($fragment, 'consider')) {
            return '/interface/avatars/default/consider.png';
        }

        if (str_contains($fragment, 'sleep') || str_contains($fragment, 'inactive')) {
            return '/interface/avatars/default/sleep.png';
        }

        if (str_contains($fragment, 'debug') || str_contains($fragment, 'diagnostic')) {
            return '/interface/avatars/default/debug.png';
        }

        return '/interface/avatars/default/debug.png';
    }
}
