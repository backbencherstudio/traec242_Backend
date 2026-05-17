<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Cashier\Subscription;

class ProviderSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->subscription('provider');

        return [
            'id' => $this->id,
            'name' => trim(($this->name ?? '').' '.($this->last_name ?? '')),
            'email' => $this->email,
            'image' => $this->image,
            'plan' => $this->plan?->title,
            'plan_package' => $this->plan?->package,
            'subscription_status' => $this->resolveStatus($subscription),
            'stripe_id' => $this->stripe_id,
            'starts_at' => $subscription?->created_at?->format('m/d/Y'),
            'ends_at' => $subscription?->ends_at?->format('m/d/Y'),
            'trial_ends_at' => $this->trial_ends_at?->format('m/d/Y'),
            'on_grace_period' => $subscription?->onGracePeriod() ?? false,
            'is_paused' => $subscription ? $this->isPaused($subscription) : false,
            'is_canceled' => $subscription?->canceled() ?? false,
            'subscription_history' => $this->subscriptions
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (Subscription $s) => $this->formatSubscription($s)),
        ];
    }

    private function isPaused(Subscription $subscription): bool
    {
        return $subscription->stripe_status === 'paused';
    }

    private function resolveStatus(?Subscription $subscription): string
    {
        if (! $subscription) {
            return 'none';
        }

        if ($this->isPaused($subscription)) {
            return 'paused';
        }

        if ($subscription->onGracePeriod()) {
            return 'grace_period';
        }

        if ($subscription->canceled()) {
            return 'canceled';
        }

        if ($subscription->active()) {
            return 'active';
        }

        return $subscription->stripe_status ?? 'unknown';
    }

    private function formatSubscription(Subscription $subscription): array
    {
        return [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->stripe_id,
            'stripe_status' => $subscription->stripe_status,
            'quantity' => $subscription->quantity,
            'starts_at' => $subscription->created_at->format('m/d/Y'),
            'ends_at' => $subscription->ends_at?->format('m/d/Y'),
            'trial_ends_at' => $subscription->trial_ends_at?->format('m/d/Y'),
        ];
    }
}
