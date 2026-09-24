<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderStripe extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_mode',
        'stripe_secret_key',
        'stripe_public_key',
    ];
}
