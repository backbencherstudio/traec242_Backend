<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public $message) {}

    // public function broadcastOn()
    // {
    //     return new Channel('test-channel'); // public channel
    // }

    public function broadcastOn()
    {
        return new PrivateChannel('test-channel');
    }

    public function broadcastAs(): string
    {
        return 'test-event';
    }
}
