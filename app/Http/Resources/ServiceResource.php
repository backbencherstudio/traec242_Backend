<?php

namespace App\Http\Resources;

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
            'image_url' => $this->user->image ? asset($this->user->image) : null,
            'provider_name' => trim(($this->user->name ?? '').' '.($this->user->last_name ?? '')),
            'description' => $this->description,
            'images' => collect($this->image)->map(fn ($img) => asset('storage/'.$img)),
            'category' => $this->category->name ?? null,
            'pricings' => ServicePricingResource::collection($this->whenLoaded('pricings')),
            'faqs' => ServiceFaqResource::collection($this->whenLoaded('faqs')),
            'reviews' => $this->whenLoaded('reviews', function () {
                if ($this->reviews->isEmpty()) {
                    return null;
                }

                return $this->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'reviewer_name' => trim(($review->user->name ?? '').' '.($review->user->last_name ?? '')),
                        'rating' => $review->rating,
                        'review' => $review->review,
                        'reply' => $review->reply,
                        'has_replied' => $review->reply !== null,
                        'created_at' => $review->created_at,
                    ];
                });
            }),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
