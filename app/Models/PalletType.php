<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PalletType extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['name', 'max_cartons', 'max_weight_kg', 'is_active'];

    protected $casts = [
        'max_cartons' => 'integer',
        'max_weight_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }
}


