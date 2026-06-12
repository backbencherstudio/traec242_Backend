<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $reviews = Review::with(['user', 'service'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user->name ?? null,
                    'service_title' => $review->service->title ?? null,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'status' => $review->status,
                    'created_at' => $review->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    public function review($id)
    {
        $service = Service::with([
            'reviews' => function ($query) {
                $query->where('status', 'approved');
            },
            'reviews.user',
        ])->findOrFail($id);

        $reviews = $service->reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'user_name' => $review->user->name ?? null,
                'rating' => $review->rating,
                'review' => $review->review,
                'created_at' => $review->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'service_title' => $service->title,
            'data' => $reviews,
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $review = Review::with(['user', 'service'])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $review->id,
                'user_name' => $review->user->name ?? null,
                'service_title' => $review->service->title ?? null,
                'rating' => $review->rating,
                'review' => $review->review,
                'status' => $review->status,
                'created_at' => $review->created_at,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'service_id' => 'required|exists:services,id',
                'review' => 'nullable|string',
            ],
            [
                'service_id.required' => 'Service ID is required.',
                'service_id.exists' => 'Service not found.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'service_id' => $request->service_id,
            'rating' => 5,
            'review' => $request->review,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'service_id' => 'required|exists:services,id',
                'review' => 'nullable|string',
                'rating' => 'nullable|integer|min:1|max:5',
            ],
            [
                'service_id.required' => 'Service ID is required.',
                'service_id.exists' => 'Service not found.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        $review->update([
            'service_id' => $request->service_id,
            'review' => $request->review,
            'rating' => $request->rating ?? $review->rating,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'data' => $review,
        ], 200);
    }

    public function changeStatus(Request $request, $id)
    {
        $allowedStatus = ['approved', 'rejected'];

        $status = $request->status;

        if (! in_array($status, $allowedStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status.',
            ], 400);
        }

        $review = Review::findOrFail($id);

        $review->update([
            'status' => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Review {$status} successfully",
            'data' => $review,
        ]);
    }
}
