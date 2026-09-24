<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardRecentMessageResource extends JsonResource
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
            'image' => $otherUser?->image,
            'name' => $otherUser ? trim(($otherUser->name ?? '').' '.($otherUser->last_name ?? '')) : 'Unknown',
            'message' => $this->message,
        ];
    }
}
