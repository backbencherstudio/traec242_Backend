<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    protected array $daysMap = [
        'free' => 7,
        'monthly' => 30,
        'yearly' => 365,
    ];

    public function index(): JsonResponse
    {
        $plans = Plan::all();

        return response()->json([
            'success' => true,
            'data' => PlanResource::collection($plans),
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['day'] = $this->daysMap[$data['package']] ?? 0;
        $data['title'] = $data['title'] ?? $data['name'].' Plan';
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['features'] = $data['features'] ?? [];
        $data['stripe_product_id'] = $data['stripe_product_id'] ?? null;
        $data['stripe_price_id'] = $data['stripe_price_id'] ?? null;
        $data['status'] = 1;

        $plan = Plan::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully',
            'data' => new PlanResource($plan),
        ], 201);
    }

    public function update(UpdatePlanRequest $request, $id): JsonResponse
    {
        $plan = Plan::find($id);
        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
            ], 404);
        }

        $data = $request->validated();

        if (isset($data['package'])) {
            $data['day'] = $this->daysMap[$data['package']] ?? 0;
        }

        if (isset($data['name']) && ! isset($data['title'])) {
            $data['title'] = $data['name'].' Plan';
        }

        if (array_key_exists('currency', $data) && ! $data['currency']) {
            $data['currency'] = 'USD';
        }

        if (array_key_exists('features', $data) && is_null($data['features'])) {
            $data['features'] = [];
        }

        $plan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully',
            'data' => new PlanResource($plan),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $plan = Plan::find($id);
        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
            ], 404);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully',
        ]);
    }
}
