<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

class SubscriptionManagementService
{
    /**
     * Get paginated providers with their subscriptions and plans.
     */
    public function getProviders(?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::where('type', '2')
            ->with(['subscriptions', 'plan']);

        if ($search) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $this->applyStatusFilter($query, $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a provider by ID with their subscriptions and plan.
     */
    public function findProvider(int $providerId): ?User
    {
        return User::where('type', '2')
            ->with(['subscriptions', 'plan'])
            ->find($providerId);
    }

    /**
     * Cancel a provider's subscription at the end of the billing period.
     *
     * @return array{success: bool, message: string, code: int}
     */
    public function cancelSubscription(User $provider): array
    {
        $subscription = $provider->subscription('provider');

        if (! $subscription instanceof Subscription || ! $subscription->valid()) {
            return ['success' => false, 'message' => 'No active subscription found', 'code' => 422];
        }

        if ($subscription->canceled()) {
            return ['success' => false, 'message' => 'Subscription is already canceled', 'code' => 422];
        }

        $subscription->cancel();

        return [
            'success' => true,
            'message' => 'Subscription will be canceled at the end of the current billing period',
            'code' => 200,
        ];
    }

    /**
     * Cancel a provider's subscription immediately.
     *
     * @return array{success: bool, message: string, code: int}
     */
    public function cancelSubscriptionNow(User $provider): array
    {
        $subscription = $provider->subscription('provider');

        if (! $subscription instanceof Subscription || ! $subscription->valid()) {
            return ['success' => false, 'message' => 'No active subscription found', 'code' => 422];
        }

        $subscription->cancelNow();

        return [
            'success' => true,
            'message' => 'Subscription canceled immediately',
            'code' => 200,
        ];
    }

    /**
     * Pause a provider's subscription.
     *
     * @return array{success: bool, message: string, code: int}
     */
    public function pauseSubscription(User $provider): array
    {
        $subscription = $provider->subscription('provider');

        if (! $subscription instanceof Subscription || ! $subscription->active()) {
            return ['success' => false, 'message' => 'No active subscription to pause', 'code' => 422];
        }

        if ($this->isPaused($subscription)) {
            return ['success' => false, 'message' => 'Subscription is already paused', 'code' => 422];
        }

        $subscription->updateStripeSubscription([
            'pause_collection' => ['behavior' => 'void'],
        ]);

        $subscription->forceFill(['stripe_status' => 'paused'])->save();

        return [
            'success' => true,
            'message' => 'Subscription paused successfully',
            'code' => 200,
        ];
    }

    /**
     * Resume a paused or canceled (within grace period) subscription.
     *
     * @return array{success: bool, message: string, code: int}
     */
    public function resumeSubscription(User $provider): array
    {
        $subscription = $provider->subscription('provider');

        if (! $subscription instanceof Subscription) {
            return ['success' => false, 'message' => 'No subscription found', 'code' => 422];
        }

        if ($this->isPaused($subscription)) {
            $subscription->updateStripeSubscription([
                'pause_collection' => '',
            ]);

            $subscription->syncStripeStatus();

            return [
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'code' => 200,
            ];
        }

        if ($subscription->onGracePeriod()) {
            $subscription->resume();

            return [
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'code' => 200,
            ];
        }

        return ['success' => false, 'message' => 'Subscription cannot be resumed in its current state', 'code' => 422];
    }

    /**
     * List all Cashier subscriptions across the platform with provider info.
     *
     * @return Collection<int, Subscription>
     */
    public function getAllSubscriptions(?string $status = null): Collection
    {
        $query = Subscription::with('user.plan')
            ->whereHas('user', fn (Builder $q) => $q->where('type', '2'));

        if ($status) {
            $query->where('stripe_status', $status);
        }

        return $query->latest()->get();
    }

    /**
     * Apply subscription status filter to query.
     */
    protected function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'none' => $query->whereDoesntHave('subscriptions', fn (Builder $q) => $q->where('type', 'provider')),
            'paused' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'provider')->where('stripe_status', 'paused')),
            'active' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'provider')->where('stripe_status', 'active')),
            'grace_period' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'provider')->where('stripe_status', 'canceled')->where('ends_at', '>', now())),
            'canceled' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'provider')->where('stripe_status', 'canceled')->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '<=', now()))),
            default => null,
        };
    }

    /**
     * Check if a subscription is currently paused.
     */
    protected function isPaused(Subscription $subscription): bool
    {
        return $subscription->stripe_status === 'paused';
    }
}
