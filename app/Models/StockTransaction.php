<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransaction extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'type', 'reason', 'raw_receipt_id', 'production_order_id', 'sort_record_line_id',
        'shipping_policy_id', 'sort_record_id', 'quantity_kg', 'unit_cost', 'total_cost', 'created_by',
    ];

    protected $casts = ['quantity_kg' => 'decimal:3', 'unit_cost' => 'decimal:4', 'total_cost' => 'decimal:2'];

    public function rawReceipt(): BelongsTo
    {
        return $this->belongsTo(RawReceipt::class);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function sortRecordLine(): BelongsTo
    {
        return $this->belongsTo(SortRecordLine::class);
    }

    public function sortRecord(): BelongsTo
    {
        return $this->belongsTo(SortRecord::class);
    }

    public function shippingPolicy(): BelongsTo
    {
        return $this->belongsTo(ShippingPolicy::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeOut($query)
    {
        return $query->where('type', 'out');
    }
}


