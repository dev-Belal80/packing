<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLine extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['packhouse_id', 'name', 'status', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }
}


