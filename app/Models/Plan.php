<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'title',
        'price',
        'currency',
        'package',
        'day',
        'features',
        'stripe_product_id',
        'stripe_price_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'decimal:2',
            'status' => 'boolean',
        ];
    }
}
