<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $dueIn = Carbon::parse($this->event_end_date)->diff(Carbon::now());
        $days = $dueIn->d;
        $hours = $dueIn->h;
        $minutes = $dueIn->i;

        $provider = $this->service?->user;
        $providerName = $provider ? trim("{$provider->name} {$provider->last_name}") : '';

        $paymentAmount = $this->providerPayments ? '$'.number_format((float) $this->providerPayments->amount) : '$0';

        $reviewData = null;
        if ($this->review) {
            $reviewData = [
                'id' => $this->review->id,
                'rating' => $this->review->rating,
                'review' => $this->review->review,
                'reply' => $this->review->reply,
                'has_replied' => $this->review->reply !== null,
                'reviewed_at' => $this->review->created_at,
            ];
        }

        return [
            'order_id' => $this->id,
            'id' => $this->id,
            'service_image' => $this->service?->image,
            'event_name' => $this->event_name,
            'provider_name' => $providerName,
            'price' => $paymentAmount,
            'due_in' => "{$days}d {$hours}h {$minutes}m",
            'status' => $this->status,
            'can_review' => $user ? $this->canBeReviewedBy($user) : false,
            'review_id' => $this->review?->id,
            'review' => $reviewData,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
