<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminSubscriptionResource;
use App\Http\Resources\ProviderSubscriptionResource;
use App\Models\User;
use App\Services\SubscriptionManagementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('admin-subscription-management', weight: 4)]
class SubscriptionManagementController extends Controller
{
    public function __construct(
        protected SubscriptionManagementService $subscriptionService
    ) {}

    /**
     * List all providers with their subscription details.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 10), 100);

        $providers = $this->subscriptionService->getProviders(
            $request->search,
            $request->status,
            $perPage
        );

        return $this->sendResponse(ProviderSubscriptionResource::collection($providers));
    }

    /**
     * Show subscription details for a single provider.
     */
    public function show(int $providerId): JsonResponse
    {
        $provider = $this->subscriptionService->findProvider($providerId);

        if (! $provider instanceof User) {
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

        $result = $this->subscriptionService->cancelSubscription($provider);
        if (! $result['success']) {
            return $this->sendError($result['message'], [], $result['code']);
        }

        return $this->sendResponse(
            new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
            $result['message']
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

        $result = $this->subscriptionService->cancelSubscriptionNow($provider);
        if (! $result['success']) {
            return $this->sendError($result['message'], [], $result['code']);
        }

        return $this->sendResponse(
            new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
            $result['message']
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

        $result = $this->subscriptionService->pauseSubscription($provider);
        if (! $result['success']) {
            return $this->sendError($result['message'], [], $result['code']);
        }

        return $this->sendResponse(
            new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
            $result['message']
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

        $result = $this->subscriptionService->resumeSubscription($provider);
        if (! $result['success']) {
            return $this->sendError($result['message'], [], $result['code']);
        }

        return $this->sendResponse(
            new ProviderSubscriptionResource($provider->fresh(['subscriptions', 'plan'])),
            $result['message']
        );
    }

    /**
     * List all Cashier subscriptions across the platform with provider info.
     */
    public function allSubscriptions(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getAllSubscriptions($request->status);

        return $this->sendResponse(AdminSubscriptionResource::collection($subscriptions));
    }

    private function findProvider(int $id): User|JsonResponse
    {
        $provider = $this->subscriptionService->findProvider($id);

        if (! $provider instanceof User) {
            return $this->sendError('Provider not found', [], 404);
        }

        return $provider;
    }
}
