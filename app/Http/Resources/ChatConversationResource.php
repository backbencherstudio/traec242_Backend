<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ChatConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = (int) $request->user()?->id;
        $otherUser = (int) $this->sender_id === $userId
            ? $this->receiver
            : $this->sender;

        return [
            'conversation_id' => $this->conversation_id,
            'image' => $otherUser?->image,
            'name' => $otherUser ? trim(($otherUser->name ?? '').' '.($otherUser->last_name ?? '')) : 'Unknown',
            'time' => $this->created_at?->format('h:i A'),
            'last_message' => Str::limit((string) $this->message, 50),
            'unread_count' => (int) ($this->unread_count ?? 0),
        ];
    }
}
