<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'description' => $this->description,
            'images' => collect($this->image)->map(fn($img) => asset('storage/' . $img)),
            'category' => $this->category->name ?? null,
            'pricings' => ServicePricingResource::collection($this->whenLoaded('pricings')),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
