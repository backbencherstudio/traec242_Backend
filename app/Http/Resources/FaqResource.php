<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'faq_category_id' => $this->faq_category_id,
            'question' => $this->question,
            'answer' => $this->answer,
            'order_number' => (int) $this->order_number,
            'status' => (bool) $this->status,
            'faq_category' => new FaqCategoryResource($this->whenLoaded('faq_category')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
