<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\ProviderStripe;
use App\Models\Service;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

#[Group('user-order', weight: 2)]
class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display a listing of orders for current customer or provider.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $query = Order::with(['service.user', 'pricing', 'providerPayments', 'user', 'review']);

        if ((int) $user->type === 0) {
            $query->where('user_id', $user->id);
        } elseif ((int) $user->type === 2) {
            $query->whereHas('service', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            });
        }

        $orders = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Display the specified order details with ownership verification.
     */
    public function show($id): JsonResponse
    {
        $order = Order::with(['service.user', 'pricing', 'providerPayments', 'user', 'review'])
            ->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $user = auth()->user();
        if (! $this->orderService->canAccessOrder($user, $order)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this order',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderDetailResource($order),
        ]);
    }

    /**
     * Place a new order and create payment intent.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            $result = $this->orderService->createOrder($user, $request->validated());

            if (! empty($result['requires_action'])) {
                return response()->json([
                    'status' => true,
                    'requires_action' => true,
                    'message' => 'Additional authentication required to complete the payment',
                    'order_id' => $result['order']->id,
                    'payment_intent_client_secret' => $result['client_secret'],
                    'payment_status' => $result['payment_intent']->status,
                ], 200);
            }

            if ($result['order']->status === 'confirmed') {
                return response()->json([
                    'status' => true,
                    'message' => 'Payment successful',
                    'order_id' => $result['order']->id,
                    'payment_status' => $result['payment_intent']?->status,
                ], 201);
            }

            return response()->json([
                'status' => false,
                'message' => 'Payment could not be processed',
                'order_id' => $result['order']->id,
                'payment_status' => $result['payment_intent']?->status,
            ], 402);
        } catch (\DomainException) {
            return response()->json([
                'status' => false,
                'error' => 'Stripe key not found',
            ], 404);
        } catch (CardException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 402);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Stripe payment success callback.
     */
    #[Group('public-order', weight: 1)]
    public function success(Request $request, $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return response()->json([
                'status' => false,
                'message' => 'Session ID missing',
            ], 400);
        }

        if ($order->status === 'confirmed') {
            return response()->json([
                'status' => true,
                'message' => 'Order already confirmed',
                'order' => new OrderResource($order),
            ], 200);
        }

        $service = Service::findOrFail($order->service_id);
        $providerStripe = ProviderStripe::where('user_id', $service->user_id)->first();

        if (! $providerStripe) {
            return response()->json([
                'status' => false,
                'message' => 'Stripe key not found',
            ], 404);
        }

        Stripe::setApiKey($providerStripe->stripe_secret_key);

        DB::beginTransaction();
        try {
            $session = Session::retrieve($sessionId);
            if (! $session || ! $session->payment_intent) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Stripe session',
                ], 400);
            }

            $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
            if (! $paymentIntent || ! isset($paymentIntent->status)) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid payment intent',
                ], 400);
            }

            $payment = ProviderPayment::where('order_id', $order->id)->first();
            if (! $payment) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Payment record not found',
                ], 404);
            }

            if ($paymentIntent->status === 'succeeded') {
                $order->update(['status' => 'confirmed']);
                $payment->update([
                    'transaction_id' => $paymentIntent->id,
                    'status' => 'successful',
                ]);
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Payment successful',
                    'order' => new OrderResource($order),
                    'payment' => $payment,
                    'transaction_id' => $paymentIntent->id,
                ], 200);
            }

            $order->update(['status' => 'cancelled']);
            $payment->update(['status' => 'failed']);
            DB::commit();

            return response()->json([
                'status' => false,
                'message' => 'Payment failed',
                'order' => new OrderResource($order),
                'payment' => $payment,
            ], 400);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Payment processing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Stripe cancel callback.
     */
    #[Group('public-order', weight: 1)]
    public function cancel(Request $request, $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => 'cancelled']);

        $payment = ProviderPayment::where('order_id', $order->id)->first();
        if ($payment) {
            $payment->update(['status' => 'failed']);
        }

        return response()->json([
            'status' => false,
            'message' => 'Order was cancelled',
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * Download order invoice PDF.
     */
    #[Group('public-order', weight: 1)]
    public function generateInvoice($orderId): Response
    {
        $order = Order::with(['service', 'pricing', 'user'])->findOrFail($orderId);

        $pricing = $order->pricing;
        $payment = ProviderPayment::where('order_id', $order->id)->first();

        $data = [
            'order' => $order,
            'user' => $order->user,
            'service' => $order->service,
            'pricing' => $pricing,
            'payment' => $payment,
            'total_amount' => $payment?->amount ?? 0,
            'transaction_id' => $payment?->transaction_id,
            'payment_method' => $payment?->payment_method ?? 'stripe',
            'payment_status' => $payment?->status ?? 'pending',
            'date' => now()->format('Y-m-d'),
            'address' => $order->address,
            'city' => $order->city,
            'state' => $order->state,
            'zip_code' => $order->zip_code,
        ];

        $pdf = Pdf::loadView('invoices.order_invoice', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('invoice_'.$orderId.'.pdf');
    }

    /**
     * Update order status (with provider authorization).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, $id): JsonResponse
    {
        $user = auth()->user();

        try {
            $order = $this->orderService->updateOrderStatus($user, (int) $id, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
