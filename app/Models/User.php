<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'phone',
        'type',
        'role',
        'status',
        'image',
        'google_id',
        'address',
        'city',
        'state',
        'zip_code',
        'category_id',
        'jwt_token',
        'provider_status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'jwt_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'languages' => 'array',
            'category_id' => 'array',
            'type' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function conversations()
{
    return $this->belongsToMany(Conversation::class)
                ->withPivot('last_read_at', 'is_admin')
                ->withTimestamps();
}
}
