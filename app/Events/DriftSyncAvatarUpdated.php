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

    // 👇 ensure it uses Redis + the broadcasts queue
    public $connection = 'redis';
    public $queue = 'broadcasts';

    public function __construct(string $avatarPath)
    {
        \Log::debug('DriftSyncAvatarUpdated::construct');
        $this->avatarPath = $avatarPath;
    }

    public function broadcastOn() {
        return new \Illuminate\Broadcasting\Channel('lens.chat');
    }
    public function broadcastAs() {
        return 'drift-avatar-change';
    }

}
