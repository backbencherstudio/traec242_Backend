<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'faq_category_id',
        'question',
        'answer',
        'status',
        'order_number',
    ];

    public function faq_category()
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}
