<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::all();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|in:free,premium',
            'title' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string',
            'package' => 'required|in:free,monthly,yearly',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string|unique:plans,stripe_price_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $daysMap = [
            'free' => 7,
            'monthly' => 30,
            'yearly' => 365,
        ];

        $data = $validator->validated();

        $data['day'] = $daysMap[$data['package']] ?? 0;
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
            'data' => $plan,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::find($id);

        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|in:free,premium',
            'title' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string',
            'package' => 'nullable|in:free,monthly,yearly',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'status' => 'nullable|in:0,1',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string|unique:plans,stripe_price_id,'.$plan->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $daysMap = [
            'free' => 7,
            'monthly' => 30,
            'yearly' => 365,
        ];

        $data = $validator->validated();

        if (isset($data['package'])) {
            $data['day'] = $daysMap[$data['package']] ?? 0;
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
            'data' => $plan,
        ]);
    }

    public function destroy($id)
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
