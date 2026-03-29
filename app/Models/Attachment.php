<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Attachment extends Model
{
    protected $fillable = ['message_id', 'file_path', 'file_name', 'file_type', 'file_size'];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }


    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}
