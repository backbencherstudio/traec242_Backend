<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderStripe;
use Illuminate\Http\Request;

class ProviderStripeController extends Controller
{
    public function upsert(Request $request)
    {
        $request->validate([
            'stripe_mode' => 'required|in:test,live',
            'stripe_secret_key' => 'required|string',
            'stripe_public_key' => 'required|string',
        ]);

        $user = auth()->user();
        if ($user->type != 2) {
            return response()->json([
                'message' => 'Only provider can update Stripe key.',
            ], 403);
        }

        $stripe = ProviderStripe::updateOrCreate(
            ['user_id' => $user->id],
            [
                'stripe_mode' => $request->stripe_mode,
                'stripe_secret_key' => $request->stripe_secret_key,
                'stripe_public_key' => $request->stripe_public_key,
            ]
        );

        return response()->json([
            'message' => 'Stripe key saved successfully.',
            'data' => $stripe,
        ]);
    }

    public function show()
    {
        $user = auth()->user();

        if ($user->type != 2) {
            return response()->json([
                'message' => 'Only provider can view Stripe keys.',
            ], 403);
        }

        $stripe = ProviderStripe::where('user_id', $user->id)->first();

        if (! $stripe) {
            return response()->json([
                'message' => 'Stripe key not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Stripe key retrieved successfully.',
            'data' => $stripe,
        ]);
    }
}
