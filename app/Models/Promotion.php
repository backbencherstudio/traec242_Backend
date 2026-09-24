<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'discount',
        'type',
        'start_date',
        'end_date',
        'status',

    ];
}
