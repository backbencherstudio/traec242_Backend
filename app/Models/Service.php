<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'title',
        'user_id',
        'category_id',
        'location',
        'description',
        'image',
        'feature_service',
        'status'
    ];

    protected $casts = [
        'image' => 'array',
        'feature_service' => 'boolean',
        'status' => 'boolean',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


    public function pricings(): HasMany
    {
        return $this->hasMany(ServicePricing::class);
    }
}
