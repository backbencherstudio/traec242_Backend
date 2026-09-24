<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertStripeRequest;
use App\Http\Resources\StripeSettingResource;
use App\Models\Stripe;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('admin-stripe', weight: 4)]
class StripeController extends Controller
{
    public function upsert(UpsertStripeRequest $request): JsonResponse
    {
        $stripe = Stripe::updateOrCreate([], $request->validated());

        return response()->json([
            'message' => 'Stripe payment settings saved successfully.',
            'data' => new StripeSettingResource($stripe),
        ]);
    }

    public function show(): JsonResponse
    {
        if ((int) auth()->user()->type !== 1) {
            return response()->json([
                'message' => 'Only Admin can view Stripe keys.',
            ], 403);
        }

        $stripe = Stripe::first();
        if (! $stripe) {
            return $this->sendError('Stripe settings not found.', [], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new StripeSettingResource($stripe),
        ]);
    }
}
