<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stripe extends Model
{
    protected $fillable = [
        'stripe_mode',
        'stripe_secret_key',
        'stripe_public_key',
        'stripe_webhook_secret',
    ];
}
