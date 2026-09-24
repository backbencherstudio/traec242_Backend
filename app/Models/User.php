<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Billable;
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles {
        assignRole as protected spatieAssignRole;
        syncRoles as protected spatieSyncRoles;
    }
    use SoftDeletes;

    protected $guard_name = 'api';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'status',
        'image',
        'google_id',
        'address',
        'city',
        'state',
        'zip_code',
        'bio',
        'languages',
        'category_id',
        'plan_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'languages' => 'array',
            'category_id' => 'array',
            'plan_id' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function getNameAttribute(): ?string
    {
        return $this->first_name;
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['first_name'] = $value;
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isProvider(): bool
    {
        return $this->hasRole('provider');
    }

    public function isUser(): bool
    {
        return $this->hasRole('user');
    }

    /**
     * Enforce a single role per user:
     * Assigning a role replaces any existing role so the user always has at most one role.
     */
    public function assignRole(...$roles): static
    {
        $role = is_array($roles[0] ?? null) ? ($roles[0][0] ?? null) : ($roles[0] ?? null);

        if (! $role) {
            return $this;
        }

        if ($this->exists) {
            $this->roles()->detach();
            $this->setRelation('roles', collect());
        }

        return $this->spatieAssignRole($role);
    }

    /**
     * Enforce a single role per user:
     * Syncing roles replaces any existing role with the single specified role.
     */
    public function syncRoles(...$roles): static
    {
        $role = is_array($roles[0] ?? null) ? ($roles[0][0] ?? null) : ($roles[0] ?? null);

        if (! $role) {
            if ($this->exists) {
                $this->roles()->detach();
                $this->setRelation('roles', collect());
                $this->forgetCachedPermissions();
            }

            return $this;
        }

        return $this->assignRole($role);
    }

    /**
     * Explicit helper to set the user's single role.
     */
    public function setRole(string|Role $role): static
    {
        return $this->assignRole($role);
    }

    public function getRoleAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function getTypeAttribute(): string
    {
        return $this->getRoleNames()->first() ?? 'user';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function providerPayments(): HasMany
    {
        return $this->hasMany(ProviderPayment::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot('last_read_at', 'is_admin')
            ->withTimestamps();
    }
}
