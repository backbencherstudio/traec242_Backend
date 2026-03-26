<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePricing extends Model
{
    protected $fillable = [
        'service_id',
        'service_type',
        'duration',
        'price',
        'description',
        'features'
    ];

    protected $casts = [
        'service_type' => ServiceType::class,
        'features' => 'array',
        'price' => 'decimal:2',
    ];


    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
