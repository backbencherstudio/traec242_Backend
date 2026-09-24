<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ReplyReviewRequest;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $reviews = Review::with(['user', 'service'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return $this->sendResponse(ReviewResource::collection($reviews));
    }

    public function providerReviews(): JsonResponse
    {
        $providerId = auth()->id();

        $reviews = Review::with(['user', 'service', 'order'])
            ->whereHas('service', function ($query) use ($providerId): void {
                $query->where('user_id', $providerId);
            })
            ->latest()
            ->get();

        return $this->sendResponse(ReviewResource::collection($reviews));
    }

    public function review($id): JsonResponse
    {
        $service = Service::with(['reviews.user'])->findOrFail($id);

        return $this->sendResponse([
            'service_title' => $service->title,
            'reviews' => ReviewResource::collection($service->reviews),
        ]);
    }

    public function show($id): JsonResponse
    {
        $userId = auth()->id();

        $review = Review::with(['user', 'service'])
            ->where('id', $id)
            ->where(function ($query) use ($userId): void {
                $query->where('user_id', $userId)
                    ->orWhereHas('service', function ($serviceQuery) use ($userId): void {
                        $serviceQuery->where('user_id', $userId);
                    });
            })
            ->first();

        if (! $review) {
            return $this->sendError('Review not found.');
        }

        return $this->sendResponse(new ReviewResource($review));
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $validated = $request->validated();
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

        return $this->sendResponse(new ReviewResource($review), 'Review submitted successfully.', 201);
    }

    public function reply(ReplyReviewRequest $request, $id): JsonResponse
    {
        $review = Review::with('service')->findOrFail($id);

        if ((int) $review->service->user_id !== (int) auth()->id()) {
            return $this->sendError('You are not authorized to reply to this review.', [], 403);
        }

        $review->update([
            'reply' => $request->reply,
        ]);

        return $this->sendResponse(new ReviewResource($review), 'Review replied successfully.');
    }
}
