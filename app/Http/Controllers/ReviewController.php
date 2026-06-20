<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $reviews = Review::with(['user', 'service'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return $this->sendResponse($reviews);
    }

    public function review($id)
    {
        $service = Service::with(['reviews.user'])->findOrFail($id);

        return $this->sendResponse([
            'service_title' => $service->title,
            'reviews' => $service->reviews,
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
            return $this->sendError('Review not found.');
        }

        return $this->sendResponse($review);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'review' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();
        $service = Service::findOrFail($validated['service_id']);

        if ((int) $service->user_id === (int) $user->id) {
            return $this->sendError('You cannot review your own service.', [], 403);
        }

        if (Review::where('user_id', $user->id)->where('service_id', $service->id)->exists()) {
            return $this->sendError('You have already reviewed this service.', [], 409);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        return $this->sendResponse($review, 'Review submitted successfully.', 201);
    }

    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'reply' => 'required|string',
        ]);

        $review = Review::with('service')->findOrFail($id);

        if ((int) $review->service->user_id !== (int) auth()->id()) {
            return $this->sendError('You are not authorized to reply to this review.', [], 403);
        }

        $review->update([
            'reply' => $validated['reply'],
        ]);

        return $this->sendResponse($review, 'Review replied successfully.');
    }
}
