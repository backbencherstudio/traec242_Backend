<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\IncludeOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServicePricing;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['service', 'pricing', 'payment', 'user'])
            ->get()
            ->map(function ($order) {

                $dueIn = Carbon::parse($order->event_end_date)->diff(Carbon::now());
                $days = $dueIn->d;
                $hours = $dueIn->h;
                $minutes = $dueIn->i;

                return [
                    'service_image' => $order->service->image,
                    'event_name' => $order->event_name,
                    'order_by' => "{$order->first_name} {$order->last_name}",
                    'price' => "$" . number_format($order->payment->amount),
                    'due_in' => "{$days}d {$hours}h {$minutes}m",
                    'status' => $order->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function show($id)
    {

        $order = Order::with(['service', 'pricing', 'payment', 'user'])
            ->find($id);

        if (!$order) {
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
            'location' => [
                'full_name' => $order->user->first_name . ' ' . $order->user->last_name,
                'email' => $order->email,
                'phone' => $order->phone,
                'address' => $order->address,
            ],
            'event_details' => [
                'event_type' => $order->event_description,
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
            'payment' => [
                'amount_paid' => $order->payment ? $order->payment->amount : 'N/A',
                'payment_method' => $order->payment ? $order->payment->payment_method : 'N/A',
            ],
            'order_status' => [
                'status' => $order->status,
                'order_date' => Carbon::parse($order->created_at)->format('M d, Y h:i A'),
                'event_due_in' => "{$days}d {$hours}h {$minutes}m",
                'completed_date' => $order->completed_date ? Carbon::parse($order->completed_date)->format('M d, Y') : null,
            ],
            'provider_details' => [
                'provider_name' => $order->service->name,
                'provider_image' => $order->service->image,
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $orderDetails,
        ]);
    }

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
            'event_start_date' => 'required|date',
            'event_end_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
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

            $includeOrderIds = $request->include_order_ids;
            $includeOrders = IncludeOrder::whereIn('id', $includeOrderIds)->get();
            $includeOrderTotal = $includeOrders->sum('price');

            $pricing = ServicePricing::findOrFail($request->service_pricing_id);
            $servicePrice = $pricing->price;
            $finalAmount = $servicePrice + $includeOrderTotal;

            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $request->event_name,
                            ],
                            'unit_amount' => $finalAmount * 100,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('order.success', ['orderId' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('order.cancel', ['orderId' => $order->id]),
            ]);

            $adminCommission = $finalAmount * 0.20;
            $providerAmount = $finalAmount - $adminCommission;

            Payment::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'transaction_id' => null,
                'amount' => $finalAmount,
                'admin_commission_amount' => $adminCommission,
                'provider_amount' => $providerAmount,
                'currency' => 'USD',
                'payment_method' => 'stripe_checkout',
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'checkout_url' => $checkoutSession->url,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function success(Request $request, $orderId)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $order = Order::findOrFail($orderId);

        $session_id = $request->query('session_id');

        try {

            $session = Session::retrieve($session_id);
            $payment_intent = PaymentIntent::retrieve($session->payment_intent);

            switch ($payment_intent->status) {
                case 'succeeded':
                    $order->status = 'confirmed';
                    $order->save();

                    $payment = Payment::where('order_id', $order->id)->first();
                    $payment->transaction_id = $payment_intent->id;
                    $payment->status = 'successful';
                    $payment->save();

                    return response()->json([
                        'status' => true,
                        'message' => 'Payment successful',
                        'order' => $order,
                        'payment' => $payment,
                        'transaction_id' => $payment_intent->id,
                    ], 200);

                case 'failed':
                    $order->status = 'failed';
                    $order->save();

                    $payment = Payment::where('order_id', $order->id)->first();
                    $payment->status = 'failed';
                    $payment->save();

                    return response()->json([
                        'status' => false,
                        'message' => 'Payment failed',
                        'order' => $order,
                        'payment' => $payment,
                    ], 400);

                case 'canceled':
                    $order->status = 'canceled';
                    $order->save();

                    $payment = Payment::where('order_id', $order->id)->first();
                    $payment->status = 'canceled';
                    $payment->save();

                    return response()->json([
                        'status' => false,
                        'message' => 'Payment canceled',
                        'order' => $order,
                        'payment' => $payment,
                    ], 400);

                default:
                    return response()->json([
                        'status' => false,
                        'message' => 'Unexpected payment status: ' . $payment_intent->status,
                    ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateInvoice($orderId)
    {

        $order = Order::with(['service', 'Pricing', 'user'])->findOrFail($orderId);

        $pricing = $order->Pricing;
        $payment = Payment::where('order_id', $order->id)->first();

        $data = [
            'order' => $order,
            'user' => $order->user,
            'service' => $order->service,
            'pricing' => $pricing,
            'payment' => $payment,
            'total_amount' => $pricing->price,
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
        return $pdf->download('invoice_' . $orderId . '.pdf');
    }
}
