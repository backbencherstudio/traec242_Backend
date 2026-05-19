<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderSubscriptionResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        $perPage = min($request->integer('per_page', 10), 100);

        $query = User::where('type', '2')
            ->with(['subscriptions', 'plan']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($status) {
            $this->applyStatusFilter($query, $status);
        }

        $providers = $query->paginate($perPage);

        return $this->sendResponse(ProviderSubscriptionResource::collection($providers));
    }

    /**
     * Show subscription details for a single provider.
     */
    public function show(int $providerId): JsonResponse
    {
        $provider = User::where('type', '2')->with(['subscriptions', 'plan'])->find($providerId);

        if (! $provider) {
            return $this->sendError('Provider not found', [], 404);
        }

        return $this->sendResponse(new ProviderSubscriptionResource($provider));
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
            return $this->sendError('No active subscription found', [], 422);
        }

        if ($subscription->canceled()) {
            return $this->sendError('Subscription is already canceled', [], 422);
        }

        $subscription->cancel();

        return $this->sendResponse(
            new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
            'Subscription will be canceled at the end of the current billing period'
        );
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
            return $this->sendError('No active subscription found', [], 422);
        }

        $subscription->cancelNow();

        return $this->sendResponse(
            new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
            'Subscription canceled immediately'
        );
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
            return $this->sendError('No active subscription to pause', [], 422);
        }

        if ($this->isPaused($subscription)) {
            return $this->sendError('Subscription is already paused', [], 422);
        }

        $subscription->updateStripeSubscription([
            'pause_collection' => ['behavior' => 'void'],
        ]);

        $subscription->forceFill(['stripe_status' => 'paused'])->save();

        return $this->sendResponse(
            new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
            'Subscription paused successfully'
        );
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
            return $this->sendError('No subscription found', [], 422);
        }

        if ($this->isPaused($subscription)) {
            $subscription->updateStripeSubscription([
                'pause_collection' => '',
            ]);

            $subscription->syncStripeStatus();

            return $this->sendResponse(
                new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
                'Subscription resumed successfully'
            );
        }

        if ($subscription->onGracePeriod()) {
            $subscription->resume();

            return $this->sendResponse(
                new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
                'Subscription resumed successfully'
            );
        }

        return $this->sendError('Subscription cannot be resumed in its current state', [], 422);
    }

    /**
     * List all Cashier subscriptions across the platform with provider info.
     */
    public function allSubscriptions(Request $request): JsonResponse
    {
        $status = $request->status;

        $query = Subscription::with('user.plan')
            ->whereHas('user', fn($q) => $q->where('type', '2'));

        if ($status) {
            $query->where('stripe_status', $status);
        }

        $subscriptions = $query->latest()->get();

        $data = $subscriptions->map(fn(Subscription $sub) => $this->formatSubscription($sub));

        return $this->sendResponse($data);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'none' => $query->whereDoesntHave('subscriptions', fn($q) => $q->where('name', 'provider')),
            'paused' => $query->whereHas('subscriptions', fn($q) => $q->where('name', 'provider')->where('stripe_status', 'paused')),
            'active' => $query->whereHas('subscriptions', fn($q) => $q->where('name', 'provider')->where('stripe_status', 'active')),
            'grace_period' => $query->whereHas('subscriptions', fn($q) => $q->where('name', 'provider')->where('stripe_status', 'canceled')->where('ends_at', '>', now())),
            'canceled' => $query->whereHas('subscriptions', fn($q) => $q->where('name', 'provider')->where('stripe_status', 'canceled')->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '<=', now()))),
            default => null,
        };
    }

    private function isPaused(Subscription $subscription): bool
    {
        return $subscription->stripe_status === 'paused';
    }

    private function findProvider(int $id): User|JsonResponse
    {
        $provider = User::where('type', '2')->find($id);

        if (! $provider) {
            return $this->sendError('Provider not found', [], 404);
        }

        return $provider;
    }

    private function formatSubscription(Subscription $subscription): array
    {
        $provider = $subscription->user;

        return [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->stripe_id,
            'stripe_status' => $subscription->stripe_status,
            'provider_id' => $provider?->id,
            'provider_name' => $provider ? trim(($provider->name ?? '') . ' ' . ($provider->last_name ?? '')) : null,
            'provider_email' => $provider?->email,
            'plan' => $provider?->plan?->title,
            'quantity' => $subscription->quantity,
            'starts_at' => $subscription->created_at->format('m/d/Y'),
            'ends_at' => $subscription->ends_at?->format('m/d/Y'),
            'trial_ends_at' => $subscription->trial_ends_at?->format('m/d/Y'),
        ];
    }
}
