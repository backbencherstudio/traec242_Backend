<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;

class ProviderRegisterController extends Controller
{
    public function store(ProviderRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $plan = Plan::query()
            ->whereKey($validated['plan_id'])
            ->where('status', true)
            ->firstOrFail();

        $user = null;

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip_code' => $validated['zip_code'],
                'password' => $validated['password'],
                'category_id' => $validated['category_id'],
                'plan_id' => $plan->id,
                'type' => 2,
                'status' => 1,
                'provider_status' => false,
            ]);

            $user->newSubscription('provider', $plan->stripe_price_id)
                ->withMetadata([
                    'plan_id' => (string) $plan->id,
                    'user_id' => (string) $user->id,
                    'registration_flow' => 'provider_signup',
                ])
                ->create($validated['payment_method']);

            $token = auth('api')->login($user);
            $user->update(['jwt_token' => $token]);

            DB::commit();

            return $this->sendResponse([
                'user' => UserResource::make($user->fresh(['plan', 'subscriptions'])),
                'token' => $token,
            ], 'Provider registered and subscribed successfully', 201);
        } catch (IncompletePayment $exception) {
            DB::commit();

            $token = auth('api')->login($user);
            $user->update(['jwt_token' => $token]);

            return $this->sendError('Additional payment confirmation is required.', [
                'user' => UserResource::make($user->fresh(['plan', 'subscriptions'])),
                'token' => $token,
                'payment_intent_client_secret' => $exception->payment->clientSecret(),
                'payment_status' => $exception->payment->status,
            ], 202);
        } catch (ApiErrorException $exception) {
            DB::rollBack();

            return $this->sendError('Provider registration payment failed.', [
                'error' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return $this->sendError('Registration failed', [
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function subscriptionConfig(): JsonResponse
    {
        $plans = Plan::query()
            ->where('status', true)
            ->where('package', 'monthly')
            ->whereNotNull('stripe_price_id')
            ->get(['id', 'name', 'title', 'price', 'currency', 'package', 'features']);

        return $this->sendResponse([
            'stripe_public_key' => config('services.stripe.key'),
            'plans' => $plans,
        ]);
    }
}
