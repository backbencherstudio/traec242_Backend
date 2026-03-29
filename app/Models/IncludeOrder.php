<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncludeOrder extends Model
{
    use HasFactory;

    protected $table = 'include_orders';

    protected $fillable = [
        'user_id',
        'name',
        'price',
        'status',
    ];

    protected $attributes = [
        'status' => 1,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
