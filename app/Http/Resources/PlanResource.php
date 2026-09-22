<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'package' => $this->package,
            'day' => (int) $this->day,
            'features' => $this->features ?? [],
            'stripe_product_id' => $this->stripe_product_id,
            'stripe_price_id' => $this->stripe_price_id,
            'status' => (int) $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
