<?php

namespace App\Events;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;

        Log::info('MessageSent Event Payload', [
            'message' => $message->toArray()
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conversation.' . $this->message->conversation_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
