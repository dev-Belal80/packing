<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'location',
        'plan',
        'subscription_ends_at',
        'max_users',
        'is_active',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'max_users' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function packhouses(): HasMany
    {
        return $this->hasMany(Packhouse::class);
    }

    public function isSubscriptionActive(): bool
    {
        return ! $this->subscription_ends_at || $this->subscription_ends_at->isFuture();
    }

    public function isReadOnly(): bool
    {
        return ! $this->isSubscriptionActive();
    }
}

