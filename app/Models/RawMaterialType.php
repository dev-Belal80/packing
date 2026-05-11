<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialType extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['name', 'unit', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function rawReceipts(): HasMany
    {
        return $this->hasMany(RawReceipt::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(RawDeliveryOrder::class);
    }

    public function gateInquiries(): HasMany
    {
        return $this->hasMany(GateInquiry::class);
    }
}


