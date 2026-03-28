<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServicePricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{

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
            'include_order_id' => 'nullable|string|unique:orders,include_order_id',
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
                'include_order_id' => $request->include_order_id ?? Str::uuid(),
                'agree_terms' => $request->agree_terms,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
            ]);

            $pricing = ServicePricing::findOrFail($request->service_pricing_id);

            $amount = $pricing->price;
            $admin_commission = $amount * 0.20;
            $provider_amount = $amount - $admin_commission;

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'transaction_id' => Str::uuid(),
                'amount' => $amount,
                'admin_commission_amount' => $admin_commission,
                'provider_amount' => $provider_amount,
                'currency' => 'USD',
                'payment_method' => $request->payment_method,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'order' => $order,
                'payment' => $payment,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
