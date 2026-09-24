<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\StripeSettingResource;
use App\Models\ProviderStripe;
use App\Models\Service;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('provider-stripe', weight: 3)]
class ProviderStripeController extends Controller
{
    public function upsert(Request $request): JsonResponse
    {
        $request->validate([
            'stripe_mode' => 'required|in:test,live',
            'stripe_secret_key' => 'required|string',
            'stripe_public_key' => 'required|string',
        ]);

        $user = auth()->user();
        if ((int) $user->type !== 2) {
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
            'data' => new StripeSettingResource($stripe),
        ]);
    }

    public function show(): JsonResponse
    {
        $user = auth()->user();

        if ((int) $user->type !== 2) {
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
            'data' => new StripeSettingResource($stripe),
        ]);
    }

    #[Group('public-provider-stripe', weight: 1)]
    public function getPublicKey($serviceId): JsonResponse
    {
        $service = Service::find($serviceId);

        if (! $service) {
            return response()->json([
                'message' => 'Service not found',
            ], 404);
        }

        $stripe = ProviderStripe::where('user_id', $service->user_id)->first();

        if (! $stripe) {
            return response()->json([
                'message' => 'Provider stripe account not found',
            ], 404);
        }

        return response()->json([
            'public_key' => $stripe->stripe_public_key,
        ]);
    }
}
