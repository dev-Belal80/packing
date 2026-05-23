<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    // BR-002: Flexible contact — role determined per document
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['name', 'phone', 'email', 'national_id', 'address', 'type', 'tags', 'notes', 'is_active'];

    protected $casts = ['tags' => 'array', 'is_active' => 'boolean'];

    public function rawReceipts(): HasMany
    {
        return $this->hasMany(RawReceipt::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(RawDeliveryOrder::class);
    }

    public function shippingPolicies(): HasMany
    {
        return $this->hasMany(ShippingPolicy::class, 'importer_contact_id');
    }

    public function isSupplier(): bool
    {
        return in_array('supplier', $this->tags ?? [], true);
    }

    public function isCustomer(): bool
    {
        return in_array('customer', $this->tags ?? [], true);
    }
}


