<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Packhouse extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['name', 'code', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function productionLines(): HasMany
    {
        return $this->hasMany(ProductionLine::class);
    }

    public function fridges(): HasMany
    {
        return $this->hasMany(Fridge::class);
    }

    public function rawReceipts(): HasMany
    {
        return $this->hasMany(RawReceipt::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }

    public function shippingPolicies(): HasMany
    {
        return $this->hasMany(ShippingPolicy::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }
}


