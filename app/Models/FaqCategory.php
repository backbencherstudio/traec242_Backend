<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model
{
    protected $table = 'faq_categories';

    protected $fillable = [
        'name',
        'slug',
        'order_number',
        'status',
    ];
}
