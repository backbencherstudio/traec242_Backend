<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'service_id',
        'service_pricing_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'event_name',
        'guest_count',
        'event_duration',
        'event_description',
        'event_start_date',
        'event_end_date',
        'start_time',
        'end_time',
        'question_one',
        'question_two',
        'question_three',
        'question_four',
        'question_five',
        'question_six',
        'include_order_ids',
        'agree_terms',
        'payment_method',
        'status',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function pricing()
    {
        return $this->belongsTo(ServicePricing::class, 'service_pricing_id');
    }

    public function providerPayments()
    {
        return $this->hasOne(ProviderPayment::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Whether the given user may leave a review for this order:
     * they own it, it is completed, and it has not been reviewed yet.
     */
    public function canBeReviewedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id
            && $this->status === 'completed'
            && $this->review === null;
    }
}
