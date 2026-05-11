<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fridge extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['packhouse_id', 'name', 'capacity_tons', 'min_temp', 'max_temp', 'is_active'];

    protected $casts = [
        'capacity_tons' => 'decimal:2',
        'min_temp' => 'decimal:2',
        'max_temp' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function palletCoolings(): HasMany
    {
        return $this->hasMany(PalletCooling::class);
    }
}


