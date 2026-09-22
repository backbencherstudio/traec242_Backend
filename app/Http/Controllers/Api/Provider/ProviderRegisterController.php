<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Plan;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;

class ProviderRegisterController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function store(ProviderRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $plan = Plan::query()
            ->whereKey($validated['plan_id'])
            ->where('status', true)
            ->firstOrFail();

        if (! $request->filled('otp')) {
            $secondsUntilNextAttempt = $this->otpService->getSecondsUntilNextAttempt($validated['email']);
            if ($secondsUntilNextAttempt > 0) {
                return $this->sendError(
                    "Please wait {$secondsUntilNextAttempt} seconds before requesting another OTP.",
                    [],
                    429
                );
            }

            $otpSent = $this->otpService->sendRegistrationOtp($validated['email']);

            if (! $otpSent) {
                return $this->sendError('Failed to send OTP', [], 500);
            }

            return $this->sendResponse([
                'email' => $validated['email'],
                'requires_verification' => true,
            ], 'OTP sent to your email. Submit the registration again with the OTP to complete provider signup.');
        }

        if (! $this->otpService->verifyRegistrationOtp($validated['email'], $validated['otp'])) {
            return $this->sendError('Invalid or expired OTP', [], 400);
        }

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
                'is_verified' => true,
            ]);

            $user->newSubscription('provider', $plan->stripe_price_id)
                ->withMetadata([
                    'plan_id' => (string) $plan->id,
                    'user_id' => (string) $user->id,
                    'registration_flow' => 'provider_signup',
                ])
                ->create($validated['payment_method']);

            DB::commit();

            return $this->registeredProviderResponse($user);
        } catch (IncompletePayment $exception) {
            DB::commit();

            return $this->registeredProviderResponse(
                $user,
                $exception->payment->clientSecret(),
                $exception->payment->status
            );
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

    protected function registeredProviderResponse(
        ?User $user,
        ?string $paymentIntentClientSecret = null,
        ?string $paymentStatus = null
    ): JsonResponse {
        if (! $user) {
            return $this->sendError('Registration failed', [], 500);
        }

        $token = auth('api')->login($user);
        $user->update(['jwt_token' => $token]);

        return $this->sendResponse(array_filter([
            'user' => UserResource::make($user->fresh(['plan', 'subscriptions'])),
            'token' => $token,
            'payment_intent_client_secret' => $paymentIntentClientSecret,
            'payment_status' => $paymentStatus,
        ], fn ($value) => ! is_null($value)), 'Provider registered successfully', 201);
    }
}
