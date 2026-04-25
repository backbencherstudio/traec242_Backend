<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Cashier\Subscription;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            'type' => $this->type,
            'status' => $this->status,
            'provider_status' => $this->provider_status,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'category_id' => $this->category_id,
            'plan_id' => $this->plan_id,
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan?->id,
                    'name' => $this->plan?->name,
                    'title' => $this->plan?->title,
                    'price' => $this->plan?->price,
                    'currency' => $this->plan?->currency,
                    'package' => $this->plan?->package,
                ];
            }),
            'provider_subscription' => $this->type === 2
                ? $this->providerSubscriptionPayload()
                : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function providerSubscriptionPayload(): array
    {
        /** @var Subscription|null $subscription */
        $subscription = $this->subscriptions->firstWhere('type', 'provider');

        if (! $subscription) {
            return [
                'status' => 'not_subscribed',
                'stripe_status' => null,
                'ends_at' => null,
            ];
        }

        return [
            'status' => $this->mapSubscriptionStatus($subscription),
            'stripe_status' => $subscription->stripe_status,
            'ends_at' => $subscription->ends_at,
        ];
    }

    protected function mapSubscriptionStatus(Subscription $subscription): string
    {
        if ($subscription->active()) {
            return 'subscribed';
        }

        if ($subscription->incomplete()) {
            return 'payment_action_required';
        }

        if ($subscription->pastDue()) {
            return 'payment_failed';
        }

        if ($subscription->ended() || $subscription->canceled()) {
            return 'expired';
        }

        return 'not_subscribed';
    }
}
