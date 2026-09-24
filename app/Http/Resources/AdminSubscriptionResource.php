<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $provider = $this->user;

        return [
            'subscription_id' => $this->id,
            'stripe_subscription_id' => $this->stripe_id,
            'stripe_status' => $this->stripe_status,
            'provider_id' => $provider?->id,
            'provider_name' => $provider ? trim(($provider->name ?? '').' '.($provider->last_name ?? '')) : null,
            'provider_email' => $provider?->email,
            'plan' => $provider?->plan?->title,
            'quantity' => $this->quantity,
            'starts_at' => $this->created_at?->format('m/d/Y'),
            'ends_at' => $this->ends_at?->format('m/d/Y'),
            'trial_ends_at' => $this->trial_ends_at?->format('m/d/Y'),
        ];
    }
}
