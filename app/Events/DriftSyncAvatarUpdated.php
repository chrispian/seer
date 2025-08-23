<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;

class DriftSyncAvatarUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $avatarPath;

    public function __construct(string $avatarPath)
    {
        Log::debug('DriftSyncAvatarUpdated::construct');
        $this->avatarPath = $avatarPath;
    }

    public function broadcastOn()
    {
        Log::debug('DriftSyncAvatarUpdated:broadcastOn');
        return new Channel('lens.chat'); // Or whatever your main Lens channel is
    }

    public function broadcastAs()
    {
        return 'drift-avatar-change'; // Match the browser listener you already have
    }
}
