<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\IncludeOrder;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\ProviderStripe;
use App\Models\Service;
use App\Models\ServicePricing;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $user = auth()->user();

        $query = Order::with(['service', 'pricing', 'providerPayments', 'user']);

        if ($user->type == 0) {
            $query->where('user_id', $user->id);
        }

        if ($user->type == 2) {
            $query->whereHas('service', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $orders = $query->get()
            ->map(function ($order) {

                $dueIn = Carbon::parse($order->event_end_date)->diff(Carbon::now());
                $days = $dueIn->d;
                $hours = $dueIn->h;
                $minutes = $dueIn->i;

                return [
                    'order_id' => $order->id,
                    'service_image' => $order->service->image,
                    'event_name' => $order->event_name,
                    'order_by' => "{$order->user->name} {$order->user->last_name}",
                    'price' => '$'.number_format($order->providerPayments->amount),
                    'due_in' => "{$days}d {$hours}h {$minutes}m",
                    'status' => $order->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function show($id)
    {
        $order = Order::with(['service', 'pricing', 'providerPayments', 'user'])
            ->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $dueIn = Carbon::parse($order->event_end_date)->diff(Carbon::now());
        $days = $dueIn->d;
        $hours = $dueIn->h;
        $minutes = $dueIn->i;

        $orderDetails = [
            'id' => $order->id,
            'order_started' => [
                'order_by' => "{$order->user->name} {$order->user->last_name}",
                'event_name' => $order->event_name,
            ],

            'location & contact' => [
                'full_name' => $order->first_name.' '.$order->last_name,
                'email' => $order->email,
                'phone' => $order->phone,
                'address' => implode(', ', array_filter([
                    $order->address,
                    $order->city,
                    trim("{$order->state} {$order->zip_code}"),
                ])),
            ],

            'event_details' => [
                'event_type' => $order->service->title,
                'event_name' => $order->event_name,
                'duration' => $order->event_duration,
                'guests' => $order->guest_count,
                'description' => $order->event_description,
            ],

            'questionnaire' => [
                'party_theme' => $order->question_one,
                'music_preference' => $order->question_two,
                'must_play_songs' => $order->question_three,
                'dance_games' => $order->question_four,
                'entrance_style' => $order->question_five,
                'additional_notes' => $order->question_six,
            ],

            'order_details' => [
                'service_image' => $order->service->image_url,
                'event_name' => $order->event_name,
                'order_by' => "{$order->user->name} {$order->user->last_name}",
                'status' => $order->status,
                'order_number' => '#ORD'.str_pad($order->id, 5, '0', STR_PAD_LEFT),
                'end_date' => Carbon::parse($order->event_end_date)->format('d M, Y'),
                'amount_paid' => '$'.number_format($order->providerPayments->amount),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $orderDetails,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'service_pricing_id' => 'required|exists:service_pricings,id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'event_name' => 'required|string',
            'guest_count' => 'nullable|integer',
            'event_duration' => 'nullable|string',
            'event_description' => 'nullable|string',
            'event_start_date' => 'required|date|after_or_equal:today',
            'event_end_date' => 'required|date|after_or_equal:event_start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'question_one' => 'nullable|string',
            'question_two' => 'nullable|string',
            'question_three' => 'nullable|string',
            'question_four' => 'nullable|string',
            'question_five' => 'nullable|string',
            'question_six' => 'nullable|string',
            'include_order_ids' => 'nullable|array',
            'include_order_ids.*' => 'integer|exists:include_orders,id',
            'agree_terms' => 'required|boolean',
            'payment_method' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::create([
                'service_id' => $request->service_id,
                'service_pricing_id' => $request->service_pricing_id,
                'user_id' => auth()->id(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'event_name' => $request->event_name,
                'guest_count' => $request->guest_count,
                'event_duration' => $request->event_duration,
                'event_description' => $request->event_description,
                'event_start_date' => $request->event_start_date,
                'event_end_date' => $request->event_end_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'question_one' => $request->question_one,
                'question_two' => $request->question_two,
                'question_three' => $request->question_three,
                'question_four' => $request->question_four,
                'question_five' => $request->question_five,
                'question_six' => $request->question_six,
                'include_order_ids' => json_encode($request->include_order_ids ?? []),
                'agree_terms' => $request->agree_terms,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
            ]);

            $includeOrderTotal = IncludeOrder::whereIn('id', $request->include_order_ids ?? [])->sum('price');
            $pricing = ServicePricing::findOrFail($request->service_pricing_id);
            $finalAmount = (float) $pricing->price + (float) $includeOrderTotal;

            $service = Service::findOrFail($request->service_id);

            $providerStripe = ProviderStripe::where(
                'user_id',
                $service->user_id
            )->first();

            if (! $providerStripe) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'error' => 'Stripe key not found',
                ], 404);
            }

            $adminCommission = $finalAmount * 0.20;
            $providerAmount = $finalAmount - $adminCommission;

            $payment = ProviderPayment::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
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
                'payment_method' => $request->payment_method_id,
                'payment_method_types' => ['card'],
                'confirm' => true,
                'description' => $request->event_name,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'user_id' => (string) auth()->id(),
                ],
            ]);

            $payment->transaction_id = $paymentIntent->id;
            $payment->save();

            if ($paymentIntent->status === 'succeeded') {
                $order->status = 'confirmed';
                $order->save();

                $payment->status = 'successful';
                $payment->save();

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Payment successful',
                    'order_id' => $order->id,
                    'payment_status' => $paymentIntent->status,
                ], 201);
            }

            if (in_array($paymentIntent->status, ['requires_action', 'requires_confirmation'], true)) {
                DB::commit();

                return response()->json([
                    'status' => true,
                    'requires_action' => true,
                    'message' => 'Additional authentication required to complete the payment',
                    'order_id' => $order->id,
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                    'payment_status' => $paymentIntent->status,
                ], 200);
            }

            $order->status = 'cancelled';
            $order->save();

            $payment->status = 'failed';
            $payment->save();

            DB::commit();

            return response()->json([
                'status' => false,
                'message' => 'Payment could not be processed',
                'order_id' => $order->id,
                'payment_status' => $paymentIntent->status,
            ], 402);
        } catch (CardException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 402);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function success(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $session_id = $request->query('session_id');

        if (! $session_id) {
            return response()->json([
                'status' => false,
                'message' => 'Session ID missing',
            ], 400);
        }

        if ($order->status === 'confirmed') {
            return response()->json([
                'status' => true,
                'message' => 'Order already confirmed',
                'order' => $order,
            ], 200);
        }

        $service = Service::findOrFail($order->service_id);

        $providerStripe = ProviderStripe::where(
            'user_id',
            $service->user_id
        )->first();

        if (! $providerStripe) {
            return response()->json([
                'status' => false,
                'message' => 'Stripe key not found',
            ], 404);
        }

        Stripe::setApiKey($providerStripe->stripe_secret_key);

        DB::beginTransaction();

        try {

            $session = Session::retrieve($session_id);
            if (! $session || ! $session->payment_intent) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Stripe session',
                ], 400);
            }
            $payment_intent = PaymentIntent::retrieve($session->payment_intent);

            if (! $payment_intent || ! isset($payment_intent->status)) {
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
            switch ($payment_intent->status) {
                case 'succeeded':
                    $order->status = 'confirmed';
                    $order->save();

                    $payment->transaction_id = $payment_intent->id;
                    $payment->status = 'successful';
                    $payment->save();

                    DB::commit();

                    return response()->json([
                        'status' => true,
                        'message' => 'Payment successful',
                        'order' => $order,
                        'payment' => $payment,
                        'transaction_id' => $payment_intent->id,
                    ], 200);

                case 'failed':
                    $order->status = 'cancelled';
                    $order->save();

                    $payment->status = 'failed';
                    $payment->save();

                    DB::commit();

                    return response()->json([
                        'status' => false,
                        'message' => 'Payment failed',
                        'order' => $order,
                        'payment' => $payment,
                    ], 400);

                case 'canceled':
                    $order->status = 'cancelled';
                    $order->save();

                    $payment->status = 'failed';
                    $payment->save();

                    DB::commit();

                    return response()->json([
                        'status' => false,
                        'message' => 'Payment canceled',
                        'order' => $order,
                        'payment' => $payment,
                    ], 400);

                default:
                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' => 'Unexpected payment status: '.$payment_intent->status,
                    ], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Payment processing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateInvoice($orderId)
    {

        $order = Order::with(['service', 'Pricing', 'user'])->findOrFail($orderId);

        $pricing = $order->Pricing;
        $payment = ProviderPayment::where('order_id', $order->id)->first();

        $data = [
            'order' => $order,
            'user' => $order->user,
            'service' => $order->service,
            'pricing' => $pricing,
            'payment' => $payment,
            'total_amount' => $payment->amount,
            'transaction_id' => $payment->transaction_id,
            'payment_method' => $payment->payment_method,
            'payment_status' => $payment->status,
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
}
