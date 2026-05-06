<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

class SubscriptionManagementController extends Controller
{
    /**
     * List all providers with their subscription details.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->search;
        $status = $request->status;

        $query = User::where('type', '2')
            ->with(['subscriptions', 'plan']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $providers = $query->get();

        $data = $providers->map(fn (User $provider) => $this->formatProviderSubscription($provider));

        if ($status) {
            $data = $data->filter(fn ($item) => $item['subscription_status'] === $status)->values();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Show subscription details for a single provider.
     */
    public function show(int $providerId): JsonResponse
    {
        $provider = User::where('type', '2')->with(['subscriptions', 'plan'])->find($providerId);

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'Provider not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatProviderSubscription($provider, detailed: true),
        ]);
    }

    /**
     * Cancel a provider's subscription at the end of the billing period.
     */
    public function cancel(int $providerId): JsonResponse
    {
        $provider = $this->findProvider($providerId);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $subscription = $provider->subscription('provider');

        if (! $subscription || ! $subscription->valid()) {
            return response()->json(['success' => false, 'message' => 'No active subscription found'], 422);
        }

        if ($subscription->canceled()) {
            return response()->json(['success' => false, 'message' => 'Subscription is already canceled'], 422);
        }

        $subscription->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Subscription will be canceled at the end of the current billing period',
            'data' => $this->formatProviderSubscription($provider->fresh(['subscriptions', 'plan'])),
        ]);
    }

    /**
     * Cancel a provider's subscription immediately.
     */
    public function cancelNow(int $providerId): JsonResponse
    {
        $provider = $this->findProvider($providerId);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $subscription = $provider->subscription('provider');

        if (! $subscription || ! $subscription->valid()) {
            return response()->json(['success' => false, 'message' => 'No active subscription found'], 422);
        }

        $subscription->cancelNow();

        return response()->json([
            'success' => true,
            'message' => 'Subscription canceled immediately',
            'data' => $this->formatProviderSubscription($provider->fresh(['subscriptions', 'plan'])),
        ]);
    }

    /**
     * Pause a provider's subscription via Stripe pause_collection.
     */
    public function pause(int $providerId): JsonResponse
    {
        $provider = $this->findProvider($providerId);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $subscription = $provider->subscription('provider');

        if (! $subscription || ! $subscription->active()) {
            return response()->json(['success' => false, 'message' => 'No active subscription to pause'], 422);
        }

        if ($this->isPaused($subscription)) {
            return response()->json(['success' => false, 'message' => 'Subscription is already paused'], 422);
        }

        $subscription->updateStripeSubscription([
            'pause_collection' => ['behavior' => 'void'],
        ]);

        $subscription->forceFill(['stripe_status' => 'paused'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Subscription paused successfully',
            'data' => $this->formatProviderSubscription($provider->fresh(['subscriptions', 'plan'])),
        ]);
    }

    /**
     * Resume a paused or canceled (within grace period) subscription.
     */
    public function resume(int $providerId): JsonResponse
    {
        $provider = $this->findProvider($providerId);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $subscription = $provider->subscription('provider');

        if (! $subscription) {
            return response()->json(['success' => false, 'message' => 'No subscription found'], 422);
        }

        if ($this->isPaused($subscription)) {
            $subscription->updateStripeSubscription([
                'pause_collection' => '',
            ]);

            $subscription->syncStripeStatus();

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'data' => $this->formatProviderSubscription($provider->fresh(['subscriptions', 'plan'])),
            ]);
        }

        if ($subscription->onGracePeriod()) {
            $subscription->resume();

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'data' => $this->formatProviderSubscription($provider->fresh(['subscriptions', 'plan'])),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Subscription cannot be resumed in its current state'], 422);
    }

    /**
     * List all Cashier subscriptions across the platform with provider info.
     */
    public function allSubscriptions(Request $request): JsonResponse
    {
        $status = $request->status;

        $query = Subscription::with('user.plan')
            ->whereHas('user', fn ($q) => $q->where('type', '2'));

        if ($status) {
            $query->where('stripe_status', $status);
        }

        $subscriptions = $query->latest()->get();

        $data = $subscriptions->map(fn (Subscription $sub) => $this->formatSubscription($sub));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function isPaused(Subscription $subscription): bool
    {
        return $subscription->stripe_status === 'paused';
    }

    private function findProvider(int $id): User|JsonResponse
    {
        $provider = User::where('type', '2')->find($id);

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'Provider not found'], 404);
        }

        return $provider;
    }

    private function formatProviderSubscription(User $provider, bool $detailed = false): array
    {
        $subscription = $provider->subscription('provider');

        $base = [
            'id' => $provider->id,
            'name' => trim(($provider->name ?? '').' '.($provider->last_name ?? '')),
            'email' => $provider->email,
            'image' => $provider->image,
            'plan' => $provider->plan?->title,
            'plan_package' => $provider->plan?->package,
            'subscription_status' => $this->resolveStatus($subscription),
            'stripe_id' => $provider->stripe_id,
            'starts_at' => $subscription?->created_at?->format('m/d/Y'),
            'ends_at' => $subscription?->ends_at?->format('m/d/Y'),
            'trial_ends_at' => $provider->trial_ends_at?->format('m/d/Y'),
            'on_grace_period' => $subscription?->onGracePeriod() ?? false,
            'is_paused' => $subscription ? $this->isPaused($subscription) : false,
            'is_canceled' => $subscription?->canceled() ?? false,
        ];

        if ($detailed) {
            $base['stripe_subscription_id'] = $subscription?->stripe_id;
            $base['stripe_status'] = $subscription?->stripe_status;
            $base['all_subscriptions'] = $provider->subscriptions->map(
                fn (Subscription $s) => $this->formatSubscription($s)
            );
        }

        return $base;
    }

    private function formatSubscription(Subscription $subscription): array
    {
        $provider = $subscription->user;

        return [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->stripe_id,
            'stripe_status' => $subscription->stripe_status,
            'provider_id' => $provider?->id,
            'provider_name' => $provider ? trim(($provider->name ?? '').' '.($provider->last_name ?? '')) : null,
            'provider_email' => $provider?->email,
            'plan' => $provider?->plan?->title,
            'quantity' => $subscription->quantity,
            'starts_at' => $subscription->created_at->format('m/d/Y'),
            'ends_at' => $subscription->ends_at?->format('m/d/Y'),
            'trial_ends_at' => $subscription->trial_ends_at?->format('m/d/Y'),
        ];
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
}
