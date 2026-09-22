<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

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

        $customerName = $this->user ? trim("{$this->user->name} {$this->user->last_name}") : '';
        $amountPaid = $this->providerPayments ? '$'.number_format((float) $this->providerPayments->amount) : '$0';

        return [
            'id' => $this->id,
            'order_started' => [
                'order_by' => $customerName,
                'event_name' => $this->event_name,
            ],
            'location & contact' => [
                'full_name' => trim("{$this->first_name} {$this->last_name}"),
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => implode(', ', array_filter([
                    $this->address,
                    $this->city,
                    trim("{$this->state} {$this->zip_code}"),
                ])),
            ],
            'event_details' => [
                'event_type' => $this->service?->title,
                'event_name' => $this->event_name,
                'duration' => $this->event_duration,
                'guests' => $this->guest_count,
                'description' => $this->event_description,
            ],
            'questionnaire' => [
                'party_theme' => $this->question_one,
                'music_preference' => $this->question_two,
                'must_play_songs' => $this->question_three,
                'dance_games' => $this->question_four,
                'entrance_style' => $this->question_five,
                'additional_notes' => $this->question_six,
            ],
            'order_details' => [
                'service_image' => $this->service?->image_url ?? $this->service?->image,
                'event_name' => $this->event_name,
                'order_by' => $customerName,
                'status' => $this->status,
                'can_review' => $user ? $this->canBeReviewedBy($user) : false,
                'review_id' => $this->review?->id,
                'review' => $reviewData,
                'order_number' => '#ORD'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT),
                'end_date' => $this->event_end_date ? Carbon::parse($this->event_end_date)->format('d M, Y') : null,
                'amount_paid' => $amountPaid,
            ],
        ];
    }
}
