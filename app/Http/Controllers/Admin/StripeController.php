<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stripe;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function upsert(Request $request)
    {
        $request->validate([
            'stripe_mode' => 'required|in:test,live',
            'stripe_secret_key' => 'required|string',
            'stripe_public_key' => 'required|string',
        ]);

        $stripe = Stripe::first();

        if ($stripe) {
            $stripe->update(
                [
                    'stripe_mode' => $request->stripe_mode,
                    'stripe_secret_key' => $request->stripe_secret_key,
                    'stripe_public_key' => $request->stripe_public_key,
                ]
            );
        } else {
            $stripe = Stripe::create(
                [
                    'stripe_mode' => $request->stripe_mode,
                    'stripe_secret_key' => $request->stripe_secret_key,
                    'stripe_public_key' => $request->stripe_public_key,
                ]
            );
        }

        return response()->json([
            'message' => 'Stripe payment settings saved successfully.',
            'data' => $stripe
        ]);
    }
}
