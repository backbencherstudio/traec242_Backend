<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServicePricingResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'type' => $this->service_type,
            'duration' => $this->duration,
            'price' => $this->price,
            'description' => $this->description,
            'features' => $this->features,
        ];
    }
}
