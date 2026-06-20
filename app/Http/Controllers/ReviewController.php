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

        $reviews = Review::with(['user', 'service'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

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

        $reviews = $service->reviews;

        return response()->json([
            'success' => true,
            'service_title' => $service->title,
            'data' => $reviews,
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();

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
            'data' => $review
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'review' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();

        $review = Review::create([
            'user_id' => $user->id,
            'service_id' => $validated['service_id'],
            'rating' => $validated['rating'],
            'review' => $validated['review'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);
    }


    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'reply' => 'nullable|string',
        ]);

        $review = Review::findOrFail($id);

        $review = $review->update([
            'reply' => $validated['reply'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review replied successfully.',
            'data' => $review,
        ], 201);
    }
}
