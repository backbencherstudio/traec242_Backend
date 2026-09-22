<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'service_id' => $this->service_id,
            'service_title' => $this->service?->title,
            'reviewer_name' => $this->user ? trim("{$this->user->name} {$this->user->last_name}") : '',
            'rating' => (int) $this->rating,
            'review' => $this->review,
            'reply' => $this->reply,
            'has_replied' => $this->reply !== null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
