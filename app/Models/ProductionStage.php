<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionStage extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['name', 'order'];

    protected $casts = ['order' => 'integer'];

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }
}


