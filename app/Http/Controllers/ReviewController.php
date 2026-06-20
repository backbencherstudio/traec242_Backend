<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

    public function providerReviews()
    {
        $providerId = auth()->id();

        $reviews = Review::with(['user', 'service', 'order'])
            ->whereHas('service', function ($query) use ($providerId) {
                $query->where('user_id', $providerId);
            })
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'order_id' => $review->order_id,
                    'service_id' => $review->service_id,
                    'service_title' => $review->service?->title,
                    'reviewer_name' => trim("{$review->user?->name} {$review->user?->last_name}"),
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'reply' => $review->reply,
                    'has_replied' => $review->reply !== null,
                    'created_at' => $review->created_at,
                ];
            });

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
            'order_id' => 'required|exists:orders,id',
            'review' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();
        $order = Order::findOrFail($validated['order_id']);

        if ((int) $order->user_id !== (int) $user->id) {
            return $this->sendError('You can only review your own order.', [], 403);
        }

        if ($order->status !== 'completed') {
            return $this->sendError('You can only review an order after it is completed.', [], 403);
        }

        if (Review::where('order_id', $order->id)->exists()) {
            return $this->sendError('You have already reviewed this order.', [], 409);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'service_id' => $order->service_id,
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
