<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromotionRequest;
use App\Http\Requests\Admin\UpdatePromotionRequest;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    public function index(): JsonResponse
    {
        $promotions = Promotion::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Promotion list',
            'data' => PromotionResource::collection($promotions),
        ]);
    }

    public function store(StorePromotionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? 1;

        $promotion = Promotion::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Promotion created successfully',
            'data' => new PromotionResource($promotion),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PromotionResource($promotion),
        ]);
    }

    public function update(UpdatePromotionRequest $request, $id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found',
            ], 404);
        }

        $promotion->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Promotion updated successfully',
            'data' => new PromotionResource($promotion),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found',
            ], 404);
        }

        $promotion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promotion deleted successfully',
        ]);
    }

    public function activePromotions(): JsonResponse
    {
        $today = now()->toDateString();

        $promotions = Promotion::where('status', 1)
            ->where(function ($query) use ($today) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Active promotions',
            'data' => PromotionResource::collection($promotions),
        ]);
    }
}
