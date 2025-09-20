<?php

namespace App\Actions;

use App\Events\DriftSyncAvatarUpdated;
use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class DriftSync
{
    public function handle(Fragment $fragment, $next)
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

        // Continue pipeline
        return $next($fragment);
    }

    protected function determineAvatar(Fragment $fragment): string
    {
        Log::debug('DriftSync::determineAvatar', $fragment->toArray());

        $message = strtolower($fragment->message ?? '');

        if (str_contains($message, 'error') || str_contains($message, 'fail')) {
            return '/interface/avatars/default/error.png';
        }

        if (str_contains($message, 'success') || str_contains($message, 'confirmed') || str_contains($message, 'great')) {
            return '/interface/avatars/default/excited-confirmation.png';
        }

        if (str_contains($message, 'wait') || str_contains($message, 'thinking') || str_contains($message, 'consider')) {
            return '/interface/avatars/default/consider.png';
        }

        if (str_contains($message, 'sleep') || str_contains($message, 'inactive')) {
            return '/interface/avatars/default/sleep.png';
        }

        if (str_contains($message, 'debug') || str_contains($message, 'diagnostic')) {
            return '/interface/avatars/default/debug.png';
        }

        return '/interface/avatars/default/debug.png';
    }
}
