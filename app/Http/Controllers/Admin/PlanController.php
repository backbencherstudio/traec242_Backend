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
            'name' => 'required|in:Basic,Premium,Enterprise',
            'title' => 'nullable|string',
            'price' => 'required|numeric',
            'currency' => 'nullable|string',
            'package' => 'required|in:Free,Monthly,Annual',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $day = 0;

        if ($request->package === 'Free') {
            $day = 7;
        } elseif ($request->package === 'Monthly') {
            $day = 30;
        } elseif ($request->package === 'Annual') {
            $day = 365;
        }

        $plan = Plan::create([
            'name' => $request->name,
            'title' => $request->title ?? $request->name . ' Plan',
            'price' => $request->price,
            'currency' => $request->currency ?? 'SAR',
            'package' => $request->package,
            'day' => $day,
            'features' => $request->features,
            'status' => 1,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully',
            'data' => $plan
        ], 201);
    }
}
