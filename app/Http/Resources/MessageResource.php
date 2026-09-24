<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => [
                'id' => $this->sender?->id,
                'name' => $this->sender?->name,
                'image' => $this->sender?->image,
            ],
            'receiver' => [
                'id' => $this->receiver?->id,
                'name' => $this->receiver?->name,
                'image' => $this->receiver?->image,
            ],
            'message' => $this->message,
            'type' => $this->type,
            'read_at' => $this->read_at,
            'attachments' => MessageAttachmentResource::collection($this->attachments ?? []),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
