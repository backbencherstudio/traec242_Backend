<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stripe;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function upsert(Request $request)
    {

        $user = auth()->user();
        if ($user->type != 1) {
            return response()->json([
                'message' => 'Only Admin can update Stripe key.',
            ], 403);
        }

        $request->validate([
            'stripe_mode' => 'required|in:test,live',
            'stripe_secret_key' => 'required|string',
            'stripe_public_key' => 'required|string',
            'stripe_webhook_secret' => 'nullable|string',
        ]);

        $stripe = Stripe::first();

        if ($stripe) {
            $stripe->update(
                [
                    'stripe_mode' => $request->stripe_mode,
                    'stripe_secret_key' => $request->stripe_secret_key,
                    'stripe_public_key' => $request->stripe_public_key,
                    'stripe_webhook_secret' => $request->stripe_webhook_secret,
                ]
            );
        } else {
            $stripe = Stripe::create(
                [
                    'stripe_mode' => $request->stripe_mode,
                    'stripe_secret_key' => $request->stripe_secret_key,
                    'stripe_public_key' => $request->stripe_public_key,
                    'stripe_webhook_secret' => $request->stripe_webhook_secret,
                ]
            );
        }

        return response()->json([
            'message' => 'Stripe payment settings saved successfully.',
            'data' => $stripe,
        ]);
    }

    public function show()
    {
        $user = auth()->user();
        if ($user->type != 1) {
            return response()->json([
                'message' => 'Only Admin can view Stripe keys.',
            ], 403);
        }
        $stripe = Stripe::firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $stripe,
        ]);
    }
}
