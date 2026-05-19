<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'image',
        'status',
    ];

    public function subcategory()
    {
        return $this->hasMany(Subcategory::class);
    }
}
