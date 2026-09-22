<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StripeSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stripe_mode' => $this->stripe_mode,
            'stripe_public_key' => $this->stripe_public_key,
            'stripe_secret_key' => $this->stripe_secret_key,
            'stripe_webhook_secret' => $this->stripe_webhook_secret,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
