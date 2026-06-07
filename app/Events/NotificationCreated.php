<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotificationCreated implements ShouldBroadcast
{
    public $notification;

    public function __construct(array $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'notifications.' . $this->notification['user_id']
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
