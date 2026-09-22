<?php

namespace App\Services;

use App\Models\IncludeOrder;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\ProviderStripe;
use App\Models\Service;
use App\Models\ServicePricing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class OrderService
{
    /**
     * Authorize whether a user can view or manage an order.
     */
    public function canAccessOrder(User $user, Order $order): bool
    {
        if ((int) $user->type === 1) {
            return true; // Admin
        }

        if ((int) $user->id === (int) $order->user_id) {
            return true; // Customer
        }

        return (int) $user->id === (int) $order->service?->user_id; // Service Provider
    }

    /**
     * Create an order and process initial Stripe payment intent.
     *
     * @return array{
     *     order: Order,
     *     payment: ProviderPayment,
     *     payment_intent?: PaymentIntent,
     *     requires_action?: bool,
     *     client_secret?: string
     * }
     */
    public function createOrder(User $user, array $validated): array
    {
        $service = Service::findOrFail($validated['service_id']);
        $pricing = ServicePricing::findOrFail($validated['service_pricing_id']);

        $providerStripe = ProviderStripe::where('user_id', $service->user_id)->first();
        if (! $providerStripe) {
            throw new \DomainException('Provider Stripe configuration not found.');
        }

        $includeOrderIds = $validated['include_order_ids'] ?? [];
        $includeOrderTotal = ! empty($includeOrderIds)
            ? (float) IncludeOrder::whereIn('id', $includeOrderIds)->sum('price')
            : 0.0;

        $finalAmount = (float) $pricing->price + $includeOrderTotal;
        $adminCommission = round($finalAmount * 0.20, 2);
        $providerAmount = round($finalAmount - $adminCommission, 2);

        return DB::transaction(function () use (
            $user,
            $validated,
            $service,
            $pricing,
            $includeOrderIds,
            $finalAmount,
            $adminCommission,
            $providerAmount,
            $providerStripe
        ) {
            $order = Order::create([
                'service_id' => $service->id,
                'service_pricing_id' => $pricing->id,
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'event_name' => $validated['event_name'],
                'guest_count' => $validated['guest_count'] ?? null,
                'event_duration' => $validated['event_duration'] ?? null,
                'event_description' => $validated['event_description'] ?? null,
                'event_start_date' => $validated['event_start_date'],
                'event_end_date' => $validated['event_end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'question_one' => $validated['question_one'] ?? null,
                'question_two' => $validated['question_two'] ?? null,
                'question_three' => $validated['question_three'] ?? null,
                'question_four' => $validated['question_four'] ?? null,
                'question_five' => $validated['question_five'] ?? null,
                'question_six' => $validated['question_six'] ?? null,
                'include_order_ids' => json_encode($includeOrderIds),
                'agree_terms' => $validated['agree_terms'],
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
            ]);

            $payment = ProviderPayment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'transaction_id' => null,
                'amount' => $finalAmount,
                'admin_commission_amount' => $adminCommission,
                'provider_amount' => $providerAmount,
                'currency' => 'USD',
                'payment_method' => 'stripe',
                'status' => 'pending',
            ]);

            Stripe::setApiKey($providerStripe->stripe_secret_key);

            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($finalAmount * 100),
                'currency' => 'usd',
                'payment_method' => $validated['payment_method_id'],
                'payment_method_types' => ['card'],
                'confirm' => true,
                'description' => $validated['event_name'],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'user_id' => (string) $user->id,
                ],
            ]);

            $payment->transaction_id = $paymentIntent->id;
            $payment->save();

            if ($paymentIntent->status === 'succeeded') {
                $order->update(['status' => 'confirmed']);
                $payment->update(['status' => 'successful']);

                return [
                    'order' => $order,
                    'payment' => $payment,
                    'payment_intent' => $paymentIntent,
                    'requires_action' => false,
                ];
            }

            if (in_array($paymentIntent->status, ['requires_action', 'requires_confirmation'], true)) {
                return [
                    'order' => $order,
                    'payment' => $payment,
                    'payment_intent' => $paymentIntent,
                    'requires_action' => true,
                    'client_secret' => $paymentIntent->client_secret,
                ];
            }

            $order->update(['status' => 'cancelled']);
            $payment->update(['status' => 'failed']);

            return [
                'order' => $order,
                'payment' => $payment,
                'payment_intent' => $paymentIntent,
                'requires_action' => false,
            ];
        });
    }

    /**
     * Update order status with provider ownership verification.
     */
    public function updateOrderStatus(User $user, int $orderId, string $status): Order
    {
        $order = Order::with('service')->findOrFail($orderId);

        // Only the assigned service provider or admin can update the status
        if ((int) $user->type !== 1 && (int) $order->service?->user_id !== (int) $user->id) {
            throw new \DomainException('You are not authorized to update this order status.');
        }

        $order->update(['status' => $status]);

        return $order;
    }
}
