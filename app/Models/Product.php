<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'name', 'code', 'unit', 'carton_weight_kg', 'grades',
        'min_cooling_hours', 'waste_threshold_pct', 'is_active',
    ];

    protected $casts = [
        'grades' => 'array',
        'carton_weight_kg' => 'decimal:3',
        'min_cooling_hours' => 'decimal:2',
        'waste_threshold_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }
}


