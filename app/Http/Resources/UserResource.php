<?php

namespace App\Http\Resources;

use App\Models\Category;
use Carbon\Carbon;
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
            'type' => match ($this->type) {
                0 => 'user',
                1 => 'admin',
                2 => 'provider',
                default => 'user',
            },
            'status' => $this->status,
            'provider_status' => $this->provider_status,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'bio' => $this->bio,
            'languages' => $this->languages,
            'category_id' => $this->category_id,
            'categories' => $this->type === 2
                ? Category::whereIn('id', $this->category_id ?? [])->get(['id', 'name', 'image'])
                : null,
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
                'current_period_start' => null,
                'current_period_end' => null,
                'cancel_at_period_end' => false,
            ];
        }

        $subscriptionCreatedAt = $subscription->created_at;
        $currentPeriodStart = $subscription->start_date
            ? Carbon::createFromTimestamp($subscription->start_date)
            : $subscriptionCreatedAt;

        $currentPeriodEnd = $subscription->billing_cycle_anchor
            ? Carbon::createFromTimestamp($subscription->billing_cycle_anchor)
            : $currentPeriodStart->copy()->addMonth();

        if ($subscription->stripe_status === 'active' && $subscription->canceled()) {
            $currentPeriodEnd = $subscription->ends_at;
        }

        return [
            'status' => $this->mapSubscriptionStatus($subscription),
            'stripe_status' => $subscription->stripe_status,
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
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
